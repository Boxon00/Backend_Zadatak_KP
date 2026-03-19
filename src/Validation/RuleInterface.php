<?php

declare(strict_types=1);

namespace App\Validation;

interface RuleInterface
{
    /**
     * Vraća null ako je vrednost validna,
     * ili string kod greške ako nije.
     */
    public function validate(mixed $value): ?string;
}