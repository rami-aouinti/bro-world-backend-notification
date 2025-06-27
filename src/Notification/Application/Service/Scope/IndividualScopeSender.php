<?php

declare(strict_types=1);

namespace App\Notification\Application\Service\Scope;

use Doctrine\ORM\Exception\ORMException;
use App\Notification\Application\Service\Interfaces\NotificationSenderInterface;
use App\Notification\Application\Service\NotificationService;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use App\Notification\Domain\Entity\PushNotification;
use App\Notification\Domain\Entity\EmailNotification;
use App\Notification\Domain\Entity\SmsNotification;

/**
 * @package App\Notification\Application\Service\Scope
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class IndividualScopeSender implements NotificationSenderInterface
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    /**
     * @param SmsNotification|EmailNotification|PushNotification $notification
     * @param string                                             $channel
     *
     * @throws ORMException
     * @return array
     */
    public function send(SmsNotification|EmailNotification|PushNotification $notification, string $channel): array
    {
        if($channel === 'EMAIL') {
            return $this->notificationService->sendNotificationEmail(
                $notification
            );
        }

        $users = $this->notificationService->fetchAllMembers($notification->getScopeTarget(), $notification->getScope(), $channel);
        if (!empty($users)) {
            return $this->notificationService->sendNotification(
                $users,
                $notification,
                $channel
            );
        }

        throw new ORMException("Empty members");
    }
}
