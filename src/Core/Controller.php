<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function render(string $template, array $data = []): string
    {
        return View::render($template, $data);
    }

    protected function redirect(string $url, int $status = 302): void
    {
        header('Location: ' . $url, true, $status);
        exit;
    }

    protected function notFound(): string
    {
        http_response_code(404);
        return $this->render('404.tpl');
    }

    protected function intParam(?string $value, int $default, int $min = 1, ?int $max = null): int
    {
        $n = (int) ($value ?? $default);
        if ($n < $min) {
            $n = $min;
        }
        if ($max !== null && $n > $max) {
            $n = $max;
        }
        return $n;
    }
}