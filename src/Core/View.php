<?php

declare(strict_types=1);

namespace App\Core;

use Smarty\Smarty;
use RuntimeException;

final class View
{
    private static ?Smarty $smarty = null;

    public static function smarty(array $config): Smarty
    {
        if (self::$smarty instanceof Smarty) {
            return self::$smarty;
        }

        if (!class_exists(Smarty::class)) {
            throw new RuntimeException('Smarty is not installed. Run "composer install".');
        }

        $smarty = new Smarty();
        $smarty->setTemplateDir($config['paths']['templates']);
        $smarty->setCompileDir($config['paths']['compile']);
        $smarty->setCacheDir($config['paths']['cache']);
        $smarty->caching = Smarty::CACHING_OFF;
        $smarty->setEscapeHtml(true);

        $smarty->assign('app_url', $config['app']['url']);
        $smarty->assign('app_debug', $config['app']['debug']);

        self::$smarty = $smarty;
        return $smarty;
    }

    public static function render(string $template, array $data = []): string
    {
        $smarty = self::$smarty;
        if ($smarty === null) {
            throw new RuntimeException('Smarty not bootstrapped');
        }
        foreach ($data as $key => $value) {
            $smarty->assign($key, $value);
        }
        return $smarty->fetch($template);
    }
}