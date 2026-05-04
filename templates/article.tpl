{extends file="layout.tpl"}

{block name="title"}{$article.title} — Блог{/block}

{block name="content"}
    <nav class="breadcrumbs">
        <a href="{$app_url}/">Главная</a>
        {foreach $categories as $c}
            / <a href="{$app_url}/category/{$c.slug|escape:'url'}">{$c.name}</a>
        {/foreach}
    </nav>

    <article class="article">
        <header class="article__head">
            <h1 class="article__title">{$article.title}</h1>
            <div class="article__meta">
                <time datetime="{$article.published_at}">
                    {$article.published_at|date_format:"%d.%m.%Y"}
                </time>
                <span>{$article.views} просмотр.</span>
            </div>
        </header>

        {if $article.image}
            <figure class="article__image">
                <img src="{$app_url}/uploads/{$article.image}" alt="{$article.title}">
            </figure>
        {/if}

        <p class="article__lead">{$article.description}</p>

        <div class="article__body">
            {$article.content nofilter}
        </div>

        {if $categories|@count > 0}
            <footer class="article__foot">
                <span>Категории:</span>
                {foreach $categories as $c}
                    <a class="tag" href="{$app_url}/category/{$c.slug|escape:'url'}">{$c.name}</a>
                {/foreach}
            </footer>
        {/if}
    </article>

    {if $similar|@count > 0}
        <section class="similar">
            <h2 class="similar__title">Похожие статьи</h2>
            <div class="grid grid--3">
                {foreach $similar as $a}
                    {include file="_card.tpl" a=$a}
                {/foreach}
            </div>
        </section>
    {/if}
{/block}