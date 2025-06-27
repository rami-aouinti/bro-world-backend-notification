<?php

declare(strict_types=1);

namespace App\Notification\Application\Service\Channel;

use App\Notification\Domain\Entity\Enum\Scope;
use App\Notification\Domain\Entity\PushNotification;
use DateTime;
use JsonException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * @package App\Notification\Application\Service
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class PushService
{
    public function __construct(
        private HubInterface $hub
    ) {
    }

    /**
     * @throws JsonException
     */
    public function generatePushNotification(PushNotification $notification, ?array $user): array
    {
        if(
            ($notification->getScope() === Scope::WORKPLACE) &&
            ($notification->getScope() === Scope::SEGMENT)
        ) {
            $scope = $notification->getTopic() . $user['id'];
        } else {
            $scope = $notification->getTopic();
        }
        $update = new Update(
            $scope,
            json_encode([
                'title' => $notification->getPushTitle(),
                'subtitle' => $notification->getPushSubtitle(),
                'content' => $notification->getPushContent(),
                'createdAt' => (new DateTime())->format('Y-m-d H:i:s'),
            ], JSON_THROW_ON_ERROR)
        );

        $response = $this->hub->publish($update);

        return [
            'status' => 'success',
            'message' => $response
        ];
    }
}
