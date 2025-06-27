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
class PushNotification extends Notification
{
    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.channel'])]
    public ?string $channel = 'PUSH';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.topic'])]
    protected string $topic;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.pushTitle'])]
    protected ?string $pushTitle;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.pushContent'])]
    protected string $pushContent;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.pushSubtitle '])]
    protected ?string $pushSubtitle = null;

    public function getTopic(): ?string
    {
        return $this->topic;
    }

    public function setTopic(?string $topic): void
    {
        $this->topic = $topic;
    }

    public function getPushTitle(): string
    {
        return $this->pushTitle;
    }

    public function setPushTitle(string $pushTitle): void
    {
        $this->pushTitle = $pushTitle;
    }

    public function getPushContent(): string
    {
        return $this->pushContent;
    }

    public function setPushContent(string $pushContent): void
    {
        $this->pushContent = $pushContent;
    }

    public function getPushSubtitle(): ?string
    {
        return $this->pushSubtitle;
    }

    public function setPushSubtitle(?string $pushSubtitle): void
    {
        $this->pushSubtitle = $pushSubtitle;
    }
}
