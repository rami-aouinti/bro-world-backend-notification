<?php

declare(strict_types=1);

namespace App\Notification\Transport\Controller\Api;

use App\Notification\Infrastructure\Repository\NotificationRepository;
use Bro\WorldCoreBundle\Domain\Utils\JSON;
use Bro\WorldCoreBundle\Infrastructure\ValueObject\SymfonyUser;
use JsonException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

use function in_array;

/**
 * @package App\Notification
 */
#[AsController]
#[OA\Tag(name: 'Notification')]
readonly class UserNotificationController
{
    public function __construct(
        private SerializerInterface $serializer,
        private NotificationRepository $notificationRepository
    ) {
    }

    /**
     * Get current user blog data, accessible only for 'IS_AUTHENTICATED_FULLY' users
     *
     * @throws ExceptionInterface
     * @throws JsonException
     */
    #[Route(path: '/v1/profile/notifications', name: 'notification_profile', methods: [Request::METHOD_GET])]
    public function __invoke(SymfonyUser $symfonyUser): JsonResponse
    {
        $targetId = $symfonyUser->getId();
        $notifications = $this->notificationRepository->findAll();
        $userNotifications = array_filter($notifications, static function ($notification) use ($targetId) {
            return in_array($targetId, $notification->getScopeTarget() ?? [], true);
        });
        $output = JSON::decode(
            $this->serializer->serialize(
                $userNotifications,
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
