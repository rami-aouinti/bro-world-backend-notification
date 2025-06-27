<?php

declare(strict_types=1);

namespace App\Notification\Transport\Controller\Api;

use App\General\Domain\Utils\JSON;
use App\General\Infrastructure\ValueObject\SymfonyUser;
use App\Notification\Domain\Entity\MailjetTemplate;
use JsonException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @package App\Notification
 */
#[AsController]
#[OA\Tag(name: 'Notification')]
readonly class GetTemplateController
{
    public function __construct(
        private SerializerInterface $serializer
    ) {
    }

    /**
     * Get current user blog data, accessible only for 'IS_AUTHENTICATED_FULLY' users
     *
     * @param SymfonyUser     $symfonyUser
     * @param MailjetTemplate $template
     *
     * @throws ExceptionInterface
     * @throws JsonException
     * @return JsonResponse
     */
    #[Route(path: '/v1/templates/{template}', name: 'public_template', methods: [Request::METHOD_GET])]
    #[Cache(smaxage: 10)]
    public function __invoke(SymfonyUser $symfonyUser, MailjetTemplate $template): JsonResponse
    {
        $output = JSON::decode(
            $this->serializer->serialize(
                $template,
                'json',
                [
                    'groups' => 'MailjetTemplate',
                ]
            ),
            true,
        );
        return new JsonResponse($output);
    }
}
