<?php

declare(strict_types=1);

namespace App\Notification\Application\ApiProxy;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class UserProxy
 *
 * @package App\Blog\Application\ApiProxy
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class UserProxy
{

    public function __construct(
        private HttpClientInterface $httpClient
    )
    {
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
        $response = $this->httpClient->request('GET', "https://bro-world.org/api/v1/user", [
            'headers' => [
                'Authorization' => 'ApiKey agYybuBZFsjXaCKBfjFWa2qFYMUshXZWFcz575KT',
            ],
        ]);

        return $response->toArray();
    }

    /**
     * @param $mediaId
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @return array
     */
    public function getMedia($mediaId): array
    {
        $response = $this->httpClient->request(
            'GET',
            "https://media.bro-world.org/v1/platform/media/" . $mediaId
        );

        return $response->toArray();
    }
}
