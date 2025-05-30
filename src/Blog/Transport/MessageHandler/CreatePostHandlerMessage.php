<?php

declare(strict_types=1);

namespace App\Blog\Transport\MessageHandler;

use App\Blog\Application\Service\PostService;
use App\Blog\Domain\Message\CreatePostMessenger;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Class CreatePostHandlerMessage
 *
 * @package App\Post\Transport\MessageHandler
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AsMessageHandler]
readonly class CreatePostHandlerMessage
{
    public function __construct(
        private PostService $postService
    )
    {
    }

    /**
     * @param CreatePostMessenger $message
     *
     * @throws InvalidArgumentException
     * @throws ORMException
     * @throws OptimisticLockException
     * @return void
     */
    public function __invoke(CreatePostMessenger $message): void
    {
        $this->handleMessage($message);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws InvalidArgumentException
     */
    private function handleMessage(CreatePostMessenger $message): void
    {
        $this->postService->savePost($message->getPost(), $message->getMediasIds());
    }
}
