{extends file="layout.tpl"}

{block name="title"}Главная — Блог{/block}

{block name="content"}
    <h1 class="page-title">Последние статьи по категориям</h1>

    {if $sections|@count === 0}
        <p class="empty">Пока нет статей.</p>
    {/if}

    {foreach $sections as $section}
        {assign var="cat" value=$section.category}
        <section class="cat-section">
            <header class="cat-section__head">
                <div>
                    <h2 class="cat-section__title">
                        <a href="{$app_url}/category/{$cat.slug|escape:'url'}">{$cat.name}</a>
                    </h2>
                    {if $cat.description}
                        <p class="cat-section__desc">{$cat.description}</p>
                    {/if}
                </div>
                <a class="btn btn--primary"
                   href="{$app_url}/category/{$cat.slug|escape:'url'}">Все статьи</a>
            </header>

            <div class="grid grid--3">
                {foreach $section.articles as $a}
                    {include file="_card.tpl" a=$a}
                {/foreach}
            </div>
        </section>
    {/foreach}
{/block}