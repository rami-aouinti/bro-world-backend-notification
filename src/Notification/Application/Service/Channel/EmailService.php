<?php

declare(strict_types=1);

namespace App\Notification\Application\Service\Channel;

use App\Notification\Application\Service\MailjetEmailService;
use App\Notification\Domain\Entity\EmailNotification;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @package App\Notification\Application\Service
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class EmailService
{
    public function __construct(
        private MailjetEmailService $mailjetEmailService
    ) {
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function generateEmail(EmailNotification $notification, ?array $user): array
    {
        return $this->mailjetEmailService->sendEmail($notification);
    }
}
