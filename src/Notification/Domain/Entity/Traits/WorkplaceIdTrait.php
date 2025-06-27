<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 */
trait WorkplaceIdTrait
{
    #[ORM\Column(type: 'uuid', nullable: true)]
    #[Assert\NotNull]
    #[Groups(['default:read'])]
    private ?UuidInterface $workplaceId = null;

    public function getWorkplaceId(): ?UuidInterface
    {
        return $this->workplaceId;
    }

    public function setWorkplaceId(?UuidInterface $workplaceId): void
    {
        $this->workplaceId = $workplaceId;
    }
}
