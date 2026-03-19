<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\QueryBuilder;
use App\Database\Expression;

class UserRepository
{
    public function __construct(private QueryBuilder $queryBuilder) {}

    public function emailExists(string $email): bool
    {
        return $this->queryBuilder
            ->table('user')
            ->where('email', '=', $email)
            ->count() > 0;
    }

    public function findByEmail(string $email): ?array
    {
        return $this->queryBuilder
            ->table('user')
            ->where('email', '=', $email)
            ->first();
    }

    /**
     * Kreira novog korisnika. Lozinka mora biti već hashirana.
     * INSERT sa Expression: 'posted' => new Expression('NOW()')
     */
    public function create(string $email, string $hashedPassword): int
    {
        return $this->queryBuilder
            ->table('user')
            ->insert([
                'email'    => $email,
                'password' => $hashedPassword,
                'posted'   => new Expression('NOW()'),
            ]);
    }

    /**
     * Korisnici registrovani u poslednjih 10 dana.
     * WHERE sa Expression: posted > NOW() - INTERVAL 10 DAY
     */
    public function findRecentUsers(): array
    {
        return $this->queryBuilder
            ->table('user')
            ->select(['id', 'email', 'posted'])
            ->where('posted', '>', new Expression('NOW() - INTERVAL 10 DAY'))
            ->get();
    }
}