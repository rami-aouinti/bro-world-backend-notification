<?php

declare(strict_types=1);

namespace App\Blog\Transport\Controller\Api\Frontend;

use App\Blog\Domain\Entity\Blog;
use App\Blog\Domain\Entity\Comment;
use App\Blog\Domain\Entity\Like;
use App\Blog\Domain\Entity\Post;
use App\Blog\Domain\Repository\Interfaces\LikeRepositoryInterface;
use App\General\Domain\Utils\JSON;
use App\General\Infrastructure\ValueObject\SymfonyUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\NotSupported;
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
readonly class LikeCommentController
{
    public function __construct(
        private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager,
        private LikeRepositoryInterface $likeRepository
    ) {
    }

    /**
     * Get current user blog data, accessible only for 'IS_AUTHENTICATED_FULLY' users.
     *
     * @param SymfonyUser $symfonyUser
     * @param Comment     $comment
     *
     * @throws JsonException
     * @throws NotSupported
     * @return JsonResponse
     */
    #[Route(path: '/v1/platform/{comment}/comment/like', name: 'comment_like', methods: [Request::METHOD_GET])]
    #[Cache(smaxage: 10)]
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
    public function __invoke(SymfonyUser $symfonyUser, Comment $comment): JsonResponse
    {
        $like = $this->likeRepository->findOneBy([
            'comment' => $comment,
            'user' => $symfonyUser->getUserIdentifier()
        ]);

        if(!$like) {
            $like = new Like();
            $like->setComment($comment);
            $like->setUser(Uuid::fromString($symfonyUser->getUserIdentifier()));

            $this->entityManager->persist($like);
            $this->entityManager->flush();
        }

        /** @var array<string, string|array<string, string>> $output */
        $output = JSON::decode(
            $this->serializer->serialize(
                [
                    'id' => $like->getUuid(),
                    'user' => $like->getUser()
                ],
                'json',
                [
                    'groups' => 'Post',
                ]
            ),
            true,
        );

        return new JsonResponse($output);
    }
}
