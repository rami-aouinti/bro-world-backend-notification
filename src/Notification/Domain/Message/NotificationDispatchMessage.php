<?php

declare(strict_types=1);

namespace App\Notification\Domain\Message;

use Bro\WorldCoreBundle\Domain\Message\Interfaces\MessageHighInterface;

/**
 * @package App\Notification\Domain\Message
 */
final class NotificationDispatchMessage implements MessageHighInterface
{
    public function __construct(
        private readonly string $notificationId,
        private readonly string $channel
    ) {
    }

    public function getNotificationId(): string
    {
        return $this->notificationId;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }
}
