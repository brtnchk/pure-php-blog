<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Migrator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MigratorTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function safeNames(): array
    {
        return [
            'plain'           => ['create_users_table'],
            'date-prefixed'   => ['2026_05_05_120001_create_users_table'],
            'with-dashes'     => ['create-users-table'],
            'mixed'           => ['ABC_123-xyz'],
        ];
    }

    #[DataProvider('safeNames')]
    public function testAcceptsValidNames(string $name): void
    {
        Migrator::assertSafeName($name);
        // Reaching this point means no exception was thrown.
        $this->expectNotToPerformAssertions();
    }

    /** @return array<string, array{string}> */
    public static function unsafeNames(): array
    {
        return [
            'parent-dir'      => ['../../etc/passwd'],
            'leading-slash'   => ['/etc/passwd'],
            'subdir'          => ['migrations/foo'],
            'space'           => ['create users'],
            'dot'             => ['create.table'],
            'null-byte'       => ["foo\0bar"],
            'empty'           => [''],
            'newline'         => ["foo\nbar"],
        ];
    }

    #[DataProvider('unsafeNames')]
    public function testRejectsUnsafeNames(string $name): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Refusing to use migration name as a path/');

        Migrator::assertSafeName($name);
    }
}