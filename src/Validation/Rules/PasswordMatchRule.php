<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\RuleInterface;

class PasswordMatchRule implements RuleInterface
{
    public function __construct(private ?string $password) {}

    public function validate(mixed $value): ?string
    {
        if ($value !== $this->password) {
            return 'password_mismatch';
        }

        return null;
    }
}