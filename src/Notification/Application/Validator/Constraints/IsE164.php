<?php

declare(strict_types=1);

namespace App\Notification\Application\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * @package App\Notification\Application\Validator\Constraints
 * @author Rami Aouinti <rami.aouinti@tkdeutschland.de>
 * @Annotation
 * @Target({"CLASS", "PROPERTY", "ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class IsE164 extends Constraint
{
    public const string INVALID_FORMAT_ERROR = 'c72e1bd9-d1d3-4841-abff-8f2a9ef6101e';
    public const string INVALID_TYPE_ERROR = '9f3a3b2a-50d7-4e13-b9b9-5d48e8c6a2b6';

    public string $message = 'The value "{{ value }}" is not a valid E.164 phone number.';
    public string $invalidTypeMessage = 'The value "{{ value }}" is not a valid string.';

    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }
}
