<?php declare(strict_types=1);

namespace App\Controllers;

use App\Category\CategoryService;
use App\Core\Controller;
use Smarty\Exception;

final class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categories,
    ) {
    }

    /** @throws Exception */
    public function show(string $slug): string
    {
        $view = $this->categories->getCategoryView(
            $slug,
            $_GET['sort'] ?? null,
            $_GET['page'] ?? null,
        );

        return $view === null
            ? $this->notFound()
            : $this->render('category.tpl', $view);
    }
}