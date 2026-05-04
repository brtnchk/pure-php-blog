<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Model
{
    protected PDO $db;

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo === null) {
            $config = require dirname(__DIR__) . '/Config/config.php';
            $pdo = Database::connection($config['db']);
        }
        $this->db = $pdo;
    }
}