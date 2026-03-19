<?php

declare(strict_types=1);

namespace App\Validation;

class Validator
{
    /** @param array<string, RuleInterface[]> $fieldRules */
    public function __construct(private array $fieldRules) {}

    /**
     * Validira podatke prema konfiguriranim pravilima.
     * Vraća array field => prvi_kod_greške.
     *
     * @return array<string, string>
     */
    public function validate(array $data): array
    {
        $errors = [];

        foreach ($this->fieldRules as $field => $rules) {
            $value = $data[$field] ?? null;

            foreach ($rules as $rule) {
                $error = $rule->validate($value);
                if ($error !== null) {
                    $errors[$field] = $error;
                    break; // samo prva greška po polju
                }
            }
        }

        return $errors;
    }
}