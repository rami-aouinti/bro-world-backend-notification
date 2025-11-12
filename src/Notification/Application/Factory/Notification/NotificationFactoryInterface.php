<?php

declare(strict_types=1);

namespace App\Notification\Application\Factory\Notification;

use App\Notification\Application\Dto\NotificationDto;
use App\Notification\Domain\Entity\Notification;

interface NotificationFactoryInterface
{
    public function supports(string $channel): bool;
    public function createFromDto(NotificationDto $dto, array $paths = []): Notification;
}
