<?php

declare(strict_types=1);

namespace App\Http;

class JsonResponse
{
    public function __construct(
        private array $payload,
        private int   $statusCode = 200
    ) {}

    public static function success(array $data = []): self
    {
        return new self(array_merge(['success' => true], $data));
    }

    public static function error(string $error, int $statusCode = 200): self
    {
        return new self(['success' => false, 'error' => $error], $statusCode);
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        header('Content-Type: application/json');
        echo json_encode($this->payload);
    }
}