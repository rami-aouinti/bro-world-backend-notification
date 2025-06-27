<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entity;

use App\General\Domain\Entity\Interfaces\EntityInterface;
use App\General\Domain\Entity\Traits\Timestampable;
use App\General\Domain\Entity\Traits\Uuid;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

/**
 * Class MailjetTemplate
 * @package App\Notification\Domain\Entity
 * @author Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[ORM\Entity]
class MailjetTemplate implements EntityInterface, Stringable
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
    #[Groups([
        'MailjetTemplate',
        'MailjetTemplate.id',
    ])]
    private UuidInterface $id;

    #[ORM\Column(type:'integer')]
    #[Groups(['entity:read', 'entity:write'])]
    private int $templateId;

    #[ORM\Column(type:'string', length:255)]
    #[Groups(['entity:read', 'entity:write'])]
    private string $name;

    #[ORM\Column(type:'string', length:10, nullable: true)]
    #[Groups(['entity:read', 'entity:write'])]
    private ?string $locale = '';

    #[ORM\Column(type:'json')]
    #[Groups(['entity:read', 'entity:write'])]
    private array $variables = [];

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
        return $this->getName();
    }

    public function getTemplateId(): int { return $this->templateId; }
    public function setTemplateId(int $templateId): self { $this->templateId = $templateId; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getLocale(): string { return $this->locale; }
    public function setLocale(string $locale): self { $this->locale = $locale; return $this; }
    public function getVariables(): array { return $this->variables; }
    public function setVariables(array $variables): self { $this->variables = $variables; return $this; }
}
