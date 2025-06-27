<?php

declare(strict_types=1);

namespace App\Notification\Application\Service\Scope;

use Doctrine\ORM\Exception\ORMException;
use App\Notification\Domain\Entity\PushNotification;
use App\Notification\Domain\Entity\EmailNotification;
use App\Notification\Domain\Entity\SmsNotification;
use App\Notification\Application\Service\Interfaces\NotificationSenderInterface;
use App\Notification\Application\Service\NotificationService;

/**
 * @package App\Notification\Application\Service\Scope
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class SegmentScopeSender implements NotificationSenderInterface
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    /**
     * @param SmsNotification|EmailNotification|PushNotification $notification
     * @param string $channel
     * @return array
     * @throws ORMException
     */
    public function send(SmsNotification|EmailNotification|PushNotification $notification, string $channel): array
    {
        if($channel === 'EMAIL') {
            return $this->notificationService->sendNotificationEmail(
                $notification
            );
        }

        throw new ORMException("Empty members");
    }
}
