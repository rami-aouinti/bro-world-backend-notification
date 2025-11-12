<?php

declare(strict_types=1);

namespace App\Notification\Application\Service\Scope;

use App\Notification\Application\ApiProxy\UserProxy;
use App\Notification\Application\Service\Interfaces\NotificationSenderInterface;
use App\Notification\Application\Service\NotificationService;
use App\Notification\Domain\Entity\EmailNotification;
use App\Notification\Domain\Entity\PushNotification;
use App\Notification\Domain\Entity\SmsNotification;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

/**
 * @package App\Notification\Application\Service\Scope
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class IndividualScopeSender implements NotificationSenderInterface
{
    public function __construct(
        private NotificationService $notificationService,
        private UserProxy $userProxy
    ) {
    }

    /**
     * @throws ORMException
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function send(SmsNotification|EmailNotification|PushNotification $notification, string $channel): array
    {
        if ($channel === 'EMAIL') {
            return $this->notificationService->sendNotificationEmail(
                $notification
            );
        }

        $usersArray = $this->userProxy->getUsers();
        $usersById = [];
        foreach ($usersArray as $user) {
            $usersById[$user['id']] = $user;
        }

        $users = [];
        foreach ($notification->getScopeTarget() as $key => $userId) {
            $users[$key] = $usersById[$userId] ?? null;
        }

        if (!empty($users)) {
            return $this->notificationService->sendNotification(
                $users,
                $notification,
                $channel
            );
        }

        throw new ORMException('Empty members');
    }
}
