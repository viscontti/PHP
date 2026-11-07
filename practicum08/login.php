<?php
// Практична робота №8, варіант 13 — Трекер особистих витрат
// Вхід: пароль перевіряється password_verify(), форма входу теж захищена CSRF-токеном
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/partials.php';

if (currentUserId() !== null) {
    header('Location: index.php');
    exit;
}

$error = null;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $username = inputString($_POST, 'username') ?? '';
    $password = inputString($_POST, 'password') ?? '';

    $user = $username !== '' ? findUserByUsername($username) : null;
    if ($user !== null && password_verify($password, $user['password_hash'])) {
        logIn($user);
        header('Location: index.php');
        exit;
    }
    // Однакове повідомлення для неіснуючого логіна й неправильного пароля
    $error = 'Невірний логін або пароль';
    http_response_code(401);
}

pageStart('Вхід — Гаманець', false);
?>
    <main class="layout layout-narrow">
        <form method="post" class="card">
            <h2>Вхід</h2>
            <?php if ($error !== null): ?>
                <p class="alert alert-error"><?= e($error) ?></p>
            <?php endif; ?>
            <?= csrfField() ?>
            <div class="field">
                <label for="username">Логін</label>
                <input type="text" id="username" name="username" required maxlength="50" autocomplete="username" value="<?= e($username) ?>">
            </div>
            <div class="field">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn">Увійти</button>
        </form>
    </main>
<?php
pageEnd();
