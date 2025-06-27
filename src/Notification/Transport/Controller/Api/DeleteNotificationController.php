<?php

declare(strict_types=1);

namespace App\Notification\Transport\Controller\Api;

use App\General\Infrastructure\ValueObject\SymfonyUser;
use App\Notification\Domain\Entity\Notification;
use App\Notification\Infrastructure\Repository\NotificationRepository;
use Exception;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class MailjetEmailController
 * @package App\Controller
 * @author Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AsController]
#[OA\Tag(name: 'Notification')]
class DeleteNotificationController
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository)
    {
    }

    /**
     * @param SymfonyUser  $symfonyUser
     * @param Request      $request
     * @param Notification $notification
     *
     * @return JsonResponse
     */
    #[Route(path: '/v1/platform/notifications/{notification}', name: 'notification_delete', methods: [Request::METHOD_DELETE])]
    public function __invoke(SymfonyUser $symfonyUser,
        Request $request,
        #[MapEntity] Notification $notification
    ): JsonResponse
    {
        $this->notificationRepository->remove($notification);

        return new JsonResponse(['status' => 'Notification deleted'], 200);
    }
}
