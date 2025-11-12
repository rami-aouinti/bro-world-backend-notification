<?php

declare(strict_types=1);

namespace App\Notification\Application\Factory\Notification;

use App\Notification\Application\Dto\NotificationDto;
use App\Notification\Domain\Entity\Enum\Scope;
use App\Notification\Domain\Entity\Notification;
use App\Notification\Domain\Entity\SmsNotification;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @package App\Notification\Application\Factory\Notification
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AutoconfigureTag('app.notification_factory')]
class SmsNotificationFactory implements NotificationFactoryInterface
{
    public function supports(string $channel): bool
    {
        return strtoupper($channel) === 'SMS';
    }

    /**
     * @throws Exception
     */
    public function createFromDto(NotificationDto $dto, array $paths = []): Notification
    {
        $notification = new SmsNotification();
        $notification->setScope(Scope::from($dto->scope));

        if ($notification->getScope()->value === 'INDIVIDUAL' || $notification->getScope()->value === 'WORKPLACE') {
            $notification->setScopeTarget($dto->scopeTarget);
        }

        $notification->setSendAfter($dto->sendAfter);
        $notification->setSmsSenderName($dto->smsSenderName);
        $notification->setSmsContent($dto->smsContent);

        return $notification;
    }
}
