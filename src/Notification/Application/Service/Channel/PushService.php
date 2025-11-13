<?php

declare(strict_types=1);

namespace App\Notification\Application\Service\Channel;

use App\Notification\Domain\Entity\Enum\Scope;
use App\Notification\Domain\Entity\PushNotification;
use DateTime;
use InvalidArgumentException;
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
        private HubInterface $hub,
        private string $mercurePublicUrl
    ) {
    }

    /**
     * @throws JsonException
     */
    public function generatePushNotification(PushNotification $notification, ?array $user): array
    {
        $scope = $this->buildTopic($notification, $user);
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
            'message' => $response,
        ];
    }

    private function buildTopic(PushNotification $notification, ?array $user): string
    {
        $topic = (string) $notification->getTopic();

        if (
            in_array($notification->getScope(), [Scope::WORKPLACE, Scope::SEGMENT], true) &&
            isset($user['id'])
        ) {
            $topic = rtrim($topic, '/') . '/' . $user['id'];
        }

        return $this->normalizeTopic($topic);
    }

    private function normalizeTopic(string $topic): string
    {
        if (filter_var($topic, FILTER_VALIDATE_URL)) {
            return $topic;
        }

        $parts = parse_url($this->mercurePublicUrl);

        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            throw new InvalidArgumentException('Invalid Mercure public URL configuration.');
        }

        $base = $parts['scheme'] . '://' . $parts['host'];

        if (isset($parts['port'])) {
            $base .= ':' . $parts['port'];
        }

        return rtrim($base, '/') . '/' . ltrim($topic, '/');
    }
}
