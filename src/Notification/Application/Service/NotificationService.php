<?php

declare(strict_types=1);

namespace App\Notification\Application\Service;

use App\Notification\Application\Service\Channel\EmailService;
use App\Notification\Application\Service\Channel\PushService;
use App\Notification\Application\Service\Channel\SmsService;
use App\Notification\Domain\Entity\EmailNotification;
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

    /**
     * @param SmsNotification|EmailNotification|PushNotification $notification
     * @return array
     */
    public function sendNotificationEmail(SmsNotification|EmailNotification|PushNotification $notification): array
    {
        $response = [];
        try {
            $response = $this->emailService->generateEmail($notification, null);
        } catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface) {
        }
        $notification->setCompletedAt(new DateTime('now'));
        $this->notificationRepository->save($notification);
        return $response;
    }

    /**
     * @param array|null $users
     * @param SmsNotification|EmailNotification|PushNotification $notification
     * @param string $channel
     * @return array
     * @throws ORMException
     */
    public function sendNotification(?array $users, SmsNotification|EmailNotification|PushNotification $notification, string $channel): array
    {
        $response = [];

        if($users) {
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

        return  $response;
    }

    /**
     * @param array|null $users
     * @param string $channel
     * @param SmsNotification|EmailNotification|PushNotification $notification
     * @return array
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

        if($users) {
            foreach ($users as $user) {
                $response[] = $service->{$method}($notification, $user, $channel);
            }
        }

        return $response;
    }
}
