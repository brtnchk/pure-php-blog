<?php

declare(strict_types=1);

namespace Tests\Category;

use App\Article\ArticleService;
use App\Category\CategoryRepositoryInterface;
use App\Category\CategoryService;
use PHPUnit\Framework\TestCase;

final class CategoryServiceTest extends TestCase
{
    public function testBuildHomeSectionsReturnsEmptyWhenNoCategoriesHaveArticles(): void
    {
        $catRepo = $this->createMock(CategoryRepositoryInterface::class);
        $catRepo->method('listWithArticles')->willReturn([]);

        $articleSvc = $this->createMock(ArticleService::class);
        $articleSvc->expects(self::never())->method('topInCategories');

        $svc = new CategoryService($catRepo, $articleSvc);

        self::assertSame([], $svc->buildHomeSections(3));
    }

    public function testBuildHomeSectionsAttachesArticlesByCategoryId(): void
    {
        $catRepo = $this->createMock(CategoryRepositoryInterface::class);
        $catRepo->method('listWithArticles')->willReturn([
            ['id' => 1, 'name' => 'A', 'slug' => 'a', 'description' => null],
            ['id' => 2, 'name' => 'B', 'slug' => 'b', 'description' => null],
        ]);

        $articleSvc = $this->createMock(ArticleService::class);
        $articleSvc->expects(self::once())
            ->method('topInCategories')
            ->with([1, 2], 3)
            ->willReturn([
                1 => [['id' => 10, 'title' => 'A1']],
                2 => [['id' => 20, 'title' => 'B1'], ['id' => 21, 'title' => 'B2']],
            ]);

        $sections = (new CategoryService($catRepo, $articleSvc))->buildHomeSections(3);

        self::assertCount(2, $sections);
        self::assertSame('A', $sections[0]['category']['name']);
        self::assertSame(10, $sections[0]['articles'][0]['id']);
        self::assertCount(2, $sections[1]['articles']);
    }

    public function testBuildHomeSectionsTolersatesMissingArticlesForACategory(): void
    {
        $catRepo = $this->createMock(CategoryRepositoryInterface::class);
        $catRepo->method('listWithArticles')->willReturn([
            ['id' => 1, 'name' => 'A', 'slug' => 'a', 'description' => null],
            ['id' => 2, 'name' => 'B', 'slug' => 'b', 'description' => null],
        ]);

        $articleSvc = $this->createMock(ArticleService::class);
        $articleSvc->method('topInCategories')->willReturn([
            1 => [['id' => 10]],
            // 2 missing on purpose
        ]);

        $sections = (new CategoryService($catRepo, $articleSvc))->buildHomeSections(3);

        self::assertSame([], $sections[1]['articles']);
    }

    public function testGetCategoryViewReturnsNullForUnknownSlug(): void
    {
        $catRepo = $this->createMock(CategoryRepositoryInterface::class);
        $catRepo->method('findBySlug')->with('nope')->willReturn(null);

        $articleSvc = $this->createMock(ArticleService::class);
        $articleSvc->expects(self::never())->method('listForCategory');

        $svc = new CategoryService($catRepo, $articleSvc);

        self::assertNull($svc->getCategoryView('nope', null, null));
    }

    public function testGetCategoryViewBundlesPaginationAndSortConstants(): void
    {
        $catRepo = $this->createMock(CategoryRepositoryInterface::class);
        $catRepo->method('findBySlug')->with('php')->willReturn([
            'id' => 7, 'name' => 'PHP', 'slug' => 'php', 'description' => 'd',
        ]);

        $articleSvc = $this->createMock(ArticleService::class);
        $articleSvc->method('normalizeSort')->with('views')->willReturn('views');
        $articleSvc->expects(self::once())
            ->method('listForCategory')
            ->with(7, 'views', 2, 6)   // page='2' parsed as int 2; per_page=6 const
            ->willReturn([
                'items' => [['id' => 99, 'title' => 'X']],
                'total' => 1, 'pages' => 1, 'page' => 1, 'per_page' => 6,
            ]);

        $bundle = (new CategoryService($catRepo, $articleSvc))->getCategoryView('php', 'views', '2');

        self::assertNotNull($bundle);
        self::assertSame(7, $bundle['category']['id']);
        self::assertSame('views', $bundle['sort']);
        self::assertSame(1, $bundle['pagination']['page']);
        self::assertSame(1, $bundle['pagination']['total']);
        self::assertSame(6, $bundle['pagination']['per_page']);
        self::assertSame(ArticleService::SORT_DATE, $bundle['sort_date']);
        self::assertSame(ArticleService::SORT_VIEWS, $bundle['sort_views']);
    }

    public function testGetCategoryViewClampsNegativePageToOne(): void
    {
        $catRepo = $this->createMock(CategoryRepositoryInterface::class);
        $catRepo->method('findBySlug')->willReturn(['id' => 1, 'name' => 'X', 'slug' => 'x', 'description' => null]);

        $articleSvc = $this->createMock(ArticleService::class);
        $articleSvc->method('normalizeSort')->willReturn('date');
        $articleSvc->expects(self::once())
            ->method('listForCategory')
            ->with(1, 'date', 1, self::anything())   // -5 clamped to 1
            ->willReturn(['items' => [], 'total' => 0, 'pages' => 1, 'page' => 1, 'per_page' => 6]);

        (new CategoryService($catRepo, $articleSvc))->getCategoryView('x', null, '-5');
    }
}
