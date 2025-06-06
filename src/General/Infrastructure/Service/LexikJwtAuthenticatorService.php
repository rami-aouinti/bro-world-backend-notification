<?php

declare(strict_types=1);

namespace App\General\Infrastructure\Service;

use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use App\General\Application\Service\AuthenticatorServiceInterface;
use App\General\Domain\Exception\AuthenticationException;
use App\General\Domain\ValueObject\UserId;
use App\General\Infrastructure\ValueObject\SymfonyUser;

/**
 * Class LexikJwtAuthenticatorService
 *
 * @package App\General\Infrastructure\Service
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
final class LexikJwtAuthenticatorService implements AuthenticatorServiceInterface, EventSubscriberInterface
{
    private ?string $userId = null;
    private ?string $fullName = null;
    private ?string $avatar = null;
    private ?array $roles = null;
    private ?string $pathRegexp = null;

    public function __construct(
        private readonly JWTTokenManagerInterface $tokenManager,
        private readonly TokenExtractorInterface $tokenExtractor,
        private readonly string $path
    ) {
        $this->configurePathRegexp();
    }

    public function getUserId(): ?UserId
    {
        return new UserId($this->userId);
    }

    public function getSymfonyUser(): ?SymfonyUser
    {
        return new SymfonyUser(
            $this->userId,
            $this->fullName,
            $this->avatar,
            $this->roles
        );
    }

    public function getToken(string $id): ?string
    {
        return $this->tokenManager->create(new SymfonyUser($id, '', '', '', []));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(KernelEvent $event): void
    {
        $uri = $event->getRequest()->getRequestUri();

        try {
            $payload = $this->extractTokenPayloadFromRequest($event->getRequest());
            $this->userId = $payload['id'];
            $this->fullName = $payload['firstName'] . ' ' . $payload['lastName'];
            $this->roles = $payload['roles'];
            $this->avatar = $payload['avatar'] ? '' : 'https://api.dicebear.com/7.x/avataaars/svg?seed=JonDoe';
        } catch (AuthenticationException $e) {
            if (preg_match($this->pathRegexp, $uri) > 0) {
                throw $e;
            }
        }
    }

    private function extractTokenPayloadFromRequest(Request $request): array
    {
        $token = $this->tokenExtractor->extract($request);
        $token = false === $token ? '' : $token;

        try {
            $payload = $this->tokenManager->parse($token);
            if (!$payload) {
                throw new AuthenticationException('Invalid JWT Token');
            }

            return $payload;
        } catch (JWTDecodeFailureException $e) {
            if (JWTDecodeFailureException::EXPIRED_TOKEN === $e->getReason()) {
                throw new AuthenticationException('Expired token');
            }

            throw new AuthenticationException('Invalid JWT Token');
        }
    }

    private function getUserIdClaim(array $payload): string
    {
        $idClaim = $this->tokenManager->getUserIdClaim();
        if (!isset($payload[$idClaim])) {
            throw new AuthenticationException(sprintf('Invalid payload "%s"', $idClaim));
        }

        return $payload[$idClaim];
    }

    private function configurePathRegexp(): void
    {
        $this->pathRegexp = '/'.str_replace('/', '\/', $this->path).'/';
        if (false === @preg_match($this->pathRegexp, '')) {
            throw new \LogicException(sprintf('Invalid path regexp "%s"', $this->path));
        }
    }
}
