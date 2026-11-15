# Грехометр

Закрытый архив опросов игроков. Laravel 12, Blade, SQLite по умолчанию. Для интерфейса достаточно PHP и Composer.

## Что есть в архиве

- **Игроки.** Ник, сервер, дата опроса, примечание и ответы на текущие вопросы. Можно оставить пустым любое поле или всю анкету. Список поддерживает поиск, фильтр по серверу и сортировку по базовым полям или ответу на конкретный вопрос. Пустые значения идут в конец.
- **Вопросы.** Короткий или длинный текст, число, дата, «Да / Нет» и выбор из списка. Вопросы можно добавлять, редактировать, менять порядок и удалять. При удалении вопроса база сразу удаляет все его ответы. При смене типа или вариантов сохранение пройдёт, только если все существующие ответы подходят.
- **Обзор.** Число анкет, серверов, вопросов и ответов, график добавления анкет за 14 дней и список самых популярных серверов.
- **Ответы.** Заполненность и частые ответы по выбранному вопросу. Для чисел есть минимум, среднее и максимум, для дат — диапазон.
- **Аккаунты.** У участников одинаковый доступ к общему архиву. Следующий аккаунт создаётся в разделе «Аккаунты».

## Запуск локально

Нужны PHP 8.2+ с расширением `pdo_sqlite` и Composer. В PowerShell из корня проекта выполните:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File database/database.sqlite
php artisan migrate
php artisan dossier:user tester@dossier.test --name="Тестовый оператор"
php artisan serve
```

Откройте [локальный архив](http://127.0.0.1:8000). Команда `dossier:user` запрашивает пароль и подтверждение. Если в папке уже есть настроенные `.env` и `database/database.sqlite`, достаточно выполнить `php artisan serve`. Повторный `key:generate` сбросит действующие сессии.

В Linux и macOS вместо `Copy-Item` используйте `cp .env.example .env`, вместо `New-Item` — `touch database/database.sqlite`.

## Размещение на хостинге

1. Укажите корнем сайта каталог `public/`. PHP должен иметь права записи в `storage/` и `bootstrap/cache/`. Понадобятся PHP 8.2+, расширения Laravel и PDO-драйвер выбранной базы.
2. Выполните `composer install --no-dev --optimize-autoloader`. Создайте серверный `.env` из `.env.example` и задайте `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://ваш-домен` и `SESSION_SECURE_COOKIE=true`. Сгенерируйте ключ командой `php artisan key:generate` один раз при первоначальной настройке.
3. Для SQLite создайте пустой `database/database.sqlite` в каталоге, доступном PHP для записи. При наличии постоянного каталога на хостинге можно указать абсолютный путь к файлу в `DB_DATABASE`. Для MySQL вместо этого задайте `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` и `DB_PASSWORD`.
4. Выполните `php artisan migrate --force`, затем `php artisan dossier:user ваш-адрес@домен --name="Ваше имя"`. Второй аккаунт добавьте после входа. При обновлении существующей установки повторяйте `php artisan migrate --force` после резервной копии базы.

[Документация Laravel по размещению](https://laravel.com/docs/12.x/deployment)

## Проверки

Выполните `php artisan test`. Тесты используют SQLite в памяти и проверяют доступ к архиву, работу форм, сортировку и удаление связанных ответов.

## Иллюстрация

Портрет на страницах входа и обзора создан встроенным инструментом ImageGen. Файл находится в `public/assets/cyber-jesus.webp`.

> Create a cinematic photorealistic web app banner photograph, landscape 3:2. Traditional recognizable depiction of Jesus of Nazareth, Middle Eastern adult man with dark shoulder-length hair, full beard, white garment and charcoal hooded cloak, looking at the camera. Cyberpunk streets at night, thin glowing magenta circular halo, atmospheric neon-pink rain haze and faint analog grain, a few subtle vertical urban lights. Head and shoulders in the RIGHT third, head fully visible with padding at top and bottom. Left half nearly black fading into the background for overlay text. Palette matte black, deep eggplant, neon fuchsia; high-end editorial film still, subtly devotional, no lettering, no logo, no watermark.
