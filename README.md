# Блог на чистом PHP + Smarty + MySQL

Тестовое задание: простой, но полностью рабочий блог с категориями и статьями.
Без фреймворков, всё своё: маршрутизатор, тонкий слой моделей поверх PDO,
шаблонизатор Smarty, SCSS-стили, Docker-окружение и сидер.

## Стек

- PHP 8.1+ (тестировалось на 8.2)
- MySQL 8 (используются оконные функции)
- Шаблонизатор Smarty 4
- Сборка стилей: SCSS (Dart Sass) → `public/css/main.css`
- Окружение: nginx + php-fpm + mysql через `docker compose`

## Быстрый старт через Docker

```bash
cp .env.example .env
docker compose up -d --build
# схема накатывается автоматически из database/schema.sql при первом старте mysql
docker compose exec php php database/seed.php
# открыть http://localhost:8080
```

Если меняете SCSS:

```bash
npm install
npm run build       # либо: npm run watch
```

## Локальный запуск без Docker

Требуется PHP 8.1+ и доступ к MySQL.

```bash
composer install
mysql -u root -p < database/schema.sql
# Поправьте .env под локальные креды БД
php database/seed.php
php -S localhost:8080 -t public
# открыть http://localhost:8080
```

## Структура проекта

```
.
├── public/                  # точка входа (DocumentRoot)
│   ├── index.php            # bootstrap + роуты
│   ├── .htaccess            # rewrite на index.php
│   ├── css/main.css         # скомпилированный SCSS
│   └── uploads/             # картинки статей
├── src/
│   ├── autoload.php         # PSR-4 fallback (если без composer)
│   ├── Config/config.php    # конфиг + парсер .env
│   ├── Core/                # Database, Router, View(Smarty), Controller, Model
│   ├── Models/              # Category, Article
│   └── Controllers/         # Home, Category, Article, Error
├── templates/               # Smarty (.tpl)
├── templates_c/             # скомпилированные шаблоны Smarty
├── scss/                    # исходники стилей
├── database/
│   ├── schema.sql           # таблицы categories, articles, article_category
│   └── seed.php             # CLI-сидер
└── docker/                  # nginx + php-fpm 8.2
```

## Структура данных

- **categories** — `id, name, slug, description`.
- **articles** — `id, title, slug, description, content, image, views, published_at`.
- **article_category** — many-to-many связь (одна статья может состоять в нескольких категориях).

## Реализация требуемых страниц

### Главная (`/`)

`HomeController::index` загружает все категории, у которых **есть** статьи
(JOIN с pivot отбрасывает пустые), и одним запросом достаёт по 3 последних
публикации в каждой через MySQL-овую `ROW_NUMBER() OVER (PARTITION BY ...)`.
Для каждой выводится кнопка «Все статьи».

### Страница категории (`/category/{slug}`)

`CategoryController::show`:

- название и описание категории;
- сортировка через query-параметр `?sort=date|views`;
- пагинация через `?page=N`, размер страницы — `pagination.per_page` в конфиге (по умолчанию 6);
- одинаковый `<select>` отправляется на сервер при изменении (без JS-фреймворков).

### Страница статьи (`/article/{slug}`)

`ArticleController::show`:

- полная информация о статье и список её категорий;
- инкремент `views` атомарным `UPDATE`;
- блок из 3 похожих статей — берутся те, что делят категорию с текущей,
  ранжируются по числу совпавших категорий и дате.

### 404

Любой неизвестный маршрут или несуществующий slug отдаёт страницу `404.tpl`
со статусом `404`.

## Сидер

`php database/seed.php` — очищает таблицы и наполняет их 5 категориями
(одна намеренно остаётся пустой — чтобы убедиться, что главная не выводит
категории без статей) и 16 статьями со связями many-to-many.

## Что было намеренно сделано просто

- Никаких DI-контейнеров, фасадов и абстрактных репозиториев — модели тонкие.
- Сессии/админка/аутентификация в задание не входят, не делал.
- Картинки статей опциональны (поле `image` nullable). Аплоадер не реализован,
  можно положить файл в `public/uploads/` и записать имя в БД.

## Использование ИИ при выполнении

Да, использовал Claude Code в роли ассистента.

Что делал я (обдумывал, выбирал, проверял):

- архитектурные решения: связь many-to-many через pivot, выбор алгоритма
  «3 последних на категорию» через `ROW_NUMBER`, ранжирование похожих
  статей по числу общих категорий, отдельный атомарный UPDATE для счётчика;
- структура слоёв (Core / Models / Controllers / templates), формат конфига,
  парсер `.env`;
- тестовые сценарии и e2e-проверка через PHP CLI с реальным MySQL
  (контейнер mysql:8.0, прогон сидера, рендер всех страниц, проверка
  сортировки/пагинации и блока похожих).

Где конкретно помогал ИИ:

- бойлерплейт: первоначальные «скелеты» классов `Database`, `Router`, `View`,
  Smarty-шаблоны, docker-compose и nginx-конфиг (все потом перечитаны и
  доработаны под конкретные требования);
- генерация демо-данных для сидера (заголовки и связи категорий);
- ускорение рутины: `.htaccess`, slugify-таблица, оформление SCSS.

Все архитектурные решения, SQL-запросы и логика контроллеров — мои; ИИ
использовался как ускоритель набора и корректор синтаксиса.