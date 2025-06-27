<?php

declare(strict_types=1);

namespace App\Notification\Transport\Controller\Api;

use App\General\Domain\Utils\JSON;
use App\General\Infrastructure\ValueObject\SymfonyUser;
use App\Notification\Infrastructure\Repository\MailjetTemplateRepository;
use Closure;
use Exception;
use JsonException;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @package App\Notification
 */
#[AsController]
#[OA\Tag(name: 'Notification')]
readonly class TemplatesController
{
    public function __construct(
        private SerializerInterface $serializer,
        private CacheInterface $cache,
        private MailjetTemplateRepository $templateRepository
    ) {
    }

    /**
     * Get current user blog data, accessible only for 'IS_AUTHENTICATED_FULLY' users
     *
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws ExceptionInterface
     * @return JsonResponse
     */
    #[Route(path: '/v1/templates', name: 'public_templates_index', methods: [Request::METHOD_GET])]
    #[Cache(smaxage: 10)]
    public function __invoke(SymfonyUser $symfonyUser): JsonResponse
    {
        $cacheKey = 'public_templates';
        $blogs = $this->cache->get($cacheKey, fn (ItemInterface $item) => $this->getClosure()($item));
        $output = JSON::decode(
            $this->serializer->serialize(
                $blogs,
                'json',
                [
                    'groups' => 'MailjetTemplate',
                ]
            ),
            true,
        );
        return new JsonResponse($output);
    }

    /**
     *
     * @return Closure
     */
    private function getClosure(): Closure
    {
        return function (ItemInterface $item): array {
            $item->expiresAfter(3600);

            return $this->getFormattedPosts();
        };
    }

    /**
     * @throws Exception
     */
    private function getFormattedPosts(): array
    {
        return $this->getBlogs();
    }

    /**
     * @return array
     */
    private function getBlogs(): array
    {
        return $this->templateRepository->findAll();
    }
}
