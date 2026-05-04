<?php declare(strict_types=1);

namespace App\Database;

use PDO;

interface Seeder
{
    /**
     * Inserts data into the DB.
     *
     * Files are loaded by the orchestrator in alphabetical order, so number
     * the seed files (01_, 02_, ...) when one needs ids produced by another.
     *
     * @param array<string, mixed> $context  values returned by previous seeders
     * @return array<string, mixed>          values to merge into the shared context
     */
    public function run(PDO $db, array $context): array;
}