<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\RuleInterface;
use App\Repository\UserRepository;

class UniqueEmailRule implements RuleInterface
{
    public function __construct(private UserRepository $userRepository) {}

    public function validate(mixed $value): ?string
    {
        if ($this->userRepository->emailExists((string)$value)) {
            return 'email_taken';
        }

        return null;
    }
}