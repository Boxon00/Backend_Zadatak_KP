<?php

declare(strict_types=1);

namespace App\Http;

class Request
{
    public function __construct(
        private array  $data,
        private string $ip
    ) {}

    public static function fromGlobals(): self
    {
        return new self(
            $_REQUEST,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function getIp(): string
    {
        return $this->ip;
    }
}