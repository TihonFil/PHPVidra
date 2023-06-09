# Скрипт для импорта и обновления видео контента на сайте (CMS Drupal 9)

## Описание

Скрипт `web_import.php` предназначен для импорта и обновления видео контента на сайте, используя внешний сервис Webcaster.

## Зависимости

Для работы скрипта требуется установленный и настроенный веб-сервер с поддержкой PHP и CMS Drupal 9.

## Установка

1. Скачайте скрипт и необходимые файлы с репозитория.

2. Разместите файлы скрипта (`web_import.php` и `web_import_functions.php`) в корневой директории вашего проекта Drupal.

3. Установите необходимые зависимости (если требуется), указанные в файле `web_import_functions.php`.

## Настройка

1. Откройте файл `web_import.php` в текстовом редакторе.

2. Проверьте и, при необходимости, измените следующие константы в файле `web_import_functions.php`:

- `PREFIX_URL`, `WEBCASTER_EVENTS_URL`, `DRUPAL_EVENTS_URL`, `NODE_URL_POST`, `NODE_URL_PATCH`, `NODE_FORMAT_PATCH`, `USER_NAME_DRUPAL`, `PASSWORD_DRUPAL`, `API_URL`, `SECRET_KEY`, `CID`, `EVENT_API_URL`

3. Сохраните файл после внесения изменений.

## Функции:

1. `getJsonArray($url)`: Получает JSON-контент с указанного URL и возвращает его в виде массива.
1. `postNode($data, $nid = null)`: Отправляет данные узла в Drupal с использованием указанного метода (POST или PATCH).
1. `postTaxonomy($data)`: Отправляет данные таксономии в Drupal.
1. `postToDrupal($url, $json, $method)`: Отправляет данные в Drupal с использованием cURL.
1. `loadArrayFromUrl($url)`: Загружает JSON с указанного URL и преобразует его в массив.
1. `loadEventViaApi($id)`: Загружает событие через API и возвращает экземпляр `WebcasterEvent`.
1. `loadEventViaApp($id)`: Загружает данные события из веб-сервиса Webcaster App API и возвращает экземпляр `WebcasterEvent`.
1. `WebcasterEvent`: Класс, представляющий событие Webcaster. Он содержит свойства, такие как идентификатор, название, описание, режиссеры, актеры, страна производства и другие данные о событии. Класс также содержит статические методы fromAppData и fromApiData для создания экземпляра WebcasterEvent из данных API, а также другие методы для генерации массивного представления события для интеграции с Drupal.

## Использование

1. Запустите скрипт `web_import.php`, выполнив его в командной строке: `php web_import.php`
