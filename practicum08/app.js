// Практична робота №8, варіант 13 — Трекер особистих витрат
// Підтвердження перед видаленням. Обробник підключено з файлу, а не атрибутом onsubmit,
// бо Content-Security-Policy забороняє інлайн-скрипти.
// confirm() захищає лише від випадкового кліку; від підробленого запиту захищають POST + CSRF-токен.
document.querySelectorAll('form.js-confirm').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});
