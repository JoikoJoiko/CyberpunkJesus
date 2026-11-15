# Грехометр

Закрытый архив опросов игроков. Laravel 12, Blade, SQLite по умолчанию. Для интерфейса достаточно PHP и Composer.

## Что есть в архиве

- **Игроки.** Ник, дата опроса, примечание и ответы на текущие вопросы. «+» рядом с вопросом открывает необязательное уточнение. Можно оставить пустым любое поле или всю анкету. Поиск работает по нику и примечанию.
- **Вопросы.** Короткий или длинный текст, число, дата, «Да / Нет» и выбор из списка. Вопросы можно добавлять, редактировать, менять порядок и удалять. При удалении вопроса база сразу удаляет все его ответы. При смене типа или вариантов сохранение пройдёт, только если все существующие ответы подходят.
- **Обзор.** Число анкет, вопросов и ответов, график добавления анкет за 14 дней и последние анкеты.
- **Ответы.** Заполненность и частые ответы по выбранному вопросу. Для чисел есть минимум, среднее и максимум, для дат — диапазон.
- **История.** Новые изменения анкет и вопросов фиксируются с датой, временем, именем и почтой автора. В журнале указано, какие поля менялись. Тексты ответов и уточнений в журнал не копируются.
- В разделе «Вопросы» можно задать порядок блоков и отдельно расставить вопросы внутри каждого блока.
- Анкета открывается в печатном виде. Оттуда её можно отредактировать или скачать в PDF и DOCX формата A4, книжной ориентации.
- На главной можно создать несколько именованных диаграмм по ответам. Гистограмма строится по числовому вопросу, круговая и столбчатая диаграммы показывают частоты ответов.

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

### Быстрый запуск по IP

На VPS с Debian 12+ или Ubuntu 24.04+ и доступом по SSH скопируйте или клонируйте проект. В его папке выполните `sudo bash deploy.sh`. Скрипт устанавливает Apache и PHP, разворачивает сайт на [185.171.83.215](http://185.171.83.215/), применяет миграции и проверяет страницу входа. В панели хостинга должен быть доступен TCP-порт 80. Подключение по HTTP передаёт пароли и данные без шифрования.

Для каждого администратора выполните в `/var/www/grekhometr` команду `sudo -u www-data php artisan dossier:user адрес@почта --name="Имя"`. Для обновления повторите запуск скрипта из свежего checkout. База в `/var/lib/grekhometr`, файлы аватаров и настройки сохраняются. Перед повторным запуском миграций скрипт создаёт резервную копию SQLite.

### Ручная установка

1. Укажите корнем сайта каталог `public/`. PHP должен иметь права записи в `storage/` и `bootstrap/cache/`. Понадобятся PHP 8.2+, расширения Laravel и PDO-драйвер выбранной базы.
2. Выполните `composer install --no-dev --optimize-autoloader`. Создайте серверный `.env` из `.env.example` и задайте `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://ваш-домен` и `SESSION_SECURE_COOKIE=true`. Сгенерируйте ключ командой `php artisan key:generate` один раз при первоначальной настройке.
3. Для SQLite создайте пустой `database/database.sqlite` в каталоге, доступном PHP для записи. При наличии постоянного каталога на хостинге можно указать абсолютный путь к файлу в `DB_DATABASE`. Для MySQL вместо этого задайте `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` и `DB_PASSWORD`.
4. Выполните `php artisan migrate --force`. Для каждого администратора выполните `php artisan dossier:user ваш-адрес@домен --name="Ваше имя"` с отдельным адресом. При обновлении существующей установки повторяйте `php artisan migrate --force` после резервной копии базы.

[Документация Laravel по размещению](https://laravel.com/docs/12.x/deployment)

## Проверки

Выполните `php artisan test`. Тесты используют SQLite в памяти и проверяют доступ к архиву, работу форм, поиск и удаление связанных ответов.

## Иллюстрация

На всех страницах используется фон `public/assets/backgraound.webp`. Исходный портрет из предыдущей версии хранится в `public/assets/cyber-jesus.webp`.

> Create a cinematic photorealistic web app banner photograph, landscape 3:2. Traditional recognizable depiction of Jesus of Nazareth, Middle Eastern adult man with dark shoulder-length hair, full beard, white garment and charcoal hooded cloak, looking at the camera. Cyberpunk streets at night, thin glowing magenta circular halo, atmospheric neon-pink rain haze and faint analog grain, a few subtle vertical urban lights. Head and shoulders in the RIGHT third, head fully visible with padding at top and bottom. Left half nearly black fading into the background for overlay text. Palette matte black, deep eggplant, neon fuchsia; high-end editorial film still, subtly devotional, no lettering, no logo, no watermark.
