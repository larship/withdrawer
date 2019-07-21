# Приложение withdrawer (тестовое)

## Требования

* PHP 7.1+
* MySQL 5.6+ или MariaDB 10.3+

## Разворачивание приложения

Для разворачивания приложения необходимо:
1. Склонировать данный репозиторий
2. Скопировать файл `.env` в `.env.local`
3. В файле `.env.local` настроить параметры подключения к базе данных и создать саму пустую базу данных
4. Выполнить `composer install`
5. Выполнить `./bin/console doctrine:migrations:migrate` или сокращённо `/bin/console do:mi:mi`
6. Выполнить `./bin/console doctrine:fixtures:load`
7. Выполнить `./bin/console server:start` для запуска встроенного веб-сервера. Вместо данного шага можно использовать apache или nginx (более подробно смотреть документацию выбранного веб-сервера)

После этого приложения готово к работе.

## Предустановленное состояние

После настройки приложения будет доступна аутентификация пользователя с именем `Tester` и паролем `123`.
Баланс данного пользователя будет составлять `100 единиц`

## Тесты

Следующий набор команд создаст тестовую базу данных, наполнит её и запустит тесты:
```
./bin/console do:database:drop --env test --if-exists --force && \
./bin/console do:database:create --env test && \
./bin/console do:mi:mi --env test --no-interaction && \
./bin/console do:fi:load --env test --no-interaction && \
phpunit
```