<?php

declare(strict_types=1);

namespace App\Fraud;

interface MaxMindClientInterface
{
    /**
     * Vraća true ako je kombinacija email/IP smatrana prevarnom.
     */
    public function isFraudulent(string $email, string $ipAddress): bool;
}