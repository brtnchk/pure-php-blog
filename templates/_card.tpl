<article class="card">
    {if $a.image}
        <a class="card__image" href="{$app_url}/article/{$a.slug|escape:'url'}">
            <img src="{$app_url}/uploads/{$a.image}" alt="{$a.title}">
        </a>
    {else}
        <a class="card__image card__image--placeholder" href="{$app_url}/article/{$a.slug|escape:'url'}" aria-hidden="true"></a>
    {/if}

    <div class="card__body">
        <div class="card__meta">
            <time datetime="{$a.published_at}">{$a.published_at|date_format:"d.m.Y"}</time>
        </div>
        <h3 class="card__title">
            <a href="{$app_url}/article/{$a.slug|escape:'url'}">{$a.title}</a>
        </h3>
        <p class="card__desc">{$a.description}</p>
        <div class="card__footer">
            <a class="card__more" href="{$app_url}/article/{$a.slug|escape:'url'}">
                Подробнее <span aria-hidden="true">→</span>
            </a>
            <span class="card__views" title="{$a.views} просмотров">
                <svg class="icon" viewBox="0 0 24 24" width="14" height="14" fill="none"
                     stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                {$a.views}
            </span>
        </div>
    </div>
</article>