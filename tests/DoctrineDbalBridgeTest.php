<?php

declare(strict_types=1);

namespace PhrameCMS\DoctrineDbalBridge\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use PhrameCMS\DoctrineDbalBridge\DoctrineDbalBridge;

final class DoctrineDbalBridgeTest extends TestCase
{
    public function testBridgeExecutesQueriesAndFetchesRows(): void
    {
        if (!DoctrineDbalBridge::isAvailable()) {
            self::markTestSkipped('Doctrine DBAL is unavailable in this environment.');
        }

        if (!self::hasSqlitePdoDriver()) {
            self::markTestSkipped('pdo_sqlite is unavailable in this environment.');
        }

        $bridge = new DoctrineDbalBridge([
            'driver' => 'pdo_sqlite',
            'path' => ':memory:',
        ]);

        $bridge->execute('CREATE TABLE pages (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL)');

        self::assertSame(1, $bridge->execute('INSERT INTO pages (title) VALUES (?)', ['Home']));
        self::assertSame(1, $bridge->execute('INSERT INTO pages (title) VALUES (?)', ['About']));

        $rows = $bridge->fetchAll('SELECT id, title FROM pages ORDER BY id ASC');

        self::assertCount(2, $rows);
        self::assertSame('Home', $rows[0]['title']);
        self::assertSame('About', $rows[1]['title']);

        $row = $bridge->fetchOne('SELECT id, title FROM pages WHERE title = ?', ['About']);

        self::assertNotNull($row);
        self::assertSame('About', $row['title']);
    }

    public function testTransactionsCanRollback(): void
    {
        if (!DoctrineDbalBridge::isAvailable()) {
            self::markTestSkipped('Doctrine DBAL is unavailable in this environment.');
        }

        if (!self::hasSqlitePdoDriver()) {
            self::markTestSkipped('pdo_sqlite is unavailable in this environment.');
        }

        $bridge = new DoctrineDbalBridge([
            'driver' => 'pdo_sqlite',
            'path' => ':memory:',
        ]);

        $bridge->execute('CREATE TABLE logs (id INTEGER PRIMARY KEY AUTOINCREMENT, message TEXT NOT NULL)');

        $bridge->beginTransaction();
        $bridge->execute('INSERT INTO logs (message) VALUES (?)', ['temp']);
        $bridge->rollback();

        $rows = $bridge->fetchAll('SELECT id, message FROM logs');

        self::assertSame([], $rows);
    }

    private static function hasSqlitePdoDriver(): bool
    {
        if (!extension_loaded('pdo_sqlite')) {
            return false;
        }

        return in_array('sqlite', PDO::getAvailableDrivers(), true);
    }
}
