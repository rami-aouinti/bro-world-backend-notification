<?php

declare(strict_types=1);

namespace App\Notification\Transport\MessageHandler;

use App\Notification\Application\Service\NotificationManager;
use App\Notification\Domain\Message\NotificationDispatchMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @package App\Notification\Transport\MessageHandler
 */
#[AsMessageHandler(bus: 'command_bus')]
final class NotificationDispatchMessageHandler
{
    public function __construct(private readonly NotificationManager $notificationManager)
    {
    }

    public function __invoke(NotificationDispatchMessage $message): void
    {
        $this->notificationManager->dispatch($message->getNotificationId(), $message->getChannel());
    }
}
