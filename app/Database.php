<?php
declare(strict_types=1);

/**
 * Thin PDO wrapper. All queries go through prepared statements.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connect(array $c): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $c['host'] ?? 'localhost',
            (int)($c['port'] ?? 3306),
            $c['name']
        );
        try {
            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(503);
            header('X-Robots-Tag: noindex, nofollow');
            // Show the reason (wrong password / unknown database …) only in debug mode.
            exit('Could not connect to the database. Check the details in config/config.php'
                . (APP_DEBUG ? ' – ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') : '.'));
        }

        // Make sure the SQL file has been imported.
        try {
            self::$pdo->query('SELECT 1 FROM sections LIMIT 1');
        } catch (PDOException $e) {
            http_response_code(503);
            exit('Connected to the database, but the tables are missing. Import database.sql in phpMyAdmin.');
        }
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            throw new RuntimeException('Database not connected');
        }
        return self::$pdo;
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = [])
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function lastId(): int
    {
        return (int)self::pdo()->lastInsertId();
    }
}
