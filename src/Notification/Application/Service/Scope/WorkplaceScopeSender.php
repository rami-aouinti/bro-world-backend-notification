<?php

declare(strict_types=1);

namespace App\Notification\Application\Service\Scope;

use App\Notification\Application\Service\Interfaces\NotificationSenderInterface;
use App\Notification\Application\Service\NotificationService;
use App\Notification\Domain\Entity\EmailNotification;
use App\Notification\Domain\Entity\PushNotification;
use App\Notification\Domain\Entity\SmsNotification;
use Doctrine\ORM\Exception\ORMException;

/**
 * @package App\Notification\Application\Service\Scope
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class WorkplaceScopeSender implements NotificationSenderInterface
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    /**
     * @throws ORMException
     */
    public function send(SmsNotification|EmailNotification|PushNotification $notification, string $channel): array
    {
        if ($channel === 'EMAIL') {
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

        throw new ORMException('Empty members');
    }
}
