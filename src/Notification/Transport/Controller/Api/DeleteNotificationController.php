<?php

declare(strict_types=1);

namespace App\Notification\Transport\Controller\Api;

use App\Notification\Domain\Entity\Notification;
use App\Notification\Infrastructure\Repository\NotificationRepository;
use Exception;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MailjetEmailController
 * @package App\Controller
 * @author Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
final class DeleteNotificationController extends AbstractController
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository)
    {
    }

    /**
     * @param Notification $notification
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function __invoke(
        Request $request,
        #[MapEntity] Notification $notification
    ): JsonResponse
    {
        $this->notificationRepository->remove($notification);

        return new JsonResponse(['status' => 'Notification deleted'], 200);
    }
}
