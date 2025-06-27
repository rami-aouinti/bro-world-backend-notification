<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class EmailNotification
 * @package App\Notification\Domain\Entity
 * @author Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[ORM\Entity]
#[Assert\Expression(
    expression: "this.templateId != 0 or (this.emailContentHtml != null and this.emailContentPlain != null)",
    message: "Either templateId must be set or both htmlContent and textContent must be provided."
)]
class EmailNotification extends Notification
{
    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.channel'])]
    public ?string $channel = 'EMAIL';

    #[ORM\Column(type: 'string')]
    #[Groups(['entity:read', 'entity:write', 'Notification', 'Notification.emailSenderName'])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    protected string $emailSenderName;

    #[ORM\Column(type: 'string')]
    #[Groups(['entity:read', 'entity:write', 'Notification', 'Notification.emailSenderEmail'])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    protected string $emailSenderEmail;

    #[ORM\Column(type: 'string')]
    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.emailSubject'])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    protected string $emailSubject;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.emailContentPlain'])]
    public ?string $emailContentPlain = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.emailContentHtml'])]
    public ?string $emailContentHtml = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['entity:write', 'entity:templateId'])]
    public ?int $templateId = 0;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.emailRecipientsCc'])]
    protected ?array $emailRecipientsCc = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.emailRecipientsBcc'])]
    protected ?array $emailRecipientsBcc = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.emailRecipientsReplyTo'])]
    protected ?array $emailRecipientsReplyTo = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.mediaIdsAttachments'])]
    protected ?array $mediaIdsAttachments = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.binaryAttachments'])]
    protected ?array $binaryAttachments = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.recipients'])]
    protected ?array $recipients = null;

    public function getEmailSenderName(): string { return $this->emailSenderName; }
    public function setEmailSenderName(string $emailSenderName): void { $this->emailSenderName = $emailSenderName; }
    public function getEmailSenderEmail(): string { return $this->emailSenderEmail; }
    public function setEmailSenderEmail(string $emailSenderEmail): void { $this->emailSenderEmail = $emailSenderEmail; }
    public function getEmailSubject(): string { return $this->emailSubject; }
    public function setEmailSubject(string $emailSubject): self { $this->emailSubject = $emailSubject; return $this; }
    public function getEmailContentPlain(): ?string { return $this->emailContentPlain; }
    public function setEmailContentPlain(?string $emailContentPlain): self { $this->emailContentPlain = $emailContentPlain; return $this; }
    public function getEmailContentHtml(): ?string { return $this->emailContentHtml; }
    public function setEmailContentHtml(?string $emailContentHtml): void { $this->emailContentHtml = $emailContentHtml; }
    public function getEmailRecipientsCc(): ?array { return $this->emailRecipientsCc; }
    public function setEmailRecipientsCc(?array $emailRecipientsCc): void { $this->emailRecipientsCc = $emailRecipientsCc; }
    public function getEmailRecipientsBcc(): ?array { return $this->emailRecipientsBcc; }
    public function setEmailRecipientsBcc(?array $emailRecipientsBcc): void { $this->emailRecipientsBcc = $emailRecipientsBcc; }
    public function getEmailRecipientsReplyTo(): ?array { return $this->emailRecipientsReplyTo; }
    public function setEmailRecipientsReplyTo(?array $emailRecipientsReplyTo): void { $this->emailRecipientsReplyTo = $emailRecipientsReplyTo; }
    public function getMediaIdsAttachments(): ?array { return $this->mediaIdsAttachments; }
    public function setMediaIdsAttachments(?array $mediaIdsAttachments): void { $this->mediaIdsAttachments = $mediaIdsAttachments; }
    public function getBinaryAttachments(): ?array { return $this->binaryAttachments; }
    public function setBinaryAttachments(?array $binaryAttachments): void { $this->binaryAttachments = $binaryAttachments; }
    public function getTemplateId(): ?int { return $this->templateId;}
    public function setTemplateId(?int $templateId): void { $this->templateId = $templateId; }
    public function getRecipients(): ?array { return $this->recipients; }
    public function setRecipients(?array $recipients): void { $this->recipients = $recipients; }

    public function toArray(): array
    {
        return [
            'emailSenderName' => $this->emailSenderName,
            'emailSenderEmail' => $this->emailSenderEmail,
            'emailSubject' => $this->emailSubject,
            'emailContentPlain' => $this->emailContentPlain,
            'emailContentHtml' => $this->emailContentHtml,
            'templateId' => $this->templateId,
            'emailRecipientsCc' => $this->emailRecipientsCc,
            'emailRecipientsBcc' => $this->emailRecipientsBcc,
            'emailRecipientsReplyTo' => $this->emailRecipientsReplyTo,
            'mediaIdsAttachments' => $this->mediaIdsAttachments,
            'paths' => $this->binaryAttachments,
            'recipients' => $this->recipients,
        ];
    }
}
