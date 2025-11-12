<?php

/** @noinspection PhpUnused */

namespace App\Notification\Domain\Entity\Embeddable;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @package App\Notification\Domain\Entity\Embeddable
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[ORM\Embeddable]
class Callback
{
    #[Assert\Type('string')]
    #[Assert\Length(max: 15)]
    #[Assert\Choice(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])]
    #[Groups(['callback:write'])]
    #[ORM\Column(length: 15, nullable: true)]
    private string $method = 'POST';
    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    #[Groups(['callback:write'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;
    #[Assert\Type('array')]
    #[Assert\All([
        new Assert\NotNull(),
    ])]
    #[Groups(['callback:write'])]
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $params = null;
    #[Assert\Type('array')]
    #[Assert\All([
        new Assert\NotBlank(),
        new Assert\Type('string'),
    ])]
    #[Groups(['callback:write'])]
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $headers = null;
    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getParams(): ?array
    {
        return $this->params;
    }

    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    public function setMethod(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function setParams(?array $params): static
    {
        $this->params = $params;

        return $this;
    }

    public function setHeaders(?array $headers): static
    {
        $this->headers = $headers;

        return $this;
    }
}
