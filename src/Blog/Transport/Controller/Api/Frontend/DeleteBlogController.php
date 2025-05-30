<?php

declare(strict_types=1);

namespace App\Blog\Transport\Controller\Api\Frontend;

use App\Blog\Domain\Entity\Blog;
use App\General\Domain\Utils\JSON;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @package App\Blog
 */
#[AsController]
#[OA\Tag(name: 'Blog')]
readonly class DeleteBlogController
{
    public function __construct(
        private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Get current user blog data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @throws JsonException
     */
    #[Route(
        path: '/v1/platform/{blog}/blog',
        methods: [Request::METHOD_DELETE],
    )]
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
    public function __invoke(Blog $blog): JsonResponse
    {
        $this->entityManager->remove($blog);
        $this->entityManager->flush();

        /** @var array<string, string|array<string, string>> $output */
        $output = JSON::decode(
            $this->serializer->serialize(
                'success',
                'json',
                [
                    'groups' => 'Blog',
                ]
            ),
            true,
        );

        return new JsonResponse($output);
    }
}
