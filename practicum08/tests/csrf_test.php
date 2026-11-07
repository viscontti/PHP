<?php
// Практична робота №8, варіант 13 — Трекер особистих витрат
// Перевірка isValidCsrf() у всіх граничних випадках. Запуск: php tests/csrf_test.php
// Головне — випадок «у сесії токена немає»: hash_equals('', '') повертає true,
// тому без явної перевірки такий запит пройшов би (помилка з зауваження до практикуму №8).
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$valid = bin2hex(random_bytes(32));

$cases = [
    // [опис, токен у сесії, токен у POST, очікуваний результат]
    ['у сесії токена немає, у POST токена немає', null, null, false],
    ['у сесії токена немає, у POST порожній рядок', null, '', false],
    ['у сесії порожній рядок, у POST порожній рядок', '', '', false],
    ['у сесії токена немає, у POST довільний токен', null, $valid, false],
    ['токен у сесії є, у POST токена немає', $valid, null, false],
    ['токен у сесії є, у POST порожній рядок', $valid, '', false],
    ['токен у сесії є, у POST інший (старий) токен', $valid, bin2hex(random_bytes(32)), false],
    ['токен у сесії є, у POST масив', $valid, [$valid], false],
    ['токен у сесії є, у POST той самий токен', $valid, $valid, true],
];

echo "hash_equals('', '') = " . var_export(hash_equals('', ''), true) . "\n\n";

$failed = 0;
foreach ($cases as [$title, $sessionToken, $postToken, $expected]) {
    $_SESSION = $sessionToken === null ? [] : ['csrf_token' => $sessionToken];
    $_POST = $postToken === null ? [] : ['csrf_token' => $postToken];
    $actual = isValidCsrf();
    $ok = $actual === $expected;
    $failed += $ok ? 0 : 1;
    printf("%-4s %s очікується %-5s отримано %s\n",
        $ok ? 'OK' : 'FAIL', mb_str_pad($title, 48), var_export($expected, true), var_export($actual, true));
}

echo $failed === 0 ? "\nУсі перевірки пройдено\n" : "\nПровалено: $failed\n";
exit($failed === 0 ? 0 : 1);
