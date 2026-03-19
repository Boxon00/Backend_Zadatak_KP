<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\RuleInterface;

class RequiredRule implements RuleInterface
{
    public function validate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return 'required';
        }

        return null;
    }
}