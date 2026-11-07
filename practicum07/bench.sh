#!/bin/sh
# Практична робота №7, варіант 13 — Трекер особистих витрат
# Відтворює всі заміри «до / після» і зберігає сирі результати в папку results/.
# Перед запуском: MySQL (контейнер php-mysql) і сервер
#   PHP_CLI_SERVER_WORKERS=8 php -S localhost:8007
set -e
cd "$(dirname "$0")"
BASE='http://localhost:8007'
LIST='resource=transactions'
MYSQL='docker exec -i php-mysql mysql -uroot -proot --default-character-set=utf8mb4 -t'
R=results
mkdir -p $R

# 5 послідовних запитів: заголовки профілювання з кожної відповіді
profile() {
    for i in 1 2 3 4 5; do
        curl -s -o /dev/null -D - "$1" | grep -E '^X-(Exec|Peak|SQL)' | tr -d '\r' | tr '\n' ' '
        echo
    done
}

# Стан «до»: індексу немає
$MYSQL < sql/02_add_index_category_down.sql 2>/dev/null || true

echo '== Крок 1. Профілювання «до» (api_before.php, без індексу)'
profile "$BASE/api_before.php?$LIST" | tee $R/01_profile_before.txt

echo '== Крок 2. ab «до»'
ab -n 200 -c 20 "$BASE/api_before.php?$LIST" > $R/02_ab_before.txt
grep -E 'Requests per second|Time per request|Failed requests' $R/02_ab_before.txt

echo '== Крок 3. Кількість SQL-запитів на один виклик'
{
    echo 'api_before.php (N+1):'
    curl -s -o /dev/null -D - "$BASE/api_before.php?$LIST" | grep -E '^X-SQL' | tr -d '\r'
    echo 'api_before.php&category=Таксі (N+1):'
    curl -s -o /dev/null -D - "$BASE/api_before.php?$LIST&category=%D0%A2%D0%B0%D0%BA%D1%81%D1%96" | grep -E '^X-SQL' | tr -d '\r'
    echo "Записів у таблиці: $($MYSQL -N practicum7 -e 'SELECT COUNT(*) FROM transactions' 2>/dev/null | tr -dc 0-9)"
} | tee $R/03_query_count_before.txt

echo '== Крок 4. EXPLAIN до індексу'
$MYSQL < sql/explain.sql 2>/dev/null | tee $R/04_explain_before.txt
$MYSQL < sql/02_add_index_category.sql 2>/dev/null
echo '== Крок 4. EXPLAIN після індексу'
{
    $MYSQL -e "SELECT INDEX_NAME, COLUMN_NAME, INDEX_TYPE, CARDINALITY FROM information_schema.STATISTICS
               WHERE TABLE_SCHEMA = 'practicum7' AND TABLE_NAME = 'transactions'" 2>/dev/null
    $MYSQL < sql/explain.sql 2>/dev/null
} | tee $R/05_explain_after.txt

echo '== Крок 4. ab «до» + індекс (окремий внесок індексу в N+1)'
ab -n 200 -c 20 "$BASE/api_before.php?$LIST" > $R/06_ab_before_with_index.txt
grep -E 'Requests per second|Time per request|Failed requests' $R/06_ab_before_with_index.txt

echo '== Крок 5. Кількість SQL-запитів після усунення N+1'
{
    echo 'api.php (GROUP BY):'
    curl -s -o /dev/null -D - "$BASE/api.php?$LIST" | grep -E '^X-SQL' | tr -d '\r'
    echo 'api.php&category=Таксі (GROUP BY):'
    curl -s -o /dev/null -D - "$BASE/api.php?$LIST&category=%D0%A2%D0%B0%D0%BA%D1%81%D1%96" | grep -E '^X-SQL' | tr -d '\r'
    echo 'Відповіді api_before.php і api.php однакові:'
    if [ "$(curl -s "$BASE/api_before.php?$LIST")" = "$(curl -s "$BASE/api.php?$LIST")" ]; then echo 'так'; else echo 'ні'; fi
} | tee $R/07_query_count_after.txt

echo '== Крок 6. Кешування balance(): ab без кешу і з кешем'
: > /tmp/empty_body
# Лічильники MySQL: Connections — кількість з'єднань, Questions — кількість команд
# (на кожне з'єднання PHP припадає SELECT + команда закриття з'єднання)
mysql_status() {
    docker exec php-mysql mysql -uroot -proot -N \
        -e "SHOW GLOBAL STATUS WHERE Variable_name IN ('Connections', 'Questions')" 2>/dev/null \
        | awk '{printf "%s ", $2}'
}
# ab на balance + скільки з'єднань і команд отримала MySQL за час тесту.
# Від різниці віднімається одне зчитування лічильників (1 з'єднання і 4 команди mysql-клієнта).
ab_balance() {
    set -- "$1" "$2" $(mysql_status)
    ab -n 1000 -c 20 -p /tmp/empty_body -T 'application/x-www-form-urlencoded' "$1" > "$2"
    set -- "$1" "$2" "$3" "$4" $(mysql_status)
    {
        echo
        echo "MySQL Connections: $3 -> $5, Questions: $4 -> $6"
        echo "MySQL отримала за час тесту: з'єднань $(($5 - $3 - 1)), команд $(($6 - $4 - 4))"
    } >> "$2"
}
rm -rf cache
ab_balance "$BASE/api_before.php?$LIST&action=balance" $R/08_ab_balance_no_cache.txt
ab_balance "$BASE/api.php?$LIST&action=balance" $R/09_ab_balance_cache.txt
grep -E 'Requests per second|Failed|MySQL' $R/08_ab_balance_no_cache.txt $R/09_ab_balance_cache.txt

echo '== Крок 6. Інвалідація кешу після додавання транзакції'
bal() { curl -s -D - -X POST "$BASE/api.php?$LIST&action=balance" | grep -E '^X-(Cache|SQL)|success' | tr -d '\r' | tr '\n' ' '; echo; }
{
    echo '1) баланс (перший запит після попередніх тестів):'; bal
    echo '2) баланс повторно:'; bal
    echo '3) додаємо транзакцію -100 Таксі:'
    curl -s -X POST -d 'amount=-100&category=Таксі&transaction_date=2026-11-07' "$BASE/api.php?$LIST"; echo
    echo '4) баланс після додавання (кеш інвалідовано -> MISS, нове значення):'; bal
    echo '5) баланс повторно (знову з кешу):'; bal
} | tee $R/10_cache_invalidation.txt

echo '== Крок 7. Профілювання «після» (api.php, з індексом)'
profile "$BASE/api.php?$LIST" | tee $R/11_profile_after.txt

echo '== Крок 7. ab «після»'
ab -n 200 -c 20 "$BASE/api.php?$LIST" > $R/12_ab_after.txt
grep -E 'Requests per second|Time per request|Failed requests' $R/12_ab_after.txt
