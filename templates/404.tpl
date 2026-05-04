{extends file="layout.tpl"}

{block name="title"}Страница не найдена{/block}

{block name="content"}
    <div class="not-found">
        <h1>404</h1>
        <p>Запрошенная страница не найдена.</p>
        <a class="btn btn--primary" href="{$app_url}/">На главную</a>
    </div>
{/block}