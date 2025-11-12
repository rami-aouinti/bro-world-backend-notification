<?php

declare(strict_types=1);

namespace App\Notification\Application\Factory\Notification;

use App\Notification\Application\Dto\NotificationDto;
use App\Notification\Domain\Entity\EmailNotification;
use App\Notification\Domain\Entity\Enum\Scope;
use App\Notification\Domain\Entity\Notification;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @package App\Notification\Application\Factory\Notification
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AutoconfigureTag('app.notification_factory')]
class EmailNotificationFactory implements NotificationFactoryInterface
{
    public function supports(string $channel): bool
    {
        return strtoupper($channel) === 'EMAIL';
    }

    /**
     * @throws Exception
     */
    public function createFromDto(NotificationDto $dto, array $paths = []): Notification
    {
        $notification = new EmailNotification();
        $notification->setScope(Scope::from($dto->scope));

        if ($notification->getScope()->value === 'INDIVIDUAL' || $notification->getScope()->value === 'WORKPLACE') {
            $notification->setScopeTarget($dto->scopeTarget);
        }

        $notification->setSendAfter($dto->sendAfter);
        $notification->setEmailSenderName($dto->emailSenderName);
        $notification->setEmailSenderEmail($dto->emailSenderEmail);
        $notification->setEmailSubject($dto->emailSubject);
        $notification->setEmailContentHtml($dto->emailContentHtml);
        $notification->setEmailContentPlain($dto->emailContentPlain);
        $notification->setTemplateId($dto->templateId);
        $notification->setRecipients($dto->recipients);
        $notification->setEmailRecipientsCc($dto->emailRecipientsCc);
        $notification->setEmailRecipientsBcc($dto->emailRecipientsBcc);
        $notification->setEmailRecipientsReplyTo($dto->emailRecipientsReplyTo);

        if (!empty($paths)) {
            $notification->setBinaryAttachments($paths);
        }

        return $notification;
    }
}
