<?php

declare(strict_types=1);

namespace App\Blog\Transport\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Psr\Cache\CacheItemPoolInterface;
use App\Blog\Domain\Entity\Post;
use Psr\Cache\InvalidArgumentException;

/**
 * Class CacheInvalidationListener
 *
 * @package App\Blog\Transport\EventListener
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
class CacheInvalidationListener
{
    private CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Post) {
            return;
        }

        $cacheKey = 'application_posts_page_1';
        $this->cache->deleteItem($cacheKey);
    }
}
