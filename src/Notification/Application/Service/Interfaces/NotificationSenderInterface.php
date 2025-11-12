<?php

declare(strict_types=1);

namespace App\Notification\Application\Service\Interfaces;

use App\Notification\Domain\Entity\EmailNotification;
use App\Notification\Domain\Entity\PushNotification;
use App\Notification\Domain\Entity\SmsNotification;

interface NotificationSenderInterface
{
    public function send(SmsNotification|EmailNotification|PushNotification $notification, string $channel): array;
}
