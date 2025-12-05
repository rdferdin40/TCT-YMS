<?php
/**
 * TCT-YMS Database Connection Manager
 *
 * Provides a singleton PDO connection with query builder capabilities.
 */

namespace App;

use PDO;
use PDOException;
use Exception;

class Database
{
    private static ?PDO $instance = null;
    private static array $queryLog = [];
    private static bool $logging = false;

    /**
     * Get PDO instance (singleton)
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    /**
     * Connect to database
     */
    private static function connect(): void
    {
        $config = config('database.connections.mysql');

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            self::$instance = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $e) {
            logMessage('critical', 'Database connection failed', ['error' => $e->getMessage()]);
            throw new Exception('Database connection failed. Please check your configuration.');
        }
    }

    /**
     * Enable/disable query logging
     */
    public static function enableLogging(bool $enable = true): void
    {
        self::$logging = $enable;
    }

    /**
     * Get query log
     */
    public static function getQueryLog(): array
    {
        return self::$queryLog;
    }

    /**
     * Execute a raw query
     */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $start = microtime(true);
        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if (self::$logging) {
            self::$queryLog[] = [
                'sql' => $sql,
                'params' => $params,
                'time' => round((microtime(true) - $start) * 1000, 2),
            ];
        }

        return $stmt;
    }

    /**
     * Fetch all results
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Fetch single row
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Fetch single column value
     */
    public static function fetchColumn(string $sql, array $params = [], int $column = 0): mixed
    {
        return self::query($sql, $params)->fetchColumn($column);
    }

    /**
     * Insert and return last insert ID
     */
    public static function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        self::query($sql, array_values($data));

        return (int) self::getInstance()->lastInsertId();
    }

    /**
     * Update records
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";

        $params = array_merge(array_values($data), $whereParams);
        return self::query($sql, $params)->rowCount();
    }

    /**
     * Delete records
     */
    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return self::query($sql, $params)->rowCount();
    }

    /**
     * Count records
     */
    public static function count(string $table, string $where = '1=1', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        return (int) self::fetchColumn($sql, $params);
    }

    /**
     * Check if record exists
     */
    public static function exists(string $table, string $where, array $params = []): bool
    {
        return self::count($table, $where, $params) > 0;
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        return self::getInstance()->rollBack();
    }

    /**
     * Execute callback within transaction
     */
    public static function transaction(callable $callback): mixed
    {
        self::beginTransaction();

        try {
            $result = $callback();
            self::commit();
            return $result;
        } catch (Exception $e) {
            self::rollback();
            throw $e;
        }
    }
}

/**
 * Query Builder for fluent database queries
 */
class QueryBuilder
{
    private string $table;
    private array $select = ['*'];
    private array $where = [];
    private array $params = [];
    private array $orderBy = [];
    private array $joins = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private ?string $groupBy = null;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public static function table(string $table): self
    {
        return new self($table);
    }

    public function select(array|string $columns): self
    {
        $this->select = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->where[] = "{$column} {$operator} ?";
        $this->params[] = $value;
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) {
            $this->where[] = '1=0';
            return $this;
        }

        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->where[] = "{$column} IN ({$placeholders})";
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->where[] = "{$column} IS NULL";
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->where[] = "{$column} IS NOT NULL";
        return $this;
    }

    public function whereLike(string $column, string $pattern): self
    {
        $this->where[] = "{$column} LIKE ?";
        $this->params[] = $pattern;
        return $this;
    }

    public function whereBetween(string $column, mixed $start, mixed $end): self
    {
        $this->where[] = "{$column} BETWEEN ? AND ?";
        $this->params[] = $start;
        $this->params[] = $end;
        return $this;
    }

    public function whereRaw(string $sql, array $params = []): self
    {
        $this->where[] = $sql;
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = "INNER JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = "LEFT JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }

    public function groupBy(string $column): self
    {
        $this->groupBy = $column;
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function paginate(int $page, int $perPage = 25): self
    {
        $this->limit = $perPage;
        $this->offset = ($page - 1) * $perPage;
        return $this;
    }

    private function buildSql(): string
    {
        $sql = 'SELECT ' . implode(', ', $this->select) . ' FROM ' . $this->table;

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }

        if ($this->groupBy) {
            $sql .= ' GROUP BY ' . $this->groupBy;
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    public function get(): array
    {
        return Database::fetchAll($this->buildSql(), $this->params);
    }

    public function first(): ?array
    {
        $this->limit = 1;
        return Database::fetch($this->buildSql(), $this->params);
    }

    public function count(): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->table;

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }

        return (int) Database::fetchColumn($sql, $this->params);
    }

    public function sum(string $column): float
    {
        $sql = "SELECT SUM({$column}) FROM " . $this->table;

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }

        return (float) Database::fetchColumn($sql, $this->params);
    }

    public function avg(string $column): float
    {
        $sql = "SELECT AVG({$column}) FROM " . $this->table;

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }

        return (float) Database::fetchColumn($sql, $this->params);
    }

    public function pluck(string $column): array
    {
        $this->select = [$column];
        $results = $this->get();
        return array_column($results, $column);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function toSql(): string
    {
        return $this->buildSql();
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
