# Testing server

A mini E-business project - server side - testing exploitation for educational purposes

run the server:

```bash
docker compose up --watch --build
```

then install the dependencies(if not installed):

```bash
docker exec -it php-apache bash
composer install
```

then run the tests:

```bash
php vendor/bin/phpunit tests/RouteConfigTest.php --debug
```
