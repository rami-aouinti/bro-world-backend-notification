<?php

declare(strict_types=1);

namespace App\Notification\Application\ApiProxy;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function sprintf;

/**
 * @package App\Blog\Application\ApiProxy
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class UserProxy
{
    private const USERS_CACHE_KEY = 'notification.users';
    private const USERS_CACHE_TTL = 300; // 5 minutes
    private const MEDIA_CACHE_KEY = 'notification.media.%s';
    private const MEDIA_CACHE_TTL = 600; // 10 minutes

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getUsers(): array
    {
        return $this->cache->get(self::USERS_CACHE_KEY, function (ItemInterface $item) {
            $item->expiresAfter(self::USERS_CACHE_TTL);

            return $this->fetchUsers();
        });
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getMedia($mediaId): array
    {
        $cacheKey = sprintf(self::MEDIA_CACHE_KEY, $mediaId);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($mediaId) {
            $item->expiresAfter(self::MEDIA_CACHE_TTL);

            return $this->fetchMedia($mediaId);
        });
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function fetchUsers(): array
    {
        $response = $this->httpClient->request('GET', 'https://bro-world.org/api/v1/user', [
            'headers' => [
                'Authorization' => 'ApiKey u6gzbhNYEr5WkvVUxuZUeh7iEJsbUxDEpqpy1uCV',
            ],
        ]);

        return $response->toArray();
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function fetchMedia(string $mediaId): array
    {
        $response = $this->httpClient->request(
            'GET',
            'https://media.bro-world.org/v1/platform/media/' . $mediaId
        );

        return $response->toArray();
    }
}
