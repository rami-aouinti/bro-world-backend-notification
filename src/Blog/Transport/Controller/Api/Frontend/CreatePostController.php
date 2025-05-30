<?php

declare(strict_types=1);

namespace App\Blog\Transport\Controller\Api\Frontend;

use App\Blog\Application\Service\PostService;
use App\Blog\Domain\Entity\Blog;
use App\General\Infrastructure\ValueObject\SymfonyUser;
use JsonException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * @package App\Blog
 */
#[AsController]
#[OA\Tag(name: 'Blog')]
readonly class CreatePostController
{
    public function __construct(
        private PostService $postService
    ) {
    }

    /**
     * Get current user blog data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param SymfonyUser $symfonyUser
     * @param Request     $request
     *
     * @throws JsonException
     * @throws Throwable
     * @return JsonResponse
     */
    #[Route(path: '/v1/platform/post', name: 'post_create', methods: [Request::METHOD_POST])]
    #[OA\Response(
        response: 200,
        description: 'Post data',
        content: new JsonContent(
            ref: new Model(
                type: Blog::class,
                groups: ['Post'],
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
        return new JsonResponse(
            $this->postService->createPost(
                $symfonyUser,
                $request
            )
        );
    }
}
