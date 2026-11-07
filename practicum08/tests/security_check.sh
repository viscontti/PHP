#!/bin/sh
# Практична робота №8, варіант 13 — Трекер особистих витрат
# Автоматична перевірка захисту через curl (обхід HTML-форми й браузерних обмежень).
# Запуск: сервер php -S localhost:8008 router.php, потім sh tests/security_check.sh
B=${BASE:-http://localhost:8008}
JAR=$(mktemp)
OTHER=$(mktemp)
FAILED=0

# check "опис" очікуваний_код фактичний_код
check() {
    if [ "$2" = "$3" ]; then r='OK  '; else r='FAIL'; FAILED=$((FAILED + 1)); fi
    printf '%s %s (очікується %s)  %s\n' "$r" "$3" "$2" "$1"
}
code() { curl -s -o /dev/null -w '%{http_code}' "$@"; }
token() { curl -s -b "$1" -c "$1" "$B/$2" | sed -n 's/.*name="csrf_token" value="\([^"]*\)".*/\1/p' | head -1; }
login() {
    t=$(token "$1" login.php)
    code -b "$1" -c "$1" --data-urlencode "csrf_token=$t" -d "username=$2&password=$3" "$B/login.php"
}

echo '--- Авторизація'
check 'Головна без входу -> перенаправлення на login.php' 302 "$(code "$B/index.php")"
check 'API без входу' 401 "$(code "$B/api.php?resource=transactions")"
check 'Вхід з неправильним паролем' 401 "$(login "$OTHER" veronika wrong)"
check 'Вхід veronika' 302 "$(login "$JAR" veronika veronika2026)"
T=$(token "$JAR" index.php)
ADD='-d amount=-50 --data-urlencode category=Перевірка -d transaction_date=2026-11-07'

echo '--- Крок 1. SQL-ін'"'"'єкція у фільтрі category'
check "Фільтр category=' OR '1'='1 на сторінці: рядків таблиці" 0 \
    "$(curl -s -b "$JAR" -G --data-urlencode "category=' OR '1'='1" "$B/index.php" | grep -c 'edit.php?id=')"
check 'Без фільтра: рядків таблиці (6 записів veronika)' 6 "$(curl -s -b "$JAR" "$B/index.php" | grep -c 'edit.php?id=')"
check "API category=' OR '1'='1: записів у відповіді" 0 \
    "$(curl -s -b "$JAR" -G -d resource=transactions --data-urlencode "category=' OR '1'='1" "$B/api.php" | grep -o '"id"' | wc -l | tr -d ' ')"

echo '--- Крок 3. CSRF'
check 'Додавання без токена' 403 "$(code -b "$JAR" $ADD "$B/add.php")"
check 'Додавання з порожнім токеном' 403 "$(code -b "$JAR" -d csrf_token= $ADD "$B/add.php")"
check 'Додавання з неправильним токеном' 403 "$(code -b "$JAR" -d csrf_token=abc $ADD "$B/add.php")"
check 'Додавання з токеном-масивом' 403 "$(code -b "$JAR" -d "csrf_token[]=$T" $ADD "$B/add.php")"
check 'Нова сесія без токена в сесії, порожній токен у POST' 403 "$(code -d csrf_token= $ADD "$B/add.php")"
check 'Видалення без токена' 403 "$(code -b "$JAR" -d id=1 "$B/delete.php")"
check 'API: action=balance без токена' 403 "$(code -b "$JAR" -X POST "$B/api.php?resource=transactions&action=balance")"
check 'API: створення без токена' 403 "$(code -b "$JAR" $ADD "$B/api.php?resource=transactions")"
check 'Вихід без токена' 403 "$(code -b "$JAR" -X POST "$B/logout.php")"
check 'Додавання з дійсним токеном' 303 "$(code -b "$JAR" -d "csrf_token=$T" $ADD "$B/add.php")"
check 'API: action=balance з токеном у X-CSRF-Token' 200 \
    "$(code -b "$JAR" -H "X-CSRF-Token: $T" -X POST "$B/api.php?resource=transactions&action=balance")"
OLD=$T
code -b "$JAR" -c "$JAR" -d "csrf_token=$T" "$B/logout.php" > /dev/null
login "$JAR" veronika veronika2026 > /dev/null
T=$(token "$JAR" index.php)
check 'Старий токен після повторного входу' 403 "$(code -b "$JAR" -d "csrf_token=$OLD" $ADD "$B/add.php")"

echo '--- Крок 4. Серверна валідація (в обхід required)'
check 'Без полів' 400 "$(code -b "$JAR" -H "X-CSRF-Token: $T" -X POST "$B/api.php?resource=transactions")"
check 'Порожня категорія' 400 "$(code -b "$JAR" -H "X-CSRF-Token: $T" -d amount=-5 -d category= -d transaction_date=2026-11-07 "$B/api.php?resource=transactions")"
check 'Категорія з 101 символу' 400 "$(code -b "$JAR" -H "X-CSRF-Token: $T" -d amount=-5 -d "category=$(printf 'a%.0s' $(seq 101))" -d transaction_date=2026-11-07 "$B/api.php?resource=transactions")"
check 'Сума = 0' 400 "$(code -b "$JAR" -H "X-CSRF-Token: $T" -d amount=0 -d category=x -d transaction_date=2026-11-07 "$B/api.php?resource=transactions")"
check 'Сума 1e12 (поза DECIMAL(10,2))' 400 "$(code -b "$JAR" -H "X-CSRF-Token: $T" -d amount=1e12 -d category=x -d transaction_date=2026-11-07 "$B/api.php?resource=transactions")"
check 'Дата 2026-02-30' 400 "$(code -b "$JAR" -H "X-CSRF-Token: $T" -d amount=-5 -d category=x -d transaction_date=2026-02-30 "$B/api.php?resource=transactions")"
check 'Поле category[] масивом' 400 "$(code -b "$JAR" -H "X-CSRF-Token: $T" -d amount=-5 -d 'category[]=x' -d transaction_date=2026-11-07 "$B/api.php?resource=transactions")"
check 'Форма без полів -> повернення до форми з помилками' 303 "$(code -b "$JAR" -d "csrf_token=$T" "$B/add.php")"

echo '--- Крок 5. Дії, що змінюють дані, — лише POST'
check 'GET delete.php?id=2' 405 "$(code -b "$JAR" "$B/delete.php?id=2")"
check 'GET add.php' 405 "$(code -b "$JAR" "$B/add.php")"
check 'GET logout.php' 405 "$(code -b "$JAR" "$B/logout.php")"

echo '--- Незахищена дія варіанта 13: чужі транзакції за id (id 7 належить olena)'
check 'veronika: edit.php?id=7' 404 "$(code -b "$JAR" "$B/edit.php?id=7")"
check 'veronika: api.php?id=7' 404 "$(code -b "$JAR" "$B/api.php?resource=transactions&id=7")"
check 'veronika: видалення id=7' 404 "$(code -b "$JAR" -d "csrf_token=$T" -d id=7 "$B/delete.php")"
check 'veronika: edit.php?id=2 (власний запис)' 200 "$(code -b "$JAR" "$B/edit.php?id=2")"
check 'olena: api.php?id=7 (власний запис)' 200 "$(login "$OTHER" olena olena2026 >/dev/null; code -b "$OTHER" "$B/api.php?resource=transactions&id=7")"

echo '--- Крок 6. Службові файли недоступні'
for f in logs/php_errors.log sql/schema.sql bootstrap.php db.php .gitignore; do
    check "GET /$f" 404 "$(code "$B/$f")"
done

rm -f "$JAR" "$OTHER"
echo
if [ $FAILED -eq 0 ]; then echo 'Усі перевірки пройдено'; else echo "Провалено перевірок: $FAILED"; exit 1; fi
