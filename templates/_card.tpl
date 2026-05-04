<article class="card">
    {if $a.image}
        <a class="card__image" href="{$app_url}/article/{$a.slug|escape:'url'}">
            <img src="{$app_url}/uploads/{$a.image}" alt="{$a.title}">
        </a>
    {/if}
    <div class="card__body">
        <h3 class="card__title">
            <a href="{$app_url}/article/{$a.slug|escape:'url'}">{$a.title}</a>
        </h3>
        <p class="card__desc">{$a.description}</p>
        <div class="card__meta">
            <time datetime="{$a.published_at}">{$a.published_at|date_format:"d.m.Y"}</time>
            <span class="card__views">{$a.views} просмотр.</span>
        </div>
    </div>
</article>