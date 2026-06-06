<?php

declare(strict_types=1);

namespace PhrameCMS\DoctrineDbalBridge;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PhrameCMS\Core\Contracts\DatabaseAdapterInterface;
use RuntimeException;

final class DoctrineDbalBridge implements DatabaseAdapterInterface
{
    private const DBAL_CONNECTION_CLASS = 'Doctrine\\DBAL\\Connection';
    private const DBAL_DRIVER_MANAGER_CLASS = 'Doctrine\\DBAL\\DriverManager';

    private Connection $connection;

    /**
     * @param array<string, mixed>|null $config
     */
    public function __construct(?array $config = null)
    {
        if (!self::isAvailable()) {
            throw new RuntimeException('Doctrine DBAL is unavailable in this environment.');
        }

        $this->connection = DriverManager::getConnection($config ?? self::resolveConfigFromEnv());
    }

    public static function isAvailable(): bool
    {
        return class_exists(self::DBAL_CONNECTION_CLASS)
            && class_exists(self::DBAL_DRIVER_MANAGER_CLASS);
    }

    /**
     * @param array<int|string, mixed> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->connection->executeStatement($sql, $params);
    }

    /**
     * @param array<int|string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
    }

    /**
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->connection->executeQuery($sql, $params)->fetchAssociative();

        if ($row === false) {
            return null;
        }

        return $row;
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollBack();
    }

    /**
     * @return array<string, mixed>
     */
    private static function resolveConfigFromEnv(): array
    {
        $driver = trim((string) (getenv('DB_DRIVER') ?: 'pdo_sqlite'));

        if ($driver === 'pdo_sqlite') {
            return [
                'driver' => 'pdo_sqlite',
                'path' => (string) (getenv('DB_PATH') ?: ':memory:'),
            ];
        }

        $host = (string) (getenv('DB_HOST') ?: '127.0.0.1');
        $port = (int) (getenv('DB_PORT') ?: ($driver === 'pdo_pgsql' ? '5432' : '3306'));
        $name = (string) (getenv('DB_NAME') ?: '');
        $user = (string) (getenv('DB_USER') ?: '');
        $password = (string) (getenv('DB_PASSWORD') ?: '');

        return [
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'dbname' => $name,
            'user' => $user,
            'password' => $password,
        ];
    }
}
