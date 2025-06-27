<?php

declare(strict_types=1);

namespace App\Notification\Transport\Controller\Api;

use App\General\Infrastructure\ValueObject\SymfonyUser;
use App\Notification\Application\Service\MailjetEmailService;
use App\Notification\Application\Service\NotificationManager;
use App\Notification\Infrastructure\Repository\NotificationRepository;
use DateTime;
use Exception;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use OpenApi\Attributes as OA;

/**
 * Class MailjetEmailController
 * @package App\Controller
 * @author Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AsController]
#[OA\Tag(name: 'Notification')]
class MailjetEmailController
{
    public function __construct(
        private readonly NotificationManager $notificationManager,
        private readonly MailjetEmailService $mailjetEmailService,
        private readonly NotificationRepository $notificationRepository
    )
    {
    }

    /**
     * @param SymfonyUser $symfonyUser
     * @param Request     $request
     *
     * @throws JsonException
     * @return JsonResponse
     */
    #[Route(path: '/v1/platform/notifications/batch', name: 'notification_batch_create', methods: [Request::METHOD_POST])]
    public function __invoke(SymfonyUser $symfonyUser, Request $request): JsonResponse
    {
        $data = $this->populateEntity($request);
        $data['paths'] = $this->notificationManager->getPaths($request);
        $notification = $this->notificationManager->validateNotification($data, $data['paths']);

        try {
            $response = $this->mailjetEmailService->sendEmailBatch($notification);
            if($response) {
                foreach ($response as $value) {
                    if(!isset($value['error'])) {
                        $notification->setCompletedAt(new DateTime('now'));
                    }
                    $this->notificationRepository->save($notification);
                }
            }
        } catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
        }
        return new JsonResponse($notification->getId(), 200);
    }

    /**
     * @param Request $request
     *
     * @throws JsonException
     * @return array
     */
    private function populateEntity(Request $request): array
    {
        $recipients = json_decode($request->request->get('recipients'), false, 512, JSON_THROW_ON_ERROR);
        $recipients = json_decode(json_encode($recipients, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        return [
            'channel' => $request->request->get('channel'),
            'recipients' => $recipients,
            'emailSenderEmail' => $request->request->get('emailSenderEmail'),
            'emailSenderName' => $request->request->get('emailSenderName'),
            'templateId' => (int)$request->request->get('templateId'),
            'emailSubject' => $request->request->get('emailSubject'),
            'emailContentPlain' => $request->request->get('emailContentPlain'),
            'emailContentHtml' => $request->request->get('emailContentHtml'),
            'scope' => $request->request->get('scope'),
            'sendAfter' => $request->request->get('sendAfter'),
        ];
    }
}
