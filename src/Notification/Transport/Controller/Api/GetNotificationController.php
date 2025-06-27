<?php

declare(strict_types=1);

namespace App\Notification\Transport\Controller\Api;

use App\General\Domain\Utils\JSON;
use App\General\Infrastructure\ValueObject\SymfonyUser;
use App\Notification\Domain\Entity\Notification;
use App\Notification\Infrastructure\Repository\MailjetTemplateRepository;
use App\Notification\Infrastructure\Repository\NotificationRepository;
use Closure;
use Exception;
use JsonException;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @package App\Notification
 */
#[AsController]
#[OA\Tag(name: 'Notification')]
readonly class GetNotificationController
{
    public function __construct(
        private SerializerInterface $serializer
    ) {
    }

    /**
     * Get current user blog data, accessible only for 'IS_AUTHENTICATED_FULLY' users
     *
     * @param SymfonyUser  $symfonyUser
     * @param Notification $notification
     *
     * @throws ExceptionInterface
     * @throws JsonException
     * @return JsonResponse
     */
    #[Route(path: '/v1/notifications/{notification}', name: 'notification_index', methods: [Request::METHOD_GET])]
    #[Cache(smaxage: 10)]
    public function __invoke(SymfonyUser $symfonyUser, Notification $notification): JsonResponse
    {
        $output = JSON::decode(
            $this->serializer->serialize(
                $notification,
                'json',
                [
                    'groups' => 'Notification',
                ]
            ),
            true,
        );
        return new JsonResponse($output);
    }
}
