## Урезанный JSON REST API c тремя методами

Для установки поправить в .env доступ к базе

Выполнить миграцию и заполнение
`$ php artisan migrate && php artisan db:seed`

Доступны три метода
```
GET /available_dates
GET /calculate?checkin=2020-08-01&checkout=2020-08-05&adults=2&children=1&room_type=1&rooms=1
POST /booking
```