<?php

declare(strict_types=1);

namespace App\Database;

use RuntimeException;

class Connection
{
    private static ?self $instance = null;
    private \mysqli $link;

    private function __construct(
        string $host,
        string $user,
        string $password,
        string $database
    ) {
        $link = mysqli_connect($host, $user, $password, $database);

        if (!$link) {
            throw new RuntimeException('DB_error');
        }

        mysqli_set_charset($link, 'utf8mb4');
        $this->link = $link;
    }

    public static function getInstance(
        string $host,
        string $user,
        string $password,
        string $database
    ): self {
        if (self::$instance === null) {
            self::$instance = new self($host, $user, $password, $database);
        }

        return self::$instance;
    }

    public function getLink(): \mysqli
    {
        return $this->link;
    }

    public function escape(string $value): string
    {
        return mysqli_real_escape_string($this->link, $value);
    }

    public function query(string $sql): \mysqli_result|bool
    {
        $result = mysqli_query($this->link, $sql);

        if ($result === false) {
            throw new RuntimeException('Query failed: ' . mysqli_error($this->link));
        }

        return $result;
    }

    public function lastInsertId(): int
    {
        return (int) mysqli_insert_id($this->link);
    }
}