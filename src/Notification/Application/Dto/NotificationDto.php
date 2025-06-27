<?php

declare(strict_types=1);

namespace App\Notification\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class NotificationDto
 * @package App\Dto
 * @author Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
class NotificationDto
{
    #[Assert\NotBlank]
    public string  $channel;
    public ?string $status = null;
    public string  $scope;
    public ?array  $scopeTarget = null;
    public ?string $topic = null;
    public ?string $pushTitle = null;
    public ?string $pushSubtitle = null;
    public ?string $pushContent = null;
    public ?string $smsSenderName = null;
    public ?string $smsContent = null;
    public ?int    $templateId = 0;
    public ?array  $recipients = null;
    public ?string $emailSenderName = null;
    public ?string $emailSenderEmail = null;
    public ?array  $emailRecipientsBcc = null;
    public ?array  $emailRecipientsCc = null;
    public ?array  $emailRecipientsReplyTo = null;
    public ?string $emailSubject = null;
    public ?string $emailContentHtml = null;
    public ?string $emailContentPlain = null;
    public ?string $sendAfter  = null;
}
