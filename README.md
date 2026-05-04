# Блог на чистом PHP + Smarty + MySQL

Тестовое задание: простой, но полностью рабочий блог с категориями и
статьями. Без фреймворков, всё своё: маршрутизатор, репозитории поверх
PDO, сервисный слой, шаблонизатор Smarty, SCSS-стили, Docker-окружение
и сидер.

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

Требуется PHP 8.2+ и доступ к MySQL.

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
├── public/                       # DocumentRoot
│   ├── index.php                 # bootstrap (autoload, конфиг, роуты)
│   ├── .htaccess                 # rewrite на index.php
│   ├── css/main.css              # скомпилированный SCSS
│   └── uploads/                  # картинки статей
├── routes/
│   └── web.php                   # таблица маршрутов (closure-регистратор)
├── app/                          # неймспейс App\, маппится 1:1 на папки
│   ├── Config/config.php         # плоская карта настроек, читает Env
│   ├── Core/
│   │   ├── Container.php         # composition root: PDO → repos → services
│   │   ├── Database.php          # PDO singleton
│   │   ├── Env.php               # загрузка .env, типизированный get/bool/int
│   │   ├── Router.php            # маршрутизатор с {param}-плейсхолдерами
│   │   ├── View.php              # обёртка над Smarty
│   │   └── Controller.php        # базовый: render(), notFound(), intParam()
│   ├── Article/
│   │   ├── ArticleRepository.php # только SQL
│   │   └── ArticleService.php    # бизнес-логика (sort, пагинация, view)
│   ├── Category/
│   │   ├── CategoryRepository.php
│   │   └── CategoryService.php   # сборка секций для главной
│   └── Controllers/              # тонкие: парсят request → зовут сервис
├── templates/                    # Smarty (.tpl) с {extends}
├── templates_c/                  # скомпилированные шаблоны Smarty
├── scss/                         # исходники стилей
├── database/
│   ├── schema.sql                # таблицы categories, articles, article_category
│   └── seed.php                  # CLI-сидер
└── docker/                       # nginx + php-fpm 8.2
```

## Архитектура

Слои разложены по ответственности:

- **Repository** (`app/<Entity>/<Entity>Repository.php`) — принимает `PDO`
  через конструктор, делает запросы, возвращает массивы. Никакой логики
  про сортировку, пагинацию или валидацию.
- **Service** (`app/<Entity>/<Entity>Service.php`) — принимает репозитории,
  складывает из них прикладные операции: нормализация sort-параметра,
  расчёт страниц, инкремент просмотров + сборка bundle для view, и т. п.
- **Controller** (`app/Controllers/`) — 5–10 строк: вытащить
  query-параметры, позвать сервис, вернуть `render(...)`.
- **Container** (`app/Core/Container.php`) — крошечный сервис-локатор,
  бутстрапится в `public/index.php` с уже созданным `PDO` и лениво
  собирает репозитории и сервисы. Контроллеры берут зависимости через
  него, не конструируя их вручную.

## Структура данных

- **categories** — `id, name, slug, description`.
- **articles** — `id, title, slug, description, content, image, views, published_at`.
- **article_category** — many-to-many связь (статья может состоять в нескольких категориях).

## Реализация требуемых страниц

### Главная (`/`)

`HomeController::index` зовёт `CategoryService::buildHomeSections(3)`,
которое отдаёт массив секций «категория → 3 последних статьи». Внутри:

- `CategoryRepository::listWithArticles()` — JOIN с pivot отбрасывает
  пустые категории;
- `ArticleRepository::recentByCategories($ids, 3)` — одним запросом
  достаёт топ-N в каждой категории через
  `ROW_NUMBER() OVER (PARTITION BY category_id ORDER BY published_at DESC)`.

Для каждой непустой категории выводится кнопка «Все статьи».

### Страница категории (`/category/{slug}`)

`CategoryController::show` дёргает:

- `CategoryService::findBySlug` — иначе 404;
- `ArticleService::listForCategory($id, $sort, $page, $perPage)` —
  нормализует sort через белый список (`date` / `views`),
  считает `total/pages/page`, отдаёт items.

Сортировка через `?sort=date|views`, пагинация через `?page=N`,
`per_page` в `pagination.per_page` (по умолчанию 6).

### Страница статьи (`/article/{slug}`)

`ArticleController::show` зовёт `ArticleService::getArticleView($slug)`,
которое:

- грузит статью (или возвращает `null` → 404);
- атомарно инкрементит `views`;
- грузит её категории и до 3 похожих статей.

Похожие — те, что делят хотя бы одну категорию с текущей; ранжируются
по `COUNT(DISTINCT shared_category)` и `published_at DESC`.

### 404

Любой неизвестный маршрут или несуществующий slug отдаёт `404.tpl`
со статусом `404`.

## Сидер

`php database/seed.php` — очищает таблицы и наполняет их 5 категориями
(одна намеренно остаётся пустой, чтобы убедиться, что главная её не
показывает) и 16 статьями со связями many-to-many.

## Что намеренно не сделано

- Сессии, админка, аутентификация — в задание не входят.
- Картинки статей опциональны (поле `image` nullable). Аплоадер не
  реализован: можно положить файл в `public/uploads/` и записать имя
  в БД руками.
- Контейнер реализован простейшим сервис-локатором; полноценный
  PSR-11/DI не требовался.

## Использование ИИ при выполнении

Да, использовал Claude Code в роли ассистента.

Что делал я (обдумывал, выбирал, проверял):

- архитектурные решения: связь many-to-many через pivot, разделение
  на repository/service/controller с модулями по сущностям, выбор
  алгоритма «3 последних на категорию» через `ROW_NUMBER()`,
  ранжирование похожих статей по числу общих категорий, отдельный
  атомарный `UPDATE` для счётчика просмотров;
- структура слоёв (Core / Article / Category / Controllers / templates),
  формат конфига, отдельный `App\Core\Env` для парсинга `.env`,
  вынос маршрутов в `routes/web.php`;
- e2e-проверка через PHP CLI с реальным MySQL (контейнер `mysql:8.0`,
  прогон сидера, рендер всех страниц, проверка сортировки/пагинации,
  блока похожих и инкремента просмотров).

Где конкретно помогал ИИ:

- бойлерплейт: первоначальные «скелеты» классов `Database`, `Router`,
  `View`, Smarty-шаблоны, docker-compose и nginx-конфиг (все потом
  перечитаны и доработаны);
- генерация демо-данных для сидера (заголовки и связи категорий);
- ускорение рутины: `.htaccess`, slugify-таблица, оформление SCSS.

Все архитектурные решения, SQL-запросы и логика сервисов — мои; ИИ
использовался как ускоритель набора и корректор синтаксиса.