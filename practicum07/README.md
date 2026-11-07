# Практична робота №7 — оптимізація серверних скриптів

Варіант 13, «Трекер особистих витрат». Списковий ендпоінт `api.php?resource=transactions` з практикуму №6.
Список додатково повертає для кожної транзакції суму по її категорії (`category_total`).

## Файли

| Файл | Призначення |
|------|-------------|
| `api_before.php` | версія «як є» (заміри «до»): N+1-запит суми по категорії, баланс без кешу |
| `api.php` | оптимізована версія (заміри «після»): `totalByCategory()` з `GROUP BY`, кеш `balance()` |
| `db.php` | ліниве підключення PDO, лічильник SQL-запитів (`CountingPDO`, `CountingStatement`) |
| `lib.php` | спільні функції; профілювання `microtime(true)` і `memory_get_peak_usage()` |
| `cache.php` | файловий кеш з TTL (`remember()`) і явною інвалідацією (`forget()`) |
| `sql/01_schema_seed.sql` | окрема база `practicum7`: таблиця `transactions` і 1000 тестових записів, без індексу |
| `sql/02_add_index_category.sql` | міграція: `CREATE INDEX idx_transactions_category ON transactions (category)` |
| `sql/02_add_index_category_down.sql` | відкат міграції, щоб повторити заміри «до» |
| `sql/explain.sql` | `EXPLAIN` запитів із фільтром за категорією |
| `bench.sh` | проводить усі заміри й зберігає сирі результати в `results/` |
| `results/` | вивід профілювання, лічильника запитів, `EXPLAIN` і `ab` до та після |

Кожна відповідь API містить заголовки з результатами профілювання:
`X-Exec-Time-Ms`, `X-Peak-Memory-MB`, `X-Peak-Memory-Used-KB`, `X-SQL-Queries`, а відповідь `balance` ще й `X-Cache: HIT/MISS`.
Ці ж значення записуються в лог сервера через `error_log()`.

## Як відтворити

```sh
docker exec -i php-mysql mysql -uroot -proot --default-character-set=utf8mb4 < sql/01_schema_seed.sql
PHP_CLI_SERVER_WORKERS=8 php -S localhost:8007    # в окремому терміналі
./bench.sh
```

## Результати «до / після»

Середовище: MacBook Air (Apple M1), PHP 8.5 (вбудований сервер, 8 воркерів), MySQL 8.4 у Docker, 1000 записів.
Навантаження: `ab -n 200 -c 20`.

### Списковий ендпоінт `resource=transactions`

| Показник | До (`api_before.php`, без індексу) | Після (`api.php`, з індексом) | Доказ |
|----------|-----------------------------------:|------------------------------:|-------|
| Час виконання (середнє з 5 запитів) | 228.90 мс | 6.07 мс | `results/01_profile_before.txt`, `results/11_profile_after.txt` |
| Пам'ять, `memory_get_peak_usage(true)` | 2.00 МБ | 2.00 МБ | там само |
| Пам'ять, `memory_get_peak_usage()` | 1049.6 КБ | 1050.2 КБ | там само |
| SQL-запитів на один HTTP-запит | 1001 | 2 | `results/03_query_count_before.txt`, `results/07_query_count_after.txt` |
| Requests per second | 16.74 | 886.97 | `results/02_ab_before.txt`, `results/12_ab_after.txt` |
| Time per request (mean) | 1194.571 мс | 22.549 мс | там само |
| Failed requests | 0 | 0 | там само |

**Чому така різниця:** версія «до» робить окремий SQL-запит на кожну з 1000 транзакцій (1 + N = 1001 запит).
Майже весь час іде на 1000 звернень до MySQL. Після заміни циклу одним запитом `GROUP BY category`
залишилось 2 запити, тож пропускна здатність зросла приблизно в 53 рази.
Пам'ять майже не змінилась, бо обидві версії обробляють ту саму кількість рядків.

### Індекс на `category` (крок 4)

| | До індексу | Після індексу |
|-|-----------|---------------|
| `EXPLAIN SELECT * FROM transactions WHERE category = 'Продукти'` | `type = ALL`, `key = NULL`, `rows = 1000` | `type = ref`, `key = idx_transactions_category`, `rows = 87` |
| `EXPLAIN SELECT SUM(amount) FROM transactions WHERE category = 'Продукти'` | `type = ALL`, `rows = 1000` | `type = ref`, `rows = 87` |

Повний вивід: `results/04_explain_before.txt`, `results/05_explain_after.txt` (там же список індексів таблиці).
SQL індексу: `sql/02_add_index_category.sql`.

Після індексу MySQL переглядає 87 рядків однієї категорії замість повного сканування 1000 рядків.
Окремий замір `api_before.php` з індексом (`results/06_ab_before_with_index.txt`) дав 16.36 rps проти 16.74 rps без індексу.
Тобто для версії з N+1 індекс майже нічого не змінює: на 1000 записах кожен окремий запит і так виконується швидко,
а основний час іде на 1000 звернень до бази. Тому головна оптимізація — усунення N+1,
а індекс прискорює фільтр `?category=` і стане відчутним із ростом таблиці.

### Кеш `balance()` (крок 6), `ab -n 1000 -c 20`

| Показник | Без кешу (`api_before.php`) | З кешем (`api.php`) | Доказ |
|----------|----------------------------:|--------------------:|-------|
| Requests per second | 2742.86 | 9780.81 | `results/08_ab_balance_no_cache.txt`, `results/09_ab_balance_cache.txt` |
| Time per request (mean) | 7.292 мс | 2.045 мс | там само |
| З'єднань із MySQL за тест | 1000 | 1 | там само (лічильник MySQL `Connections`) |
| Команд MySQL за тест | 2000 | 2 | там само (лічильник MySQL `Questions`) |

TTL кешу — 60 секунд. Кеш явно інвалідується (`forget('balance')`) після додавання транзакції.
Протокол перевірки: `results/10_cache_invalidation.txt`:
HIT → додавання транзакції → MISS із новим балансом → знову HIT.

Завдяки лінивому підключенню (`db()` у `db.php`) відповідь із кешу взагалі не відкриває з'єднання з MySQL.
Тому пропускна здатність зросла в 3.6 раза, а база за весь тест отримала лише 1 запит (перший MISS).
