<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entity\Enum;

/**
 * @package App\Notification\Domain\Entity\Enum
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
enum Channel: string
{
    case SMS = 'Sms';
    case EMAIL = 'Email';
    case PUSH = 'Push';
}
