<?php declare(strict_types=1);

namespace App\Core;

use Smarty\Exception;

abstract class Controller
{
    /** @throws Exception */
    protected function render(string $template, array $data = []): string
    {
        return View::render($template, $data);
    }

    /** @throws Exception */
    protected function notFound(): string
    {
        http_response_code(404);

        return $this->render('404.tpl');
    }
}