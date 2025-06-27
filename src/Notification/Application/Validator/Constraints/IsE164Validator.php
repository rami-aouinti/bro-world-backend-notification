<?php

declare(strict_types=1);

namespace App\Notification\Application\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use function is_string;

/**
 * @package App\Notification\Application\Validator\Constraints
 * @author Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
class IsE164Validator extends ConstraintValidator
{
    /**
     * @param            $value
     * @param Constraint $constraint
     *
     * @return void
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof IsE164) {
            throw new UnexpectedTypeException($constraint, IsE164::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value)) {
            $this->context->buildViolation($constraint->invalidTypeMessage)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(IsE164::INVALID_TYPE_ERROR)
                ->addViolation();

            return;
        }

        if (!preg_match('/^\+[1-9]\d{1,14}$/', $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(IsE164::INVALID_FORMAT_ERROR)
                ->addViolation();
        }
    }
}
