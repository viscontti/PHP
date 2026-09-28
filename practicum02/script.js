// Практична робота №2, варіант 13 — клієнтська частина форми «Нова транзакція»

const form = document.getElementById('transaction-form');
const amountInput = document.getElementById('amount');
const categorySelect = document.getElementById('category');
const dateInput = document.getElementById('date');
const categoryHint = document.getElementById('category-hint');

// Ключ, під яким категорія зберігається в localStorage
const STORAGE_KEY = 'expenses_default_category';

// ---------------------------------------------------------------
// Крок 6. localStorage: остання обрана категорія за замовчуванням
// ---------------------------------------------------------------

// Відновлення одразу після завантаження сторінки.
// Якщо сервер уже підставив категорію (форма з помилками) — її не чіпаємо.
const savedCategory = localStorage.getItem(STORAGE_KEY);
const optionExists = [...categorySelect.options].some(option => option.value === savedCategory);

if (categorySelect.value === '' && savedCategory && optionExists) {
    categorySelect.value = savedCategory;
    const title = categorySelect.options[categorySelect.selectedIndex].text.trim();
    categoryHint.textContent = `Категорію «${title}» підставлено з localStorage`;
}

// Збереження при кожній зміні вибору
categorySelect.addEventListener('change', () => {
    if (categorySelect.value !== '') {
        localStorage.setItem(STORAGE_KEY, categorySelect.value);
    }
    categoryHint.textContent = '';
});

// ---------------------------------------------------------------
// Крок 5. Клієнтська валідація перед відправкою форми
// ---------------------------------------------------------------

// Вимикаємо стандартні підказки браузера, щоб показувати власні повідомлення.
// Якщо JavaScript вимкнено — продовжать працювати HTML5-атрибути (required, min).
form.noValidate = true;

function showError(field, message) {
    const errorElement = form.querySelector(`[data-error-for="${field}"]`);
    errorElement.textContent = message;
    errorElement.closest('.field').classList.toggle('has-error', message !== '');
}

function validateForm() {
    let isValid = true;

    // amount: обов'язкове число більше 0
    const amount = Number(amountInput.value.replace(',', '.'));
    if (amountInput.value.trim() === '') {
        showError('amount', 'Вкажіть суму транзакції');
        isValid = false;
    } else if (Number.isNaN(amount) || amount <= 0) {
        showError('amount', 'Сума має бути більшою за 0');
        isValid = false;
    } else {
        showError('amount', '');
    }

    // category: обов'язкова
    if (categorySelect.value === '') {
        showError('category', 'Оберіть категорію');
        isValid = false;
    } else {
        showError('category', '');
    }

    // date: обов'язкова і коректна (браузер повертає '' для неповної дати)
    if (dateInput.value === '' || Number.isNaN(Date.parse(dateInput.value))) {
        showError('date', 'Вкажіть коректну дату');
        isValid = false;
    } else {
        showError('date', '');
    }

    return isValid;
}

form.addEventListener('submit', (event) => {
    if (!validateForm()) {
        event.preventDefault(); // не відправляємо форму, сторінка не перезавантажується
        return;
    }
    localStorage.setItem(STORAGE_KEY, categorySelect.value);
});

// Прибираємо повідомлення про помилку, щойно користувач виправляє поле
[amountInput, categorySelect, dateInput].forEach((element) => {
    element.addEventListener('input', () => showError(element.name, ''));
});
