<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entity;

use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\General\Domain\Entity\Traits\Timestampable;
use App\General\Domain\Entity\Traits\Uuid;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Stringable;
use Symfony\Component\Serializer\Attribute\Groups;
use App\Notification\Domain\Entity\Enum\Scope;

use Throwable;

use function is_string;

/**
 * Class Notification
 * @package App\Notification\Domain\Entity
 * @author Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'channel', type: 'string')]
#[ORM\DiscriminatorMap([
    'EMAIL' => EmailNotification::class,
    'SMS' => SmsNotification::class,
    'PUSH' => PushNotification::class,
])]
#[ORM\HasLifecycleCallbacks]
class Notification implements EntityInterface, Stringable
{
    use Timestampable;
    use Uuid;

    #[ORM\Id]
    #[ORM\Column(
        name: 'id',
        type: UuidBinaryOrderedTimeType::NAME,
        unique: true,
        nullable: false,
    )]
    #[\Symfony\Component\Serializer\Annotation\Groups([
        'Notification',
        'Notification.id',
    ])]
    private UuidInterface $id;

    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.channel'])]
    public ?string $channel = null;

    #[ORM\Column(type: 'string', enumType: Scope::class)]
    #[Groups(['entity:read', 'entity:write', 'Notification', 'Notification.scope'])]
    protected Scope $scope;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['entity:read', 'entity:write', 'Notification', 'Notification.scopeTarget'])]
    protected ?array $scopeTarget = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['entity:write', 'entity:read', 'Notification', 'Notification.status'])]
    protected ?string $status = 'pending';

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['entity:read', 'entity:write', 'Notification', 'Notification.sendAfter'])]
    protected ?DateTimeInterface $sendAfter = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['entity:read', 'entity:write', 'Notification', 'Notification.completedAt'])]
    protected ?DateTimeInterface $completedAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['entity:read', 'entity:write', 'Notification', 'Notification.callback'])]
    protected ?array $callback;

    /**
     * @throws Throwable
     */
    public function __construct()
    {
        $this->id = $this->createUuid();
    }

    public function getId(): string
    {
        return $this->id->toString();
    }

    public function __toString(): string
    {
        return $this->getId();
    }

    public function getCallback(): ?array
    {
        return $this->callback;
    }

    public function getScope(): Scope
    {
        return $this->scope;
    }

    public function setScope(Scope $scope): void
    {
        $this->scope = $scope;
    }

    public function getScopeTarget(): ?array
    {
        return $this->scopeTarget;
    }

    public function setScopeTarget(?array $scopeTarget): void
    {
        $this->scopeTarget = $scopeTarget;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getSendAfter(): ?DateTimeInterface
    {
        return $this->sendAfter;
    }

    /**
     * @throws Exception
     */
    public function setSendAfter($sendAfter): void
    {
        if (is_string($sendAfter)) {
            $this->sendAfter = new DateTime($sendAfter);
        } else {
            $this->sendAfter = $sendAfter;
        }
    }

    public function setCompletedAt(?DateTimeInterface $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    public function getCompletedAt(): ?DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCallback(?array $callback): void
    {
        $this->callback = $callback;
    }
}
