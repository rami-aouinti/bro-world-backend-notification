<?php

declare(strict_types=1);

namespace App\Notification\Transport\Controller\Api;

use App\Notification\Application\Service\NotificationManager;
use App\Notification\Domain\Message\NotificationDispatchMessage;
use Bro\WorldCoreBundle\Infrastructure\ValueObject\SymfonyUser;
use Doctrine\ORM\Exception\ORMException;
use Exception;
use JsonException;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @package App\Controller
 * @author Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AsController]
#[OA\Tag(name: 'Notification')]
readonly class NotificationMailjetEmailController
{
    public function __construct(
        private NotificationManager $notificationManager,
        #[Autowire(service: 'messenger.bus.command')]
        private readonly MessageBusInterface $commandBus
    ) {
    }

    /**
     * @throws JsonException
     * @throws ORMException
     * @throws TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws Exception
     */
    #[Route(path: '/v1/platform/notifications', name: 'notification_create', methods: [Request::METHOD_POST])]
    public function __invoke(SymfonyUser $symfonyUser, Request $request): JsonResponse
    {
        $data = $this->populateEntity($request);
        $notification = $this->notificationManager->validateNotification(
            $this->populateEntity($request),
            $this->notificationManager->getPaths($request)
        );

        if (($data['channel'] === 'EMAIL') && ($data['templateId'] !== 0)) {
            $this->notificationManager->verifyVariables($data['recipients'], $data['templateId']);
        }

        $this->commandBus->dispatch(
            new NotificationDispatchMessage($notification->getId(), $data['channel'])
        );

        return new JsonResponse($notification->getId(), 200);
    }

    /**
     * @throws JsonException
     */
    private function populateEntity(Request $request): array
    {
        $notificationData = [
            'channel' => $request->request->get('channel'),
            'scope' => $request->request->get('scope'),
            'sendAfter' => $request->request->get('sendAfter'),
        ];

        if ($request->request->get('channel') === 'PUSH') {
            $data = [
                'topic' => $request->request->get('topic'),
                'pushTitle' => $request->request->get('pushTitle'),
                'pushSubtitle' => $request->request->get('pushSubtitle'),
                'pushContent' => $request->request->get('pushContent'),
                'scopeTarget' => json_decode($request->request->get('scopeTarget'), true, 512, JSON_THROW_ON_ERROR),
            ];
        } elseif ($request->request->get('channel') === 'SMS') {
            $data = [
                'smsContent' => $request->request->get('smsContent'),
                'smsSenderName' => $request->request->get('smsSenderName'),
                'scopeTarget' => json_decode($request->request->get('scopeTarget'), true, 512, JSON_THROW_ON_ERROR),
            ];
        } else {
            $recipients = json_decode($request->request->get('recipients'), false, 512, JSON_THROW_ON_ERROR);
            $recipients = json_decode(json_encode($recipients, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

            $emailRecipientsCc = json_decode(
                $request->request->get('emailRecipientsCc'),
                false,
                512,
                JSON_THROW_ON_ERROR
            );
            $emailRecipientsCc = json_decode(json_encode($emailRecipientsCc, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

            $emailRecipientsBcc = json_decode(
                $request->request->get('emailRecipientsBcc'),
                false,
                512,
                JSON_THROW_ON_ERROR
            );
            $emailRecipientsBcc = json_decode(json_encode($emailRecipientsBcc, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

            $emailRecipientsReplyTo = json_decode(
                $request->request->get('emailRecipientsReplyTo'),
                false,
                512,
                JSON_THROW_ON_ERROR
            );
            $emailRecipientsReplyTo = json_decode(json_encode($emailRecipientsReplyTo, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

            $data = [
                'recipients' => $recipients,
                'emailRecipientsCc' => $emailRecipientsCc,
                'emailRecipientsBcc' => $emailRecipientsBcc,
                'emailRecipientsReplyTo' => $emailRecipientsReplyTo,
                'emailSenderEmail' => $request->request->get('emailSenderEmail'),
                'emailSenderName' => $request->request->get('emailSenderName'),
                'templateId' => (int)$request->request->get('templateId'),
                'emailSubject' => $request->request->get('emailSubject'),
                'emailContentPlain' => $request->request->get('emailContentPlain'),
                'emailContentHtml' => $request->request->get('emailContentHtml'),
            ];
        }

        return array_merge($data, $notificationData);
    }
}
