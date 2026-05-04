<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{block name="title"}Блог{/block}</title>
    <link rel="stylesheet" href="{$app_url}/css/main.css">
</head>
<body>

<header class="site-header">
    <div class="container">
        <a class="site-header__brand" href="{$app_url}/">Мой блог</a>
        <nav class="site-header__nav">
            <a href="{$app_url}/">Главная</a>
        </nav>
    </div>
</header>

<main class="container site-main">
    {block name="content"}{/block}
</main>

<footer class="site-footer">
    <div class="container">
        <small>&copy; {$smarty.now|date_format:"%Y"} Блог на чистом PHP + Smarty</small>
    </div>
</footer>

</body>
</html>