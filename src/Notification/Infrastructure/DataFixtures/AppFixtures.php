<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;

/**
 * @package App\DataFixtures
 * @author Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
class AppFixtures extends Fixture
{
    public function __construct()
    {
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
    }
}
