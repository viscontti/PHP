// Практична робота №5, варіант 13 — Трекер особистих витрат
// AJAX: завантаження списку, живий пошук і додавання без перезавантаження сторінки

const tbody = document.querySelector('#results tbody');
const search = document.getElementById('search');
const message = document.getElementById('message');
const balanceEl = document.getElementById('balance');
const form = document.getElementById('add-form');

// Екранування даних перед вставкою в innerHTML (захист від XSS)
function escapeHtml(text) {
    return String(text)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

// -245.5 -> "-245,50 грн"
function formatMoney(amount) {
    return amount.toLocaleString('uk-UA', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' грн';
}

// "2026-10-02" -> "02.10.2026"
function formatDate(date) {
    return date.split('-').reverse().join('.');
}

// Крок 7. Повідомлення про помилку показуємо на сторінці, а не лише в консолі
function showMessage(text) {
    message.textContent = text;
    message.hidden = false;
}

function hideMessage() {
    message.hidden = true;
}

// Перемальовує вміст таблиці з масиву транзакцій
function renderRows(rows) {
    if (rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="empty">Нічого не знайдено</td></tr>';
        return;
    }

    tbody.innerHTML = rows.map((row) => `
        <tr>
            <td>${row.id}</td>
            <td>${formatDate(row.transaction_date)}</td>
            <td>${escapeHtml(row.category)}</td>
            <td class="num ${row.amount > 0 ? 'plus' : 'minus'}">${formatMoney(row.amount)}</td>
        </tr>`).join('');
}

// Кроки 3–4. Завантаження списку (з фільтром q або без нього)
async function loadList(q = '') {
    try {
        const response = await fetch('api_list.php?q=' + encodeURIComponent(q));
        if (!response.ok) {
            throw new Error('Сервер повернув помилку ' + response.status);
        }
        const rows = await response.json();
        hideMessage();
        renderRows(rows);
    } catch (error) {
        tbody.innerHTML = '';
        showMessage('Не вдалося завантажити транзакції: ' + error.message);
    }
}

async function loadBalance() {
    try {
        const response = await fetch('api_balance.php');
        if (!response.ok) {
            throw new Error('Сервер повернув помилку ' + response.status);
        }
        const data = await response.json();
        balanceEl.textContent = formatMoney(data.balance);
    } catch (error) {
        showMessage('Не вдалося отримати баланс: ' + error.message);
    }
}

// Крок 4. Живий пошук: запит на кожну зміну поля
search.addEventListener('input', () => {
    loadList(search.value.trim());
});

// Крок 6. Додавання через AJAX без перезавантаження сторінки
form.addEventListener('submit', async (event) => {
    event.preventDefault();

    try {
        const response = await fetch('api_add.php', {
            method: 'POST',
            body: new FormData(form),
        });
        if (!response.ok) {
            // api_add.php при помилці валідації повертає JSON {"error": "..."}
            let text = 'Сервер повернув помилку ' + response.status;
            try {
                text = (await response.json()).error || text;
            } catch {
                // відповідь не JSON (наприклад, помилка 500)
            }
            showMessage(text);
            return;
        }

        const data = await response.json();

        form.reset();
        document.getElementById('date').value = data.transaction_date;
        // Оновлюємо таблицю (з поточним фільтром) і баланс над нею
        await loadList(search.value.trim());
        await loadBalance();
    } catch (error) {
        showMessage('Не вдалося додати транзакцію: ' + error.message);
    }
});

// Крок 3. Перше завантаження при відкритті сторінки
document.getElementById('date').value = new Date().toISOString().slice(0, 10);
loadList();
loadBalance();
