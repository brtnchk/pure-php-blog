<?php declare(strict_types=1);

namespace Tests\Core;

use App\Article\ArticleRepository;
use App\Article\ArticleRepositoryInterface;
use App\Article\ArticleService;
use App\Category\CategoryRepository;
use App\Category\CategoryRepositoryInterface;
use App\Category\CategoryService;
use App\Core\Container;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ContainerTest extends TestCase
{
    private function makeContainer(): Container
    {
        $c = new Container();
        // PDO is the only thing autowiring can't resolve on its own.
        $c->bind(PDO::class, fn () => $this->createMock(PDO::class));
        $c->bind(ArticleRepositoryInterface::class,  fn (Container $c) => $c->get(ArticleRepository::class));
        $c->bind(CategoryRepositoryInterface::class, fn (Container $c) => $c->get(CategoryRepository::class));
        return $c;
    }

    public function testGetMemoizesInstances(): void
    {
        $c = $this->makeContainer();

        self::assertSame(
            $c->get(ArticleRepository::class),
            $c->get(ArticleRepository::class),
        );
    }

    public function testAutowiresFullGraphFromASinglePdoBinding(): void
    {
        $c = $this->makeContainer();

        $service = $c->get(ArticleService::class);

        self::assertInstanceOf(ArticleService::class, $service);
    }

    public function testResolvesInterfaceToBoundImplementation(): void
    {
        $c = $this->makeContainer();

        $repo = $c->get(ArticleRepositoryInterface::class);

        self::assertInstanceOf(ArticleRepository::class, $repo);
    }

    public function testCategoryServiceMemoizedAcrossCalls(): void
    {
        $c = $this->makeContainer();

        self::assertSame(
            $c->get(CategoryService::class),
            $c->get(CategoryService::class),
        );
    }

    public function testInterfaceAndConcreteResolveToTheSameRepoInstance(): void
    {
        // Sanity: the binding for the interface routes through the concrete,
        // so both lookups should hand out the same memoised object.
        $c = $this->makeContainer();

        self::assertSame(
            $c->get(ArticleRepositoryInterface::class),
            $c->get(ArticleRepository::class),
        );
    }

    public function testThrowsOnMissingClass(): void
    {
        $c = $this->makeContainer();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/does not exist/');

        /** @phpstan-ignore-next-line  intentionally non-existent */
        $c->get('App\\Doesnt\\Exist');
    }

    public function testRebindReplacesPreviouslyResolvedInstance(): void
    {
        $c = new Container();
        $c->bind('id', static fn () => new \stdClass());

        $first = $c->get('id');
        $c->bind('id', static fn () => new \stdClass());
        $second = $c->get('id');

        self::assertNotSame($first, $second);
    }
}