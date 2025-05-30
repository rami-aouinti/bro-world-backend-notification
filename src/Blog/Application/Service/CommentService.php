<?php

declare(strict_types=1);

namespace App\Blog\Application\Service;

/**
 * Class CommentService
 *
 * @package App\Blog\Application\Service
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
readonly class CommentService
{
    public function __construct(
    ) {}

    /**
     * @param $comment
     * @param $usersById
     *
     * @return array
     */
    public function commentToArray($comment, $usersById): array {
        return [
            'id' => $comment->getId(),
            'content' => $comment->getContent(),
            'parent' => $comment->getParent()?->getId(),
            'children' => [],
            'medias' => $comment->getMedias(),
            'likes' => $comment->getLikes()->toArray(),
            'publishedAt' => $comment->getPublishedAt()->format('Y-m-d H:i:s'),
            'author' => $usersById[$comment->getAuthor()?->toString()] ?? null,
        ];
    }
}
