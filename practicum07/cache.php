<?php
// Практична робота №7, варіант 13 — Трекер особистих витрат
// Крок 6. Файловий кеш із часом життя (TTL) і явною інвалідацією
declare(strict_types=1);

const CACHE_DIR = __DIR__ . '/cache';

function cacheFile(string $key): string
{
    return CACHE_DIR . '/' . md5($key) . '.json';
}

// Повертає значення з кешу, якщо файл існує і не старший за $ttlSeconds; інакше обчислює
// його функцією $compute і зберігає. Заголовок X-Cache показує, звідки взято результат.
function remember(string $key, int $ttlSeconds, callable $compute): mixed
{
    $file = cacheFile($key);
    if (is_file($file) && (time() - filemtime($file)) < $ttlSeconds) {
        header('X-Cache: HIT');
        return json_decode(file_get_contents($file), true);
    }

    $value = $compute();
    if (!is_dir(CACHE_DIR)) {
        mkdir(CACHE_DIR, 0777, true);
    }
    // Запис у тимчасовий файл і rename — паралельні запити не прочитають недописаний файл
    $tmp = $file . '.' . bin2hex(random_bytes(4));
    file_put_contents($tmp, json_encode($value));
    rename($tmp, $file);
    header('X-Cache: MISS');
    return $value;
}

// Явна інвалідація: викликається після зміни даних
function forget(string $key): void
{
    $file = cacheFile($key);
    if (is_file($file)) {
        unlink($file);
    }
}
