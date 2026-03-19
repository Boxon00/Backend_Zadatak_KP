<?php

declare(strict_types=1);

namespace App\Logger;

use App\Database\QueryBuilder;
use App\Database\Expression;

class UserLogger
{
    public function __construct(private QueryBuilder $queryBuilder) {}

    public function log(int $userId, string $action): void
    {
        $this->queryBuilder
            ->table('user_log')
            ->insert([
                'action'   => $action,
                'user_id'  => $userId,
                'log_time' => new Expression('NOW()'),
            ]);
    }
}