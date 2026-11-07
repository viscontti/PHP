# API трекера особистих витрат (варіант 13)

Єдина точка входу: `api.php`. Ресурс і запис задаються параметрами `resource` та `id`, дія — параметром `action`.

Усі відповіді — JSON (`Content-Type: application/json`) однакової структури:

- успіх: `{"success": true, "data": ...}`
- помилка: `{"success": false, "error": "опис помилки"}`

Ресурс `transactions`: `id`, `amount` (додатна — дохід, від'ємна — витрата), `category`, `transaction_date` (YYYY-MM-DD).

| Метод | Запит | Параметри | Успіх | Помилки |
|-------|-------|-----------|-------|---------|
| GET | `api.php?resource=transactions` | — | 200, масив записів | — |
| GET | `api.php?resource=transactions&id=N` | `id` — ціле > 0 | 200, один запис | 400 (некоректний id), 404 (немає запису) |
| POST | `api.php?resource=transactions` | тіло: `amount`, `category`, `transaction_date` | 201, створений запис | 400 (поле відсутнє або некоректне) |
| POST | `api.php?resource=transactions&action=balance` | — | 200, `{"balance": ...}` | 400 (невідома дія) |

Тіло POST-запиту приймається як форма (`application/x-www-form-urlencoded`, `multipart/form-data`) або як JSON (`application/json`).

Інші випадки:

- невідомий ресурс — 404;
- метод, який ресурс не підтримує (PUT, DELETE тощо) — 405 і заголовок `Allow: GET, POST`;
- `action` у GET-запиті — 405 (дії виконуються лише методом POST);
- помилка на сервері (наприклад, недоступна БД) — 500.

## Приклади

```
GET /api.php?resource=transactions&id=2
200 {"success":true,"data":{"id":2,"amount":-245.5,"category":"Продукти","transaction_date":"2026-10-02"}}

GET /api.php?resource=transactions&id=999
404 {"success":false,"error":"Транзакцію з id 999 не знайдено"}

POST /api.php?resource=transactions   amount=-120&category=Таксі&transaction_date=2026-11-07
201 {"success":true,"data":{"id":15,"amount":-120,"category":"Таксі","transaction_date":"2026-11-07"}}

POST /api.php?resource=transactions   amount=-120&transaction_date=2026-11-07
400 {"success":false,"error":"Поле category є обов'язковим"}

POST /api.php?resource=transactions&action=balance
200 {"success":true,"data":{"balance":2362682.2}}
```
