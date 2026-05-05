## Стек

- PHP 8.2+ (Docker-образ — `php:8.2-fpm-alpine`)
- MySQL 8 (используются оконные функции)
- Шаблонизатор Smarty 5
- Сборка стилей: SCSS (Dart Sass) → `public/css/main.css`
- Окружение: nginx + php-fpm + mysql через `docker compose`

## Быстрый старт через Docker

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec php php database/migrate.php   # создать таблицы
docker compose exec php php database/seed.php      # наполнить демо-данными
# открыть http://localhost:8080
```

## Качество кода

```bash
composer install                 # phpunit + phpstan + php-cs-fixer
composer fix                     # авто-форматирование
composer fix:check               # проверить без правок (для CI)
composer analyse                 # phpstan analyse
composer test                    # phpunit
```
