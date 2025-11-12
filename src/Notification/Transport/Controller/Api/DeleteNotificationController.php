<?php

declare(strict_types=1);

namespace App\Notification\Transport\Controller\Api;

use App\Notification\Domain\Entity\Notification;
use App\Notification\Infrastructure\Repository\NotificationRepository;
use Bro\WorldCoreBundle\Infrastructure\ValueObject\SymfonyUser;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @package App\Controller
 * @author Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AsController]
#[OA\Tag(name: 'Notification')]
class DeleteNotificationController
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository
    ) {
    }

    #[Route(path: '/v1/platform/notifications/{notification}', name: 'notification_delete', methods: [Request::METHOD_DELETE])]
    public function __invoke(
        SymfonyUser $symfonyUser,
        Request $request,
        #[MapEntity]
        Notification $notification
    ): JsonResponse {
        $this->notificationRepository->remove($notification);

        return new JsonResponse([
            'status' => 'Notification deleted',
        ], 200);
    }
}
