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
readonly class GetBlogController
{
    public function __construct(
        private SerializerInterface $serializer,
        private BlogRepositoryInterface $repository,
        private CacheInterface $cache,
        private HttpClientInterface $httpClient
    ) {
    }

    /**
     * Get current user blog data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param SymfonyUser $symfonyUser
     * @param Request     $request
     * @param Blog        $blog
     *
     * @throws InvalidArgumentException
     * @return JsonResponse
     */
    #[Route(path: '/v1/platform/blog/{blog}/blog', name: 'blog_show', methods: [Request::METHOD_GET])]
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
    public function __invoke(SymfonyUser $symfonyUser, Request $request, Blog $blog): JsonResponse
    {
        $cacheKey = 'blog_' . $blog->getId() . $symfonyUser->getUserIdentifier();
        $blogs = $this->cache->get($cacheKey, fn (ItemInterface $item) => $this->getClosure($request, $blog)($item));

        return new JsonResponse($blogs);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function getMedia(Request $request, string $media): string
    {
        $mediaResponse = $this->httpClient->request('GET', "http://media.bro-world.org/api/v1/platform/media/$media", [
            'headers' => ['Authorization' => $request->headers->get('Authorization')],
        ]);

        $mediaData = $mediaResponse->toArray();
        return 'http://media.bro-world.org/uploads/' . $mediaData['path'];
    }

    /**
     * @param             $request
     * @param Blog        $blog
     *
     * @return Closure
     */
    private function getClosure($request, Blog $blog): Closure
    {
        return function (ItemInterface $item) use ( $request, $blog): array {
            $item->expiresAfter(3600);

            return $this->getFormattedPosts($request, $blog);
        };
    }

    /**
     * @throws Exception
     */
    private function getFormattedPosts(Request $request, Blog $blog): array
    {
        return $this->getBlog($request, $blog);
    }

    /**
     * @param Request $request
     * @param Blog    $blog
     *
     * @return array
     */
    private function getBlog(Request $request, Blog $blog): array
    {
        return [
            'id' => $blog->getId(),
            'title' => $blog->getTitle(),
            'subTitle' => $blog->getBlogSubtitle(),
            'color' => $blog->getColor(),
            'author' => $blog->getAuthor(),
            'publishedAt' => $blog->getCreatedAt(),
            'updatedAt' => $blog->getUpdatedAt(),
            'logo' => $this->safeGetMedia($request, $blog->getLogo()?->toString())
        ];
    }

    private function safeGetMedia(Request $request, ?string $media): ?string
    {
        if (!$media) {
            return null;
        }

        try {
            return $this->getMedia($request, $media);
        } catch (Exception $e) {
            return null;
        }
    }
}
