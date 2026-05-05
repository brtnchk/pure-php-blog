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
                    {$article.published_at|date_format:"d.m.Y"}
                </time>
                <span class="article__views" title="{$article.views} просмотров">
                    <svg class="icon" viewBox="0 0 24 24" width="16" height="16" fill="none"
                         stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    {$article.views}
                </span>
            </div>
        </header>

        {if $article.image}
            <figure class="article__image">
                <img src="{$app_url}/uploads/{$article.image}" alt="{$article.title}">
            </figure>
        {/if}

        <p class="article__lead">{$article.description}</p>

        <div class="article__body">
            {* nofilter: content is stored as raw HTML so paragraphs and inline
               formatting render. This is safe only as long as content is
               author-controlled. If a public-facing editor is added later,
               either sanitise on save or switch to a Markdown→HTML pipeline. *}
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