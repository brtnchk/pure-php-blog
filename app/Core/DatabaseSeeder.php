<?php declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class DatabaseSeeder
{
    public function __construct(
        private PDO $db,
        private string $seedsDir,
    ) {
    }

    public function run(): void
    {
        $this->db->exec('SET FOREIGN_KEY_CHECKS = 0');

        try {
            $context = [];
            foreach ($this->discover() as $name => $file) {
                $seeder = require $file;
                if (!$seeder instanceof Seeder) {
                    throw new RuntimeException(
                        "Seed file {$file} must return an instance of " . Seeder::class
                    );
                }

                $context = array_replace($context, $seeder->run($this->db, $context));
            }
        } finally {
            $this->db->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    /** @return array<string, string>  name → absolute file path */
    private function discover(): array
    {
        if (!is_dir($this->seedsDir)) {
            return [];
        }

        $files = glob($this->seedsDir . '/*.php') ?: [];
        sort($files);

        $result = [];
        foreach ($files as $file) {
            $result[basename($file, '.php')] = $file;
        }

        return $result;
    }
}