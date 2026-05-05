{extends file="layout.tpl"}

{block name="title"}Главная — Блог{/block}

{block name="content"}
    {if $sections|@count === 0}
        <p class="empty">Пока нет статей.</p>
    {/if}

    {foreach $sections as $section}
        {assign var="cat" value=$section.category}
        <section class="cat-section">
            <header class="cat-section__head">
                <h2 class="cat-section__title">
                    <a href="{$app_url}/category/{$cat.slug|escape:'url'}">{$cat.name}</a>
                </h2>
                <a class="cat-section__more"
                   href="{$app_url}/category/{$cat.slug|escape:'url'}">
                    Все статьи <span aria-hidden="true">→</span>
                </a>
            </header>

            <div class="grid grid--3">
                {foreach $section.articles as $a}
                    {include file="_card.tpl" a=$a}
                {/foreach}
            </div>
        </section>
    {/foreach}
{/block}