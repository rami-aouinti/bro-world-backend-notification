<?php

declare(strict_types=1);

namespace App\Blog\Infrastructure\Repository;

use App\Blog\Application\Pagination\Paginator;
use App\Blog\Domain\Entity\Blog;
use App\Blog\Domain\Entity\Post as Entity;
use App\Blog\Domain\Entity\Tag;
use App\Blog\Domain\Repository\Interfaces\PostRepositoryInterface;
use App\General\Infrastructure\Repository\BaseRepository;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;

use function count;
use function Symfony\Component\String\u;

/**
 * @package App\Blog
 *
 * @psalm-suppress LessSpecificImplementedReturnType
 * @codingStandardsIgnoreStart
 *
 * @method Entity|null find(string $id, ?int $lockMode = null, ?int $lockVersion = null, ?string $entityManagerName = null)
 * @method Entity|null findAdvanced(string $id, string | int | null $hydrationMode = null, string|null $entityManagerName = null)
 * @method Entity|null findOneBy(array $criteria, ?array $orderBy = null, ?string $entityManagerName = null)
 * @method Entity[] findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?string $entityManagerName = null)
 * @method Entity[] findByAdvanced(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?array $search = null, ?string $entityManagerName = null)
 * @method Entity[] findAll(?string $entityManagerName = null)
 *
 * @codingStandardsIgnoreEnd
 */
class PostRepository extends BaseRepository implements PostRepositoryInterface
{
    /**
     * @var array<int, string>
     */
    protected static array $searchColumns = ['title', 'content', 'summary'];

    /**
     * @psalm-var class-string
     */
    protected static string $entityName = Entity::class;

    public function __construct(
        protected ManagerRegistry $managerRegistry
    ) {
    }

    /**
     * @throws Exception
     */
    public function findLatest(int $page = 1, ?Tag $tag = null): Paginator
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('t', 'l')
            ->leftJoin('p.tags', 't')
            ->leftJoin('p.likes', 'l')
            ->where('p.publishedAt <= :now')
            ->orderBy('p.publishedAt', 'DESC')
            ->setParameter('now', new DateTimeImmutable());

        if (null !== $tag) {
            $qb->andWhere(':tag MEMBER OF p.tags')
                ->setParameter('tag', $tag);
        }

        return (new Paginator($qb))->paginate($page);
    }

    /**
     * @throws Exception
     */
    public function findLatestByBlog(int $page, ?Tag $tag, Blog $blog): Paginator
    {

        $qb = $this->createQueryBuilder('p')
            ->addSelect('t', 'l', 'b')
            ->leftJoin('p.tags', 't')
            ->leftJoin('p.likes', 'l')
            ->leftJoin('p.blog', 'b')
            ->where('p.publishedAt <= :now')
            ->andWhere('b.slug = :blog')
            ->setParameter('blog', $blog->getSlug())
            ->orderBy('p.publishedAt', 'DESC')
            ->setParameter('now', new DateTimeImmutable());

        if (null !== $tag) {
            $qb->andWhere(':tag MEMBER OF p.tags')
                ->setParameter('tag', $tag);
        }

        return (new Paginator($qb))->paginate($page);
    }

    /**
     * @return Entity[]
     */
    public function findBySearchQuery(string $query, int $limit = Paginator::PAGE_SIZE): array
    {
        $searchTerms = $this->extractSearchTerms($query);

        if (0 === count($searchTerms)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('p');

        foreach ($searchTerms as $key => $term) {
            $queryBuilder
                ->orWhere('p.title LIKE :t_'.$key)
                ->setParameter('t_'.$key, '%'.$term.'%')
            ;
        }

        /** @var Entity[] $result */
        $result = $queryBuilder
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    /**
     * Transforms the search string into an array of search terms.
     *
     * @return string[]
     */
    private function extractSearchTerms(string $searchQuery): array
    {
        $terms = array_unique(u($searchQuery)->replaceMatches('/[[:space:]]+/', ' ')->trim()->split(' '));

        // ignore the search terms that are too short
        return array_filter($terms, static function ($term) {
            return 2 <= $term->length();
        });
    }
}
