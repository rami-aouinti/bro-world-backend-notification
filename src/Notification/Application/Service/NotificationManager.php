<?php

declare(strict_types=1);

namespace App\Notification\Application\Service;

use App\Notification\Application\Dto\NotificationDto;
use App\Notification\Application\Factory\Notification\NotificationFactoryResolver;
use App\Notification\Application\Service\Scope\GlobalScopeSender;
use App\Notification\Application\Service\Scope\IndividualScopeSender;
use App\Notification\Application\Service\Scope\SegmentScopeSender;
use App\Notification\Application\Service\Scope\WorkplaceScopeSender;
use App\Notification\Domain\Entity\Enum\Scope;
use App\Notification\Domain\Entity\Notification;
use App\Notification\Infrastructure\Repository\MailjetTemplateRepository;
use App\Notification\Infrastructure\Repository\NotificationRepository;
use Doctrine\ORM\Exception\ORMException;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function dirname;
use function sprintf;

/**
 * @package App\Notification\Application\Service
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class NotificationManager
{
    public function __construct(
        private SegmentScopeSender $segmentScope,
        private GlobalScopeSender $globalScope,
        private IndividualScopeSender $individualScope,
        private WorkplaceScopeSender $workplaceScope,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private NotificationRepository $notificationRepository,
        private MailjetEmailService $mailjetEmailService,
        private MailjetTemplateRepository $templateRepository,
        private NotificationFactoryResolver $notificationFactoryResolver
    ) {
    }

    /**
     * @throws ORMException
     * @throws TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function dispatch(string $notificationId, string $channel): array
    {
        $notification = $this->notificationRepository->find($notificationId);
        if ($notification) {
            return $this->sendNotifications($notification, $channel);
        }

        return [
            'message' => 'Entity not found',
        ];
    }

    /**
     * @throws ORMException
     * @throws TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function sendNotifications($notification, string $channel): array
    {
        return match ($notification->getScope()) {
            Scope::INDIVIDUAL => $this->individualScope->send($notification, $channel),
            Scope::GLOBAL => $this->globalScope->send($notification, $channel),
            Scope::SEGMENT => $this->segmentScope->send($notification, $channel),
            Scope::WORKPLACE => $this->workplaceScope->send($notification, $channel),
            default => throw new InvalidArgumentException(
                sprintf('Unknown notification scope: %s', $notification->getScope())
            ),
        };
    }

    /**
     * Prepares and validates a notification.
     *
     * @return Notification Returns the prepared notification object.
     * @throws Exception
     */
    public function validateNotification(array $data, ?array $paths): Notification
    {
        try {
            /** @var NotificationDto $dto */
            $dto = $this->serializer->denormalize($data, NotificationDto::class);
        } catch (Exception $e) {
            throw new BadRequestHttpException('Invalid input: ' . $e->getMessage());
        }

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            throw new BadRequestHttpException($this->formatErrors($errors));
        }

        $notification = $this->notificationFactoryResolver->createNotification($dto, $dto->channel, $paths);

        $errors = $this->validator->validate($notification);
        if (count($errors) > 0) {
            throw new BadRequestHttpException($this->formatErrors($errors));
        }

        $this->notificationRepository->save($notification);

        return $notification;
    }

    public function getPaths(Request $request): array
    {
        $paths = [];
        $files = $request->files->all('pdf_attachments');
        $uploadDir = $this->getUploadDir();

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            throw new RuntimeException(sprintf('Unable to create upload directory: %s', $uploadDir));
        }

        foreach ($files as $file) {
            if ($file && $file->isValid()) {
                $filename = uniqid('upload_', true) . '_' . $file->getClientOriginalName();
                $path = $file->move($uploadDir, $filename)->getPathname();
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * @throws Exception
     */
    public function verifyVariables(array $recipient, int|string $templateId): void
    {
        if ((int)$templateId === 0) {
            return;
        }

        $template = $this->templateRepository->findOneBy([
            'templateId' => $templateId,
        ]);

        if (!$template) {
            throw new RuntimeException('Template not found.');
        }

        $requiredVariables = $template->getVariables();

        if (empty($requiredVariables)) {
            return;
        }

        $recipientVariables = $recipient[0]['variables'] ?? [];

        $flattenedRecipientVars = $this->mailjetEmailService->flattenVariables($recipientVariables);
        $flattenedRequiredVars = $this->mailjetEmailService->flattenVariables($requiredVariables);

        if (!$this->mailjetEmailService->verifyRequiredFields($flattenedRequiredVars, $flattenedRecipientVars)) {
            throw new BadRequestHttpException('Some required template variables are missing.');
        }
    }

    private function formatErrors(ConstraintViolationListInterface $errors): string
    {
        $messages = [];
        foreach ($errors as $error) {
            $messages[] = sprintf('%s: %s', $error->getPropertyPath(), $error->getMessage());
        }

        return implode("\n", $messages);
    }

    private function getUploadDir(): string
    {
        return dirname(__DIR__, 2) . '/var/uploads';
    }
}
