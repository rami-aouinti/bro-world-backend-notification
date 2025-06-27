<?php

declare(strict_types=1);

namespace App\Notification\Transport\Controller\Api;

use App\Notification\Application\Service\NotificationManager;
use Doctrine\ORM\Exception\ORMException;
use Exception;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Class MailjetEmailController
 * @package App\Controller
 * @author Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
final class NotificationMailjetEmailController extends AbstractController
{
    public function __construct(
        private readonly NotificationManager $notificationManager
    )
    {
    }

    /**
     * @param Request $request
     *
     * @throws JsonException
     * @throws ORMException
     * @throws TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws Exception
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $data = $this->populateEntity($request);
        $notification = $this->notificationManager->validateNotification(
            $this->populateEntity($request),
            $this->notificationManager->getPaths($request)
        );

        if(($data['channel'] === 'EMAIL') &&  ($data['templateId'] !== 0 )) {
            $this->notificationManager->verifyVariables($data['recipients'], $data['templateId']);
        }


        $this->notificationManager->dispatch($notification->getId(), $data['channel']);
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
        $notificationData = [
            'channel' => $request->request->get('channel'),
            'scope' => $request->request->get('scope'),
            'sendAfter' => $request->request->get('sendAfter')
        ];

        if($request->request->get('channel') === "PUSH") {
            $data = [
                'topic' => $request->request->get('topic'),
                'pushTitle' => $request->request->get('pushTitle'),
                'pushSubtitle' => $request->request->get('pushSubtitle'),
                'pushContent' => $request->request->get('pushContent'),
                'scopeTarget' => json_decode($request->request->get('scopeTarget'), true, 512, JSON_THROW_ON_ERROR)
            ];
        } else if($request->request->get('channel') === "SMS") {
            $data = [
                'smsContent' => $request->request->get('smsContent'),
                'smsSenderName' => $request->request->get('smsSenderName'),
                'scopeTarget' => json_decode($request->request->get('scopeTarget'), true, 512, JSON_THROW_ON_ERROR)
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
