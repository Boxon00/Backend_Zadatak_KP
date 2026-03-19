<?php

declare(strict_types=1);

namespace App\Database;

/**
 * Wraps a raw SQL expression so the QueryBuilder will NOT escape it.
 *
 * Usage:
 *   new Expression('NOW()')
 *   new Expression('NOW() - INTERVAL 10 DAY')
 */
class Expression
{
    public function __construct(private string $value) {}

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}