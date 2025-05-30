<?php

declare(strict_types=1);

namespace App\Blog\Application\Service;

use App\Blog\Domain\Entity\Blog;
use App\Blog\Domain\Repository\Interfaces\BlogRepositoryInterface;
use App\General\Infrastructure\ValueObject\SymfonyUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
/**
 * Class PostService
 *
 * @package App\Blog\Application\Service
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class BlogService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BlogRepositoryInterface $blogRepository
    ) {}

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws TransactionRequiredException
     * @throws NotSupported
     */
    public function getBlog(Request $request,SymfonyUser $symfonyUser): Blog
    {
        $response = $request->request->all();

        if(isset($response['blog'])) {
            $blogObject = $this->blogRepository->find($response['blog']);
        } else {
            $blogObject = $this->blogRepository->findOneBy([
                'title' => 'public',
            ]);

            if(!$blogObject) {
                $blogObject = new Blog();
                $blogObject->setTitle('public');
                $blogObject->setBlogSubtitle('General posts');
                $blogObject->setSlug('public');
                $blogObject->setAuthor(Uuid::fromString($symfonyUser->getUserIdentifier()));
                $blogObject->setColor('primary');
                $this->entityManager->persist($blogObject);
                $this->entityManager->flush();
            }
        }

        return $blogObject;
    }
}
