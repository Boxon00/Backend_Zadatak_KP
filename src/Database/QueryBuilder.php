<?php

declare(strict_types=1);

namespace App\Database;

use RuntimeException;

/**
 * Fluent query builder za SELECT, INSERT i UPDATE.
 *
 * Podržava Expression objekte kao vrednosti:
 *   new Expression('NOW()')
 *   new Expression('NOW() - INTERVAL 10 DAY')
 *
 * Svi skalari se escapuju putem mysqli_real_escape_string.
 *
 * Primer INSERT sa NOW():
 *   $qb->table('user')->insert([
 *       'email'    => 'user@example.com',
 *       'password' => 'hashed',
 *       'posted'   => new Expression('NOW()'),
 *   ]);
 *
 * Primer WHERE sa INTERVAL:
 *   $qb->table('user')
 *      ->where('posted', '>', new Expression('NOW() - INTERVAL 10 DAY'))
 *      ->get();
 */
class QueryBuilder
{
    private string $table      = '';
    private array  $columns    = ['*'];
    private array  $conditions = [];
    private ?int   $limitValue = null;

    public function __construct(private Connection $connection) {}

    // ── Table ────────────────────────────────────────────────

    public function table(string $table): static
    {
        $clone             = clone $this;
        $clone->table      = $table;
        $clone->conditions = [];
        $clone->columns    = ['*'];
        $clone->limitValue = null;

        return $clone;
    }

    // ── SELECT ───────────────────────────────────────────────

    public function select(array $columns = ['*']): static
    {
        $clone          = clone $this;
        $clone->columns = $columns;

        return $clone;
    }

    public function where(string $column, string $operator, mixed $value): static
    {
        $clone               = clone $this;
        $clone->conditions[] = [
            'column'   => $column,
            'operator' => $operator,
            'value'    => $value,
        ];

        return $clone;
    }

    public function limit(int $limit): static
    {
        $clone             = clone $this;
        $clone->limitValue = $limit;

        return $clone;
    }

    public function get(): array
    {
        $result = $this->connection->query($this->buildSelect());

        if ($result === true || $result === false) {
            return [];
        }

        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function first(): ?array
    {
        $rows = $this->limit(1)->get();

        return $rows[0] ?? null;
    }

    public function count(): int
    {
        $clone          = clone $this;
        $clone->columns = ['COUNT(*) as _count'];
        $result         = $this->connection->query($clone->buildSelect());

        if ($result === true || $result === false) {
            return 0;
        }

        $row = mysqli_fetch_assoc($result);

        return (int)($row['_count'] ?? 0);
    }

    // ── INSERT ───────────────────────────────────────────────

    /** @param array<string, mixed|Expression> $data */
    public function insert(array $data): int
    {
        if (empty($data)) {
            throw new RuntimeException('INSERT requires at least one column.');
        }

        $sql = sprintf(
            'INSERT INTO `%s` SET %s',
            $this->table,
            implode(', ', $this->buildSetParts($data))
        );

        $this->connection->query($sql);

        return $this->connection->lastInsertId();
    }

    // ── UPDATE ───────────────────────────────────────────────

    /** @param array<string, mixed|Expression> $data */
    public function update(array $data): int
    {
        if (empty($data)) {
            throw new RuntimeException('UPDATE requires at least one column.');
        }

        $sql   = sprintf('UPDATE `%s` SET %s', $this->table, implode(', ', $this->buildSetParts($data)));
        $where = $this->buildWhereParts();

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $this->connection->query($sql);

        return mysqli_affected_rows($this->connection->getLink());
    }

    // ── Private helpers ──────────────────────────────────────

    private function buildSelect(): string
    {
        $sql   = sprintf('SELECT %s FROM `%s`', implode(', ', $this->columns), $this->table);
        $where = $this->buildWhereParts();

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . $this->limitValue;
        }

        return $sql;
    }

    private function buildWhereParts(): array
    {
        $parts = [];

        foreach ($this->conditions as $c) {
            $parts[] = sprintf('`%s` %s %s', $c['column'], $c['operator'], $this->formatValue($c['value']));
        }

        return $parts;
    }

    private function buildSetParts(array $data): array
    {
        $parts = [];

        foreach ($data as $column => $value) {
            $parts[] = sprintf('`%s` = %s', $column, $this->formatValue($value));
        }

        return $parts;
    }

    private function formatValue(mixed $value): string
    {
        if ($value instanceof Expression) {
            return $value->getValue();
        }

        if ($value === null) {
            return 'NULL';
        }

        return "'" . $this->connection->escape((string)$value) . "'";
    }
}