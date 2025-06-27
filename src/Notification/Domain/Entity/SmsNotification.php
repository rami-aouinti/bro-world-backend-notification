<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @package App\Notification\Domain\Entity
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[ORM\Entity]
class SmsNotification extends Notification
{
    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.channel'])]
    public ?string $channel = 'SMS';

    #[ORM\Column(type: 'string', length: 11)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(max: 11)]
    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.smsSenderName'])]
    protected string $smsSenderName;

    #[ORM\Column(type: 'string', length: 320)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(max: 320)]
    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.smsContent'])]
    protected string $smsContent;

    public function getSmsSenderName(): string
    {
        return $this->smsSenderName;
    }

    public function setSmsSenderName(string $smsSenderName): void
    {
        $this->smsSenderName = $smsSenderName;
    }

    public function getSmsContent(): string
    {
        return $this->smsContent;
    }

    public function setSmsContent(string $smsContent): void
    {
        $this->smsContent = $smsContent;
    }
}
