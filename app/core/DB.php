<?php
declare(strict_types=1);

/**
 * Thin PDO wrapper. All queries use prepared statements (SQL-injection safe).
 * Identifiers passed to insert()/update() are validated against a strict pattern.
 */
final class DB
{
    private static ?PDO $pdo = null;

    public static function connect(array $c): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], (int)($c['port'] ?? 3306), $c['name']);
        self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);
        self::$pdo->exec("SET time_zone = '" . date('P') . "'");
        return self::$pdo;
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            throw new RuntimeException('Database not connected');
        }
        return self::$pdo;
    }

    public static function q(string $sql, array $params = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::q($sql, $params)->fetchAll();
    }

    public static function row(string $sql, array $params = []): ?array
    {
        $r = self::q($sql, $params)->fetch();
        return $r === false ? null : $r;
    }

    public static function val(string $sql, array $params = []): mixed
    {
        $v = self::q($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    /** key => value pairs from first two columns */
    public static function pairs(string $sql, array $params = []): array
    {
        return self::q($sql, $params)->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public static function col(string $sql, array $params = []): array
    {
        return self::q($sql, $params)->fetchAll(PDO::FETCH_COLUMN);
    }

    private static function ident(string $name): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Invalid identifier: $name");
        }
        return "`$name`";
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_map([self::class, 'ident'], array_keys($data));
        $sql = 'INSERT INTO ' . self::ident($table) . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($data), '?')) . ')';
        self::q($sql, array_values($data));
        return (int)self::pdo()->lastInsertId();
    }

    /** INSERT ... ON DUPLICATE KEY UPDATE for the given update columns */
    public static function upsert(string $table, array $data, array $updateCols): int
    {
        $cols = array_map([self::class, 'ident'], array_keys($data));
        $upd = implode(',', array_map(fn($c) => self::ident($c) . '=VALUES(' . self::ident($c) . ')', $updateCols));
        $sql = 'INSERT INTO ' . self::ident($table) . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($data), '?')) . ") ON DUPLICATE KEY UPDATE $upd";
        return self::q($sql, array_values($data))->rowCount();
    }

    public static function update(string $table, array $data, string $where, array $params = []): int
    {
        $set = implode(',', array_map(fn($c) => self::ident($c) . '=?', array_keys($data)));
        $sql = 'UPDATE ' . self::ident($table) . " SET $set WHERE $where";
        return self::q($sql, array_merge(array_values($data), $params))->rowCount();
    }

    public static function delete(string $table, string $where, array $params): int
    {
        return self::q('DELETE FROM ' . self::ident($table) . " WHERE $where", $params)->rowCount();
    }

    public static function tx(callable $fn): mixed
    {
        $pdo = self::pdo();
        if ($pdo->inTransaction()) {
            return $fn();
        }
        $pdo->beginTransaction();
        try {
            $r = $fn();
            $pdo->commit();
            return $r;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Build "IN (?,?,?)" placeholder list */
    public static function in(array $values): string
    {
        return $values ? '(' . implode(',', array_fill(0, count($values), '?')) . ')' : '(NULL)';
    }
}
