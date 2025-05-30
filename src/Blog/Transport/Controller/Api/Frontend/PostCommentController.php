<?php

declare(strict_types=1);

namespace App\Blog\Transport\Controller\Api\Frontend;

use App\Blog\Domain\Entity\Blog;
use App\Blog\Domain\Entity\Comment;
use App\Blog\Domain\Entity\Post;
use App\Blog\Domain\Repository\Interfaces\BlogRepositoryInterface;
use App\Blog\Transport\Event\CommentCreatedEvent;
use App\General\Domain\Utils\JSON;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JsonException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @package App\Blog
 */
#[AsController]
#[OA\Tag(name: 'Blog')]
readonly class PostCommentController
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
     * @throws Exception
     */
    #[Route(
        path: '/v1/platform/blog/{userId}/{postSlug}/new',
        name: 'comment_new',
        requirements: ['postSlug' => Requirement::ASCII_SLUG],
        methods: [Request::METHOD_POST],
    )]
    #[OA\RequestBody(
        request: 'body',
        description: 'Credentials object',
        required: true,
        content: new JsonContent(
            properties: [
                new Property(property: 'phone', ref: new Model(type: Blog::class, groups: ['Blog.phone'])),
                new Property(property: 'userId', ref: new Model(type: Blog::class, groups: ['Blog.userId'])),
                new Property(property: 'title', ref: new Model(type: Blog::class, groups: ['Blog.title'])),
                new Property(property: 'description', ref: new Model(type: Blog::class, groups: ['Blog.description'])),
                new Property(property: 'photo', ref: new Model(type: Blog::class, groups: ['Blog.photo'])),
                new Property(property: 'birthday', ref: new Model(type: Blog::class, groups: ['Blog.birthday'])),
                new Property(property: 'gender', ref: new Model(type: Blog::class, groups: ['Blog.gender'])),
                new Property(property: 'googleId', ref: new Model(type: Blog::class, groups: ['Blog.googleId'])),
                new Property(property: 'githubId', ref: new Model(type: Blog::class, groups: ['Blog.githubId'])),
                new Property(property: 'githubUrl', ref: new Model(type: Blog::class, groups: ['Blog.githubUrl'])),
                new Property(property: 'instagramUrl', ref: new Model(type: Blog::class, groups: ['Blog.instagramUrl'])),
                new Property(property: 'linkedInId', ref: new Model(type: Blog::class, groups: ['Blog.linkedInId'])),
                new Property(property: 'linkedInUrl', ref: new Model(type: Blog::class, groups: ['Blog.linkedInUrl'])),
                new Property(property: 'twitterUrl', ref: new Model(type: Blog::class, groups: ['Blog.twitterUrl'])),
                new Property(property: 'facebookUrl', ref: new Model(type: Blog::class, groups: ['Blog.facebookUrl'])),
            ],
            type: 'object',
            example: [
                'phone' => '+33612345678',
                'userId' => '550e8400-e29b-41d4-a716-446655440000',
                'title' => 'Developer Backend Symfony',
                'description' => 'Expert API et microservices.',
                'photo' => '550e8400-e29b-41d4-a716-446655440001',
                'birthday' => '1993-05-14',
                'gender' => 'Men',
                'googleId' => '12345678901234567890',
                'githubId' => '98765432109876543210',
                'githubUrl' => 'https://github.com/johndoe',
                'instagramUrl' => 'https://instagram.com/johndoe',
                'linkedInId' => 'abc123def456ghi789',
                'linkedInUrl' => 'https://linkedin.com/in/johndoe',
                'twitterUrl' => 'https://twitter.com/johndoe',
                'facebookUrl' => 'https://facebook.com/johndoe'
            ],
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Blog data',
        content: new JsonContent(
            ref: new Model(
                type: Comment::class,
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
        Request $request,
        string $userId,
        #[MapEntity(mapping: ['postSlug' => 'slug'])] Post $post,
        EventDispatcherInterface $eventDispatcher
    ): JsonResponse
    {
        $comment = new Comment();
        $uuidUser = Uuid::isValid($userId) ? Uuid::fromString($userId) : Uuid::uuid1();
        $comment->setAuthor($uuidUser);
        $post->addComment($comment);
        $eventDispatcher->dispatch(new CommentCreatedEvent($comment));

        $this->entityManager->persist($post);
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
