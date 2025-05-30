<?php

declare(strict_types=1);

namespace App\Blog\Transport\Controller\Api\Frontend;

use App\Blog\Application\Pagination\Paginator;
use App\Blog\Domain\Entity\Blog;
use App\Blog\Domain\Entity\Tag;
use App\Blog\Domain\Repository\Interfaces\BlogRepositoryInterface;
use App\Blog\Domain\Repository\Interfaces\PostRepositoryInterface;
use App\Blog\Infrastructure\Repository\PostRepository;
use App\Blog\Infrastructure\Repository\TagRepository;
use App\General\Domain\Utils\JSON;
use App\General\Infrastructure\ValueObject\SymfonyUser;
use Closure;
use Doctrine\ORM\Exception\NotSupported;
use Exception;
use JsonException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Throwable;
use Traversable;

/**
 * @package App\Blog
 */
#[AsController]
#[OA\Tag(name: 'Blog')]
readonly class GetPostsController
{
    public function __construct(
        private SerializerInterface $serializer,
        private PostRepositoryInterface $repository,
        private TagRepository $tags,
        private BlogRepositoryInterface $blogRepository,
        private CacheInterface $cache
    ) {
    }

    /**
     * Get current user blog data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param SymfonyUser $symfonyUser
     * @param Request     $request
     * @param int|null    $page
     * @param string      $_format
     *
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws NotSupported
     * @return JsonResponse
     */
    #[Route(path: '/v1/platform/posts', name: 'posts_index', defaults: ['page' => '1'], methods: [Request::METHOD_GET])]
    #[Route('/v1/platform/posts/page/{page}', name: 'post_index_paginated', requirements: ['page' => Requirement::POSITIVE_INT], methods: [Request::METHOD_GET])]
    #[Cache(smaxage: 10)]
    #[OA\Response(
        response: 200,
        description: 'Blog data',
        content: new JsonContent(
            ref: new Model(
                type: Blog::class,
                groups: ['Blog'],
            ),
            type: 'object',
        ),
    )]
    #[OA\Response(
        response: 401,
        description: 'Invalid token (not found or expired)',
        content: new JsonContent(
            properties: [
                new Property(property: 'code', description: 'Error code', type: 'integer'),
                new Property(property: 'message', description: 'Error description', type: 'string'),
            ],
            type: 'object',
            example: [
                'code' => 401,
                'message' => 'JWT Token not found',
            ],
        ),
    )]
    #[OA\Response(
        response: 403,
        description: 'Access denied',
        content: new JsonContent(
            properties: [
                new Property(property: 'code', description: 'Error code', type: 'integer'),
                new Property(property: 'message', description: 'Error description', type: 'string'),
            ],
            type: 'object',
            example: [
                'code' => 403,
                'message' => 'Access denied',
            ],
        ),
    )]
    public function __invoke(
        SymfonyUser $symfonyUser,
        Request $request,
        ?int $page,
        string $_format
    ): JsonResponse
    {

        $tag = null;

        if ($request->query->has('tag')) {
            $tag = $this->tags->findOneBy(['name' => $request->query->get('tag')]);
        }
        if(!$page) {
            $page= 1;
        }
        $blog = $this->blogRepository->findOneBy([
            'title' => 'public'
        ]);
        // Generate a unique cache key based on the page and tag (if any)
        $cacheKey = 'application_posts_page_' . $blog->getId() . $symfonyUser->getUserIdentifier() .  $page . ($tag ? '_tag_' . $tag->getName() : '');

        // Try to get the posts from the cache
        $posts = $this->cache->get($cacheKey, $this->getClosure($page, $tag, $blog));

        /** @var array<string, string|array<string, string>> $output */
        $output = JSON::decode(
            $this->serializer->serialize(
                $posts,
                'json',
                [
                    'groups' => 'Post',
                ]
            ),
            true,
        );

        return new JsonResponse($output);
    }

    /**
     * @param          $page
     * @param Tag|null $tag
     * @param Blog     $blog
     *
     * @return Closure
     */
    private function getClosure($page,?Tag $tag, Blog $blog): Closure
    {
        return function (ItemInterface $item) use ($page, $tag, $blog): Traversable {

            $item->expiresAfter(3600);

            return $this->getFormattedPosts($page, $tag, $blog)->getResults();
        };
    }

    /**
     * @throws Exception
     */
    private function getFormattedPosts($page,?Tag $tag, Blog $blog): Paginator
    {
        return $this->repository->findLatestByBlog($page, $tag, $blog);
    }
}
