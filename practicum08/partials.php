<?php
// Практична робота №8, варіант 13 — Трекер особистих витрат
// Спільні частини розмітки
declare(strict_types=1);

function pageStart(string $title, bool $withUser = true): void
{
    ?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <title><?= e($title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700&family=Unbounded:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="app.js" defer></script>
</head>
<body>
    <header class="page-header">
        <h1>Гаманець</h1>
        <p>Трекер особистих витрат</p>
        <?php if ($withUser && currentUserId() !== null): ?>
            <form action="logout.php" method="post" class="user-bar">
                <?= csrfField() ?>
                <span><?= e($_SESSION['username']) ?></span>
                <button type="submit" class="link-button">Вийти</button>
            </form>
        <?php endif; ?>
    </header>
    <?php
}

function pageEnd(): void
{
    ?>
    <footer class="page-footer">
        Практична робота №8 · Варіант 13
    </footer>
</body>
</html>
    <?php
}

// Повідомлення про помилки форми, збережені в сесії перед перенаправленням
function takeFlash(): array
{
    $flash = $_SESSION['flash'] ?? ['errors' => [], 'old' => []];
    unset($_SESSION['flash']);
    return $flash;
}
