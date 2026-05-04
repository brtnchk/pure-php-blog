{extends file="layout.tpl"}

{block name="title"}{$category.name} — Блог{/block}

{block name="content"}
    <nav class="breadcrumbs">
        <a href="{$app_url}/">Главная</a> / <span>{$category.name}</span>
    </nav>

    <header class="cat-header">
        <h1 class="page-title">{$category.name}</h1>
        {if $category.description}
            <p class="cat-header__desc">{$category.description}</p>
        {/if}
    </header>

    <form class="toolbar" method="get">
        <label for="sort">Сортировка:</label>
        <select id="sort" name="sort" onchange="this.form.submit()">
            <option value="{$sort_date}"  {if $sort === $sort_date}selected{/if}>По дате публикации</option>
            <option value="{$sort_views}" {if $sort === $sort_views}selected{/if}>По количеству просмотров</option>
        </select>
        <noscript><button type="submit" class="btn">Применить</button></noscript>
    </form>

    {if $articles|@count === 0}
        <p class="empty">В этой категории пока нет статей.</p>
    {else}
        <div class="grid grid--3">
            {foreach $articles as $a}
                {include file="_card.tpl" a=$a}
            {/foreach}
        </div>

        {if $pagination.pages > 1}
            <nav class="pagination">
                {for $p=1 to $pagination.pages}
                    {if $p === $pagination.page}
                        <span class="pagination__item pagination__item--active">{$p}</span>
                    {else}
                        <a class="pagination__item"
                           href="?sort={$sort|escape:'url'}&page={$p}">{$p}</a>
                    {/if}
                {/for}
            </nav>
        {/if}
    {/if}
{/block}