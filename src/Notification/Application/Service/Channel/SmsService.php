<?php

declare(strict_types=1);

namespace App\Notification\Application\Service\Channel;

use App\Notification\Domain\Entity\SmsNotification;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

/**
 * @package App\Notification\Application\Service
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
class SmsService
{
    private Client $client;

    private string $from;

    /**
     * @param string $sid
     * @param string $token
     * @param string $from
     */
    public function __construct(
        string $sid, string $token, string $from
    )
    {
        $this->client = new Client($sid, $token);
        $this->from = $from;
    }

    /**
     * @param SmsNotification $notification
     * @param array|null      $user
     *
     * @throws TwilioException
     * @return array
     */
    public function generateSms(SmsNotification $notification, ?array $user): array
    {
        $response = $this->client->messages->create($user['phone'], [
            'from' => $this->from,
            'body' => $notification->getSmsContent(),
        ]);

        return [
            'status' => $response
        ];
    }

}
