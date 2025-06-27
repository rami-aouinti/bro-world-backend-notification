<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Repository;

use App\Notification\Domain\Entity\MailjetTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MailjetTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method MailjetTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method MailjetTemplate[]    findAll()
 * @method MailjetTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MailjetTemplateRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, MailjetTemplate::class);
    }

    public function save(MailjetTemplate $entity): MailjetTemplate
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        return $entity;
    }

    public function remove(MailjetTemplate $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
    }
}
