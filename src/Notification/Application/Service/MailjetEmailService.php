<?php

declare(strict_types=1);

namespace App\Notification\Application\Service;

use App\Notification\Domain\Entity\EmailNotification;
use App\Notification\Infrastructure\Repository\MailjetTemplateRepository;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\HttpClient\Exception\{
    ClientExceptionInterface,
    DecodingExceptionInterface,
    RedirectionExceptionInterface,
    ServerExceptionInterface,
    TransportExceptionInterface
};
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function is_array;
use function is_object;

/**
 * @package App\Notification\Application\Service
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class MailjetEmailService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private MailjetTemplateRepository $templateRepository,
        private string $mailjetApiKey,
        private string $mailjetSecretKey,
        private string $mailjetSenderEmail,
        private KernelInterface $kernel,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @throws Exception|TransportExceptionInterface|ServerExceptionInterface|RedirectionExceptionInterface|DecodingExceptionInterface|ClientExceptionInterface
     */
    public function sendEmail(EmailNotification $notification): array
    {
        return $this->handleEmailSend($notification, false);
    }

    /**
     * @throws Exception|TransportExceptionInterface|ServerExceptionInterface|RedirectionExceptionInterface|DecodingExceptionInterface|ClientExceptionInterface
     */
    public function sendEmailBatch($notification): array
    {
        return $this->handleEmailSend($notification, true);
    }

    public function flattenVariables(array $variables, string $prefix = ''): array
    {
        $flattened = [];
        foreach ($variables as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;
            if (is_array($value) || is_object($value)) {
                $flattened += $this->flattenVariables((array)$value, $newKey);
            } else {
                $flattened[$newKey] = $value;
            }
        }

        return $flattened;
    }

    public function verifyRequiredFields(array $requiredFields, array $data): bool
    {
        foreach ($requiredFields as $key => $field) {
            if (str_contains($key, 'items.')) {
                $parts = explode('.', $field);
                $indexKey = 'items.0.' . $parts[0];
                if (!isset($data[$indexKey])) {
                    return false;
                }
            } elseif (!isset($data[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    private function handleEmailSend(EmailNotification $notification, bool $batch): array
    {
        $templateId = $notification->getTemplateId();
        $recipients = $notification->getRecipients() ?? [];
        $attachments = $this->prepareAttachments($notification->getBinaryAttachments());

        $requiredVariables = [];
        if ($templateId !== 0) {
            $template = $this->templateRepository->findOneBy([
                'templateId' => $templateId,
            ]);
            if (!$template) {
                throw new RuntimeException('Template not found');
            }
            $requiredVariables = $template->getVariables();
        }

        $prepared = $batch
            ? $this->prepareMessagesBatch($recipients, $attachments, $requiredVariables, $templateId, $notification)
            : $this->prepareMessages($recipients, $attachments, $requiredVariables, $templateId, $notification);

        return $this->sendRequestToMailjet($prepared);
    }

    private function prepareAttachments(?array $paths): array
    {
        $attachments = [];
        if ($paths) {
            foreach ($paths as $path) {
                $filePath = $this->kernel->getProjectDir() . '/var/uploads/' . basename($path);
                if (file_exists($filePath)) {
                    $attachments[] = [
                        'ContentType' => mime_content_type($filePath),
                        'Filename' => basename($filePath),
                        'Base64Content' => base64_encode(file_get_contents($filePath)),
                    ];
                }
            }
        }

        return $attachments;
    }

    private function prepareMessagesBatch(array $recipients, ?array $attachments, ?array $requiredVars, ?int $templateId, EmailNotification $notification): array
    {
        $messages = [];
        $errors = [];

        foreach ($recipients as $recipient) {
            if (!isset($recipient['email'], $recipient['variables'])) {
                $errors[] = $this->logError($recipient, 'Each recipient must have an email and variables');

                continue;
            }

            if (
                !empty($requiredVars) && !$this->verifyRequiredFields(
                    $this->flattenVariables($requiredVars),
                    $this->flattenVariables($recipient['variables'])
                )
            ) {
                $errors[] = $this->logError($recipient, 'Some required variables are missing');

                continue;
            }

            $message = $this->buildMessage(
                $recipient['email'],
                $recipient['variables'],
                $templateId,
                $notification,
                $attachments
            );

            $messages[] = $message;
        }

        return [
            'messages' => $messages,
            'errors' => $errors,
        ];
    }

    private function prepareMessages(array $recipients, ?array $attachments, ?array $requiredVars, ?int $templateId, EmailNotification $notification): array
    {
        $messages = [];
        $errors = [];
        $to = [];

        $recipient = $recipients[0];
        if (!isset($recipient['email'], $recipient['variables'])) {
            $errors[] = $this->logError($recipient, 'Recipient must have an email');
        }
        if (
            !empty($requiredVars) && !$this->verifyRequiredFields(
                $this->flattenVariables($requiredVars),
                $this->flattenVariables($recipient['variables'])
            )
        ) {
            $errors[] = $this->logError($recipient, 'Some required variables are missing');
        }
        foreach ($recipient['email'] as $email) {
            if (!isset($email['address'])) {
                $errors[] = $this->logError($recipient, 'Missing email address');

                continue;
            }
            $to[] = [
                'Email' => $email['address'],
            ];
        }
        if (!empty($to)) {
            $message = $this->buildMail(
                $to,
                $recipient['variables'],
                $templateId,
                $notification,
                $attachments
            );

            $messages[] = $message;
        }

        return [
            'messages' => $messages,
            'errors' => $errors,
        ];
    }
    private function buildMail(array $emails, array $variables, int $templateId, EmailNotification $notification, ?array $attachments): array
    {
        $message = [
            'From' => [
                'Email' => $this->mailjetSenderEmail,
            ],
            'To' => $emails,
            'Subject' => $notification->getEmailSubject(),
        ];

        if ($templateId !== 0) {
            $message['TemplateID'] = $templateId;
            $message['TemplateLanguage'] = true;
            $message['Variables'] = $variables;
        } else {
            $message['TextPart'] = $notification->getEmailContentPlain();
            $message['HTMLPart'] = $notification->getEmailContentHtml();
        }

        $this->addOptionalRecipients($message, $notification);

        if (!empty($attachments)) {
            $message['Attachments'] = $attachments;
        }

        return $message;
    }

    private function buildMessage(array $emails, array $variables, int $templateId, EmailNotification $notification, ?array $attachments): array
    {
        $message = [
            'From' => [
                'Email' => $this->mailjetSenderEmail,
            ],
            'To' => $this->formatEmailList($emails),
            'Subject' => $notification->getEmailSubject(),
        ];

        if ($templateId !== 0) {
            $message['TemplateID'] = $templateId;
            $message['TemplateLanguage'] = true;
            $message['Variables'] = $variables;
        } else {
            $message['TextPart'] = $notification->getEmailContentPlain();
            $message['HTMLPart'] = $notification->getEmailContentHtml();
        }

        $this->addOptionalRecipients($message, $notification);

        if (!empty($attachments)) {
            $message['Attachments'] = $attachments;
        }

        return $message;
    }

    private function addOptionalRecipients(array &$message, EmailNotification $notification): void
    {
        $this->addRecipientType($message, 'Cc', $notification->getEmailRecipientsCc(), 'emailCc');
        $this->addRecipientType($message, 'Bcc', $notification->getEmailRecipientsBcc(), 'emailBcc');

        $replyToData = $notification->getEmailRecipientsReplyTo();
        if (!empty($replyToData[0]['emailReplyTo'][0])) {
            $email = $replyToData[0]['emailReplyTo'][0];
            $message['ReplyTo'] = [
                'Email' => $email['address'],
                'Name' => $email['name'] ?? null,
            ];
        }
    }

    private function addRecipientType(array &$message, string $field, ?array $data, string $key): void
    {
        if (!empty($data[0][$key])) {
            $message[$field] = $this->formatEmailList($data[0][$key]);
        }
    }

    private function formatEmailList(array $emails): array
    {
        return array_map(fn ($email) => [
            'Email' => $email['address'],
            'Name' => $email['name'] ?? null,
        ], $emails);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function sendRequestToMailjet(array $prepared): array
    {
        if (count($prepared['errors']) === 0) {
            $response = $this->httpClient->request('POST', 'https://api.mailjet.com/v3.1/send', [
                'auth_basic' => [$this->mailjetApiKey, $this->mailjetSecretKey],
                'json' => [
                    'Messages' => $prepared['messages'],
                ],
            ]);

            return $response->toArray(false);
        }

        return $prepared['errors'];
    }

    private function logError(array $recipient, string $errorMessage): array
    {
        $error = [
            'recipient' => $recipient['email'] ?? 'unknown',
            'error' => $errorMessage,
        ];
        $this->logger->error('Email preparation error', $error);

        return $error;
    }
}
