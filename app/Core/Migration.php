<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

interface Migration
{
    public function up(PDO $db): void;

    public function down(PDO $db): void;
}
