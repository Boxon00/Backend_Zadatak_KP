<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\RuleInterface;

class MinLengthRule implements RuleInterface
{
    public function __construct(private int $minLength) {}

    public function validate(mixed $value): ?string
    {
        if (mb_strlen((string)$value) < $this->minLength) {
            return 'min_length';
        }

        return null;
    }
}