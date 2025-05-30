<?php

declare(strict_types=1);

namespace App\Blog\Transport\Controller\Api\Frontend;

use App\Blog\Domain\Entity\Blog;
use App\Blog\Domain\Entity\Comment;
use App\Blog\Domain\Entity\Post;
use App\General\Domain\Utils\JSON;
use App\General\Infrastructure\ValueObject\SymfonyUser;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @package App\Blog
 */
#[AsController]
#[OA\Tag(name: 'Blog')]
readonly class CreateCommentController
{
    public function __construct(
        private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Get current user blog data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param SymfonyUser $symfonyUser
     * @param Request     $request
     * @param Post        $post
     *
     * @throws JsonException
     * @return JsonResponse
     */
    #[Route(path: '/v1/platform/{post}/comment', name: 'comment_create', methods: [Request::METHOD_POST])]
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
    public function __invoke(SymfonyUser $symfonyUser, Request $request, Post $post): JsonResponse
    {
        $comment = new Comment();
        $comment->setPost($post);
        $comment->setAuthor(Uuid::fromString($symfonyUser->getUserIdentifier()));
        $comment->setContent($request->request->get('content'));

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        /** @var array<string, string|array<string, string>> $output */
        $output = JSON::decode(
            $this->serializer->serialize(
                $comment,
                'json',
                [
                    'groups' => 'Comment',
                ]
            ),
            true,
        );

        return new JsonResponse($output);
    }
}
