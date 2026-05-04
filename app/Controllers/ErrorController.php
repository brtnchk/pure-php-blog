<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class ErrorController extends Controller
{
    public function notFound(): string
    {
        http_response_code(404);
        return $this->render('404.tpl');
    }
}