<?php

declare(strict_types=1);

namespace App\Notification\Application\Factory\Notification;

use App\Notification\Application\Dto\NotificationDto;
use App\Notification\Domain\Entity\Notification;
use InvalidArgumentException;
use Traversable;

/**
 * @package App\Notification\Application\Factory\Notification
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
class NotificationFactoryResolver
{
    /**
     * @var NotificationFactoryInterface[]
     */
    private array $factories;

    public function __construct(iterable $factories)
    {
        $this->factories = $factories instanceof Traversable ? iterator_to_array($factories) : $factories;
    }

    public function createNotification(NotificationDto $dto, string $channel, array $paths = []): Notification
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($channel)) {
                return $factory->createFromDto($dto, $paths);
            }
        }

        throw new InvalidArgumentException("No factory found for channel: {$channel}");
    }
}
