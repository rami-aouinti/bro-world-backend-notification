<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entity\Enum;

/**
 * @package App\Notification\Domain\Entity\Enum
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
enum Scope: string
{
    case INDIVIDUAL = 'INDIVIDUAL';
    case GLOBAL = 'GLOBAL';
    case WORKPLACE = 'WORKPLACE';
    case SEGMENT = 'SEGMENT';
}
