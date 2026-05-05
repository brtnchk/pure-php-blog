<?php declare(strict_types=1);

namespace Tests\Article;

use App\Article\ArticleRepositoryInterface;
use App\Article\ArticleService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ArticleServiceTest extends TestCase
{
    private function svc(?ArticleRepositoryInterface $repo = null): ArticleService
    {
        return new ArticleService($repo ?? $this->createMock(ArticleRepositoryInterface::class));
    }

    /** @return array<string, array{string, string}> */
    public static function knownSorts(): array
    {
        return [
            'date' => ['date', 'date'],
            'views' => ['views', 'views'],
        ];
    }

    #[DataProvider('knownSorts')]
    public function testNormalizeSortAcceptsKnownValues(string $input, string $expected): void
    {
        self::assertSame($expected, $this->svc()->normalizeSort($input));
    }

    /** @return array<string, array{?string}> */
    public static function unknownSorts(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'garbage' => ['haxx'],
            'sqli-attempt'=> ['1; DROP TABLE articles --'],
            'wrong-case' => ['DATE'],
        ];
    }

    #[DataProvider('unknownSorts')]
    public function testNormalizeSortFallsBackToDate(?string $input): void
    {
        self::assertSame(ArticleService::SORT_DATE, $this->svc()->normalizeSort($input));
    }

    public function testListForCategoryComputesPaginationMath(): void
    {
        $repo = $this->createMock(ArticleRepositoryInterface::class);
        $repo->method('countByCategory')->with(7)->willReturn(15);
        $repo->method('listByCategory')
            ->with(7, 'date', 6, 0)
            ->willReturn([['id' => 1]]);

        $r = $this->svc($repo)->listForCategory(7, 'date', 1, 6);

        self::assertSame(15, $r['total']);
        self::assertSame(3,  $r['pages']);
        self::assertSame(1,  $r['page']);
        self::assertSame(6,  $r['per_page']);
    }

    public function testListForCategoryClampsPageAboveMax(): void
    {
        $repo = $this->createMock(ArticleRepositoryInterface::class);
        $repo->method('countByCategory')->willReturn(15);

        $repo->expects(self::once())
            ->method('listByCategory')
            ->with(self::anything(), self::anything(), 6, 12)
            ->willReturn([]);

        $r = $this->svc($repo)->listForCategory(7, 'date', 99, 6);

        self::assertSame(3, $r['page']);
    }

    public function testListForCategoryClampsPageBelowOne(): void
    {
        $repo = $this->createMock(ArticleRepositoryInterface::class);
        $repo->method('countByCategory')->willReturn(15);
        $repo->expects(self::once())
            ->method('listByCategory')
            ->with(self::anything(), self::anything(), 6, 0)
            ->willReturn([]);

        $r = $this->svc($repo)->listForCategory(7, 'date', -10, 6);

        self::assertSame(1, $r['page']);
    }

    public function testListForCategorySkipsRepoQueryWhenEmpty(): void
    {
        $repo = $this->createMock(ArticleRepositoryInterface::class);
        $repo->method('countByCategory')->willReturn(0);
        $repo->expects(self::never())->method('listByCategory');

        $r = $this->svc($repo)->listForCategory(7, 'date', 1, 6);

        self::assertSame([], $r['items']);
        self::assertSame(0,  $r['total']);
        self::assertSame(1,  $r['pages']);   // max(1, ceil(0/6)) = 1
        self::assertSame(1,  $r['page']);
    }

    public function testListForCategoryNormalisesUnknownSortBeforeQuery(): void
    {
        $repo = $this->createMock(ArticleRepositoryInterface::class);
        $repo->method('countByCategory')->willReturn(1);
        $repo->expects(self::once())
            ->method('listByCategory')
            ->with(7, 'date', 6, 0)   // 'haxx' was normalised to 'date'
            ->willReturn([['id' => 1]]);

        $this->svc($repo)->listForCategory(7, 'haxx', 1, 6);
    }

    public function testGetArticleViewReturnsNullForUnknownSlug(): void
    {
        $repo = $this->createMock(ArticleRepositoryInterface::class);
        $repo->method('findBySlug')->with('nope')->willReturn(null);
        $repo->expects(self::never())->method('incrementViews');
        $repo->expects(self::never())->method('similar');

        self::assertNull($this->svc($repo)->getArticleView('nope'));
    }

    public function testGetArticleViewIncrementsViewsAndBundles(): void
    {
        $article = ['id' => 5, 'views' => 10, 'title' => 'Foo'];

        $repo = $this->createMock(ArticleRepositoryInterface::class);
        $repo->method('findBySlug')->with('foo')->willReturn($article);
        $repo->expects(self::once())->method('incrementViews')->with(5);
        $repo->method('categoriesOf')->with(5)->willReturn([['id' => 1, 'name' => 'A']]);
        $repo->method('similar')->with(5, 3)->willReturn([['id' => 9]]);

        $bundle = $this->svc($repo)->getArticleView('foo');

        self::assertNotNull($bundle);
        self::assertSame(11, $bundle['article']['views']);   // pre-incremented in PHP
        self::assertSame('Foo', $bundle['article']['title']);
        self::assertCount(1, $bundle['categories']);
        self::assertCount(1, $bundle['similar']);
    }

    public function testGetArticleViewRespectsCustomSimilarLimit(): void
    {
        $repo = $this->createMock(ArticleRepositoryInterface::class);
        $repo->method('findBySlug')->willReturn(['id' => 5, 'views' => 0]);
        $repo->method('categoriesOf')->willReturn([]);
        $repo->expects(self::once())->method('similar')->with(5, 7)->willReturn([]);

        $this->svc($repo)->getArticleView('foo', 7);
    }

    public function testTopInCategoriesDelegatesToRepo(): void
    {
        $repo = $this->createMock(ArticleRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('recentByCategories')
            ->with([1, 2], 3)
            ->willReturn([1 => [['id' => 10]]]);

        $r = $this->svc($repo)->topInCategories([1, 2], 3);

        self::assertArrayHasKey(1, $r);
    }
}