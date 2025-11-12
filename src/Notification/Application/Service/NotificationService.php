<?php

declare(strict_types=1);

namespace App\Notification\Application\Service;

use App\Notification\Application\Service\Channel\EmailService;
use App\Notification\Application\Service\Channel\PushService;
use App\Notification\Application\Service\Channel\SmsService;
use App\Notification\Domain\Entity\EmailNotification;
use App\Notification\Domain\Entity\Enum\Scope;
use App\Notification\Domain\Entity\PushNotification;
use App\Notification\Domain\Entity\SmsNotification;
use App\Notification\Infrastructure\Repository\NotificationRepository;
use DateTime;
use Doctrine\ORM\Exception\ORMException;
use Exception;
use InvalidArgumentException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @package App\Notification\Application\Service
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class NotificationService
{
    public function __construct(
        private EmailService $emailService,
        private SmsService $smsService,
        private PushService $pushService,
        private NotificationRepository $notificationRepository
    ) {
    }

    public function sendNotificationEmail(SmsNotification|EmailNotification|PushNotification $notification): array
    {
        $response = [];

        try {
            $response = $this->emailService->generateEmail($notification, null);
        } catch (ClientExceptionInterface | DecodingExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface) {
        }
        $notification->setCompletedAt(new DateTime('now'));
        $this->notificationRepository->save($notification);

        return $response;
    }

    /**
     * @throws ORMException
     */
    public function sendNotification(?array $users, SmsNotification|EmailNotification|PushNotification $notification, string $channel): array
    {
        $response = [];

        if ($users) {
            foreach (array_chunk($users, 50) as $batch) {
                try {
                    $response[] = $this->sendBatch($batch, $channel, $notification);
                } catch (Exception | TransportExceptionInterface | \Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
                    throw new InvalidArgumentException($e->getMessage());
                }
            }
        } else {
            $response['Messages'] = $this->sendBatch(null, $channel, $notification);
            $notification->setCompletedAt(new DateTime('now'));
            $this->notificationRepository->save($notification);
        }
        $notification->setCompletedAt(new DateTime('now'));
        $this->notificationRepository->save($notification);

        return $response;
    }

    /**
     * @throws ORMException
     */
    public function fetchAllMembers(?array $scopeTarget, Scope $scope, string $channel): array
    {
        if ($scopeTarget) {
            $users = $this->generateAllUsers($scopeTarget, $scope);
        } else {
            $users = $this->generateAllUsers(null, $scope);
        }

        if (!empty($users)) {
            return array_filter($users, static function ($user) use ($channel) {
                return match ($channel) {
                    'EMAIL' => isset($user['email']),
                    'PUSH' => isset($user['name']),
                    'SMS' => isset($user['phone']),
                    default => false,
                };
            });
        }

        throw new ORMException('Empty members');
    }

    public function generateAllUsers(?array $scopeTarget, Scope $scope): array
    {
        return $this->getUsers();
    }

    public function getUsers(): array
    {
        return [
            [
                'id' => '123e4567-e89b-12d3-a456-426614174000',
                'name' => 'Rami Aouinti',
                'title' => 'Backend-end Developer',
                'email' => 'rami.aouinti@gmail.com',
                'avatar' => 'https://robohash.org/rami',
                'phone' => '004917635587613',
                'role' => 'Member',
            ],
        ];
    }

    /**
     * @throws ORMException
     */
    private function sendBatch(?array $users, string $channel, SmsNotification|EmailNotification|PushNotification $notification): array
    {
        $response = [];
        [$service, $method] = match ($notification::class) {
            SmsNotification::class => [$this->smsService, 'generateSms'],
            EmailNotification::class => [$this->emailService, 'generateEmail'],
            PushNotification::class => [$this->pushService, 'generatePushNotification'],
            default => throw new InvalidArgumentException('Invalid notification type received')
        };

        if ($users) {
            foreach ($users as $user) {
                $response[] = $service->{$method}($notification, $user);
            }

            return $response;
        }

        if ($notification instanceof EmailNotification) {
            $response[] = $service->{$method}($notification, null);
        }

        return $response;
    }
}
