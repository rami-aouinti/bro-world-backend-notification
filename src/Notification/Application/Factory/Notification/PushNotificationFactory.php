<?php

declare(strict_types=1);

namespace App\Notification\Application\Factory\Notification;

use App\Notification\Application\Dto\NotificationDto;
use App\Notification\Domain\Entity\Enum\Scope;
use App\Notification\Domain\Entity\PushNotification;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use App\Notification\Domain\Entity\Notification;

/**
 * Class PushNotificationFactory
 *
 * @package App\Notification\Application\Factory\Notification
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AutoconfigureTag('app.notification_factory')]
class PushNotificationFactory implements NotificationFactoryInterface
{
    public function supports(string $channel): bool
    {
        return strtoupper($channel) === 'PUSH';
    }

    /**
     * @throws Exception
     */
    public function createFromDto(NotificationDto $dto, array $paths = []): Notification
    {
        $notification = new PushNotification();
        $notification->setScope(Scope::from($dto->scope));

        if ($notification->getScope()->value === 'INDIVIDUAL' || $notification->getScope()->value === 'WORKPLACE') {
            $notification->setScopeTarget($dto->scopeTarget);
        }

        $notification->setSendAfter($dto->sendAfter);
        $notification->setTopic($dto->topic);
        $notification->setPushTitle($dto->pushTitle);
        $notification->setPushSubtitle($dto->pushSubtitle);
        $notification->setPushContent($dto->pushContent);

        return $notification;
    }
}
