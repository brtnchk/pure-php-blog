<?php declare(strict_types=1);

use App\Controllers\ArticleController;
use App\Controllers\CategoryController;
use App\Controllers\HomeController;
use App\Core\Router;

return static function (Router $router): void {
    $router->get('/', [HomeController::class, 'index']);
    $router->get('/category/{slug}', [CategoryController::class, 'show']);
    $router->get('/article/{slug}', [ArticleController::class, 'show']);
};