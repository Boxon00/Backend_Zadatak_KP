<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\RuleInterface;
use App\Fraud\MaxMindClientInterface;

class MaxMindRule implements RuleInterface
{
    public function __construct(
        private MaxMindClientInterface $maxMindClient,
        private string $ipAddress
    ) {}

    public function validate(mixed $value): ?string
    {
        if ($this->maxMindClient->isFraudulent((string)$value, $this->ipAddress)) {
            return 'fraud_detected';
        }

        return null;
    }
}