<?php

declare(strict_types=1);

namespace App\Blog\Transport\Controller\Api\Frontend;

use App\Blog\Domain\Entity\Blog;
use App\Blog\Domain\Repository\Interfaces\BlogRepositoryInterface;
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
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @package App\Blog
 */
#[AsController]
#[OA\Tag(name: 'Blog')]
readonly class GetBlogsController
{
    public function __construct(
        private BlogRepositoryInterface $repository,
        private CacheInterface $cache
    ) {
    }

    /**
     * Get current user blog data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param SymfonyUser $symfonyUser
     * @param Request     $request
     *
     * @throws InvalidArgumentException
     * @return JsonResponse
     */
    #[Route(path: '/public/v1/platform/blog', name: 'blog_index', methods: [Request::METHOD_GET])]
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
    public function __invoke(SymfonyUser $symfonyUser, Request $request): JsonResponse
    {
        $cacheKey = 'blog_user_' . $symfonyUser->getUserIdentifier() . '_public';
        $blogs = $this->cache->get($cacheKey, fn (ItemInterface $item) => $this->getClosure($symfonyUser, $request)($item));

        return new JsonResponse($blogs);
    }

    /**
     * @param SymfonyUser $symfonyUser
     * @param             $request
     *
     * @return Closure
     */
    private function getClosure(SymfonyUser $symfonyUser, $request): Closure
    {
        return function (ItemInterface $item) use ($symfonyUser, $request): array {
            $item->expiresAfter(3600);

            return $this->getFormattedPosts($symfonyUser, $request);
        };
    }

    /**
     * @param SymfonyUser $symfonyUser
     * @param Request     $request
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws NotSupported
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @return array
     */
    private function getFormattedPosts(SymfonyUser $symfonyUser,Request $request): array
    {
        $blogs = $this->repository->findBy([
            'author' => $symfonyUser->getUserIdentifier()
        ]);
        $filteredBlogs = array_filter($blogs, function($blog) {
            return $blog->getTitle() !== 'public';
        });
        return $this->getBlogs($request, $filteredBlogs);
    }

    /**
     * @param Request $request
     * @param array   $blogs
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @return array
     */
    private function getBlogs(Request $request, array $blogs): array
    {
        return array_map(function ($blog) use ($request) {
            return [
                'id' => $blog->getId(),
                'title' => $blog->getTitle(),
                'subTitle' => $blog->getBlogSubtitle(),
                'color' => $blog->getColor(),
                'author' => $blog->getAuthor(),
                'publishedAt' => $blog->getCreatedAt(),
                'updatedAt' => $blog->getUpdatedAt(),
                'logo' => $blog->getLogo()->toString()
            ];
        }, $blogs);
    }
}
