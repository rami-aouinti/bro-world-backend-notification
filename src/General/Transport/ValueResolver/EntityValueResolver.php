<?php

declare(strict_types=1);

namespace App\General\Transport\ValueResolver;

use App\General\Application\Resource\ResourceCollection;
use App\General\Application\Rest\Interfaces\RestFindOneResourceInterface;
use App\General\Domain\Entity\Interfaces\EntityInterface;
use Generator;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Throwable;

use function end;
use function explode;
use function is_string;
use function is_subclass_of;
use function Symfony\Component\String\u;

/**
 * Example how to use this within your controller;
 *
 *  #[Route(path: 'some_path_to_your_route/{user}/{apikey}')]
 *  public function someMethod(\App\User\Domain\Entity\User $user, \App\ApiKey\Domain\Entity\ApiKey $apikey): Response
 *  {
 *      ...
 *  }
 *
 * And when you make your request like `GET /some_path_to_your_route/_user_uuid_/_apikey_uuid`
 * you will get those entities to your controller method which are resolved automatically via
 * those entity resource classes.
 *
 * Only thing that you need check is that parameter in your `path` definition matches with
 * method argument name.
 *
 * @package App\General
 */
class EntityValueResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly ResourceCollection $resourceCollection,
    ) {
    }

    /**
     * With this we check following cases:
     *  1. Request parameter is a string (query, request, attributes)
     *  2. Argument type is subclass of EntityInterface
     *  3. Argument name is same as entity name (argument type) as in camel case format
     *  4. Our REST resource collection has resource for this entity
     *
     * Examples:
     *  public function __invoke(UserGroup $userGroup): JsonResponse    => Works
     *  public function __invoke(User $user): JsonResponse              => Works
     *  public function __invoke(UserGroup $UserGroup): JsonResponse    => Doesn't work
     *  public function __invoke(UserGroup $group): JsonResponse        => Doesn't work
     *  public function __invoke(User $requestUser): JsonResponse       => Doesn't work (another resolver does this)
     */
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        $bits = explode('\\', (string)$argument->getType());
        $entity = end($bits);

        return is_string($this->getUuid($argument, $request))
            && is_subclass_of((string)$argument->getType(), EntityInterface::class)
            && $argument->getName() === u($entity)->camel()->toString()
            && $this->resourceCollection->hasEntityResource($argument->getType(), RestFindOneResourceInterface::class);
    }

    /**
     * {@inheritdoc}
     *
     * @return Generator<EntityInterface|null>
     *
     * @throws Throwable
     */
    #[Override]
    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        if (!$this->supports($request, $argument)) {
            return [];
        }

        yield $this->resourceCollection /** @phpstan-ignore-line */
            ->getEntityResource((string)$argument->getType(), RestFindOneResourceInterface::class)
            ->findOne((string)($this->getUuid($argument, $request)), !$argument->isNullable());
    }

    private function getUuid(ArgumentMetadata $argument, Request $request): mixed
    {
        $argumentName = $argument->getName();

        return $request->attributes->get($argumentName)
            ?? $request->request->get($argumentName)
            ?? $request->query->get($argumentName);
    }
}
