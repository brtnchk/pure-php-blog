<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use RuntimeException;

/**
 * Forward-only migration runner with rollback support.
 *
 *  - migrate()  applies every file from $migrationsDir whose name is not yet
 *               in the `migrations` registry table; all migrations of a
 *               single run share the same batch number.
 *  - rollback() reverts the most recent batch (in reverse order of insertion).
 *  - fresh()    rolls everything back, then re-applies all migrations.
 *  - status()   reports applied/pending state of every known migration.
 *
 * Migration files must `return new class implements App\Core\Migration {...};`.
 */
final class Migrator
{
    public function __construct(
        private PDO $db,
        private string $migrationsDir,
    ) {}

    public function migrate(): int
    {
        $this->ensureRegistry();

        $pending = $this->pending();
        if ($pending === []) {
            echo "Nothing to migrate.\n";
            return 0;
        }

        $batch = $this->nextBatch();
        foreach ($pending as $name => $file) {
            $this->load($file)->up($this->db);
            $this->register($name, $batch);
            echo "  ↑ {$name}\n";
        }
        return count($pending);
    }

    public function rollback(): int
    {
        $this->ensureRegistry();

        $latest = $this->lastBatchMigrations();
        if ($latest === []) {
            echo "Nothing to roll back.\n";
            return 0;
        }

        foreach (array_reverse($latest) as $name) {
            self::assertSafeName($name);
            $file = $this->migrationsDir . '/' . $name . '.php';
            if (!is_file($file)) {
                throw new RuntimeException("Migration file missing: {$file}");
            }
            $this->load($file)->down($this->db);
            $this->unregister($name);
            echo "  ↓ {$name}\n";
        }
        return count($latest);
    }

    /**
     * @return array<string, string> name → 'applied' | 'pending'
     */
    public function status(): array
    {
        $this->ensureRegistry();
        $applied = $this->appliedNames();
        $report  = [];
        foreach ($this->discoverAll() as $name => $_file) {
            $report[$name] = isset($applied[$name]) ? 'applied' : 'pending';
        }
        return $report;
    }

    public function fresh(): int
    {
        $this->ensureRegistry();
        /** @var list<string> $applied */
        $applied = $this->query('SELECT name FROM migrations ORDER BY id DESC')
                        ->fetchAll(PDO::FETCH_COLUMN);

        foreach ($applied as $name) {
            self::assertSafeName($name);
            $file = $this->migrationsDir . '/' . $name . '.php';
            if (is_file($file)) {
                $this->load($file)->down($this->db);
            }
            echo "  ↓ {$name}\n";
        }
        $this->db->exec('TRUNCATE TABLE migrations');
        return $this->migrate();
    }

    /**
     * Exposed so it can be unit-tested directly. Pure function on a string —
     * if it ever grows logic, factor it out into a MigrationName VO.
     */
    public static function assertSafeName(string $name): void
    {
        if (preg_match('/^[A-Za-z0-9_-]+$/', $name) !== 1) {
            throw new RuntimeException("Refusing to use migration name as a path: {$name}");
        }
    }

    private function ensureRegistry(): void
    {
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                batch INT UNSIGNED NOT NULL,
                ran_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_migrations_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );
    }

    /** @return array<string, string>  pending name → file path */
    private function pending(): array
    {
        $applied = $this->appliedNames();

        return array_diff_key($this->discoverAll(), $applied);
    }

    /** @return array<string, string>  name → absolute file path */
    private function discoverAll(): array
    {
        if (!is_dir($this->migrationsDir)) {
            return [];
        }

        $files = glob($this->migrationsDir . '/*.php') ?: [];
        sort($files);
        $result = [];

        foreach ($files as $file) {
            $result[basename($file, '.php')] = $file;
        }

        return $result;
    }

    /** @return array<string, true>  set of names already applied */
    private function appliedNames(): array
    {
        /** @var list<string> $names */
        $names = $this->query('SELECT name FROM migrations')->fetchAll(PDO::FETCH_COLUMN);

        return array_fill_keys($names, true);
    }

    private function nextBatch(): int
    {
        $stmt = $this->query('SELECT COALESCE(MAX(batch), 0) FROM migrations');
        return ((int) $stmt->fetchColumn()) + 1;
    }

    /** @return list<string> */
    private function lastBatchMigrations(): array
    {
        $stmt = $this->query('SELECT COALESCE(MAX(batch), 0) FROM migrations');
        $batch = (int) $stmt->fetchColumn();

        if ($batch === 0) {
            return [];
        }

        $stmt = $this->db->prepare('SELECT name FROM migrations WHERE batch = :b ORDER BY id ASC');
        $stmt->execute(['b' => $batch]);

        /** @var list<string> */
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Executes a query and returns the statement.
     *
     * Under PDO::ERRMODE_EXCEPTION (which we always use), PDO::query() never
     * returns false — it throws on failure. The runtime guard is kept so the
     * static analyser doesnt have to take that on faith.
     */
    private function query(string $sql): PDOStatement
    {
        $stmt = $this->db->query($sql);
        if (!$stmt instanceof PDOStatement) {
            throw new RuntimeException("Query failed: {$sql}");
        }
        return $stmt;
    }

    private function register(string $name, int $batch): void
    {
        $stmt = $this->db->prepare('INSERT INTO migrations (name, batch) VALUES (:n, :b)');
        $stmt->execute(['n' => $name, 'b' => $batch]);
    }

    private function unregister(string $name): void
    {
        $stmt = $this->db->prepare('DELETE FROM migrations WHERE name = :n');
        $stmt->execute(['n' => $name]);
    }

    private function load(string $file): Migration
    {
        $migration = require $file;

        if (!$migration instanceof Migration) {
            throw new RuntimeException(
                "Migration file {$file} must return an instance of " . Migration::class,
            );
        }
        return $migration;
    }
}
