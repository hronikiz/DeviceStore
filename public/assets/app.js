const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const telegramPattern = /^@?[A-Za-z0-9_]{5,32}$/;
const phonePattern = /^[0-9+()\s-]{6,20}$/;

function setError(form, fieldName, message) {
    const field = form.querySelector(`[name="${fieldName}"]`);
    const error = form.querySelector(`[data-error-for="${fieldName}"]`);
    const label = field?.closest('label') || field?.closest('fieldset');

    if (error) {
        error.textContent = message || '';
    }

    if (label) {
        label.classList.toggle('has-error', Boolean(message));
    }
}

function value(form, fieldName) {
    return form.querySelector(`[name="${fieldName}"]`)?.value.trim() || '';
}

function validateAuth(form, needsPasswordConfirmation) {
    const errors = {};

    if (form.querySelector('[name="name"]') && value(form, 'name').length < 2) {
        errors.name = 'Введите имя длиной минимум 2 символа.';
    }

    if (!emailPattern.test(value(form, 'email'))) {
        errors.email = 'Введите корректный email.';
    }

    if (form.querySelector('[name="password"]') && value(form, 'password').length < 8) {
        errors.password = 'Пароль должен содержать минимум 8 символов.';
    }

    if (needsPasswordConfirmation && value(form, 'password') !== value(form, 'password_confirmation')) {
        errors.password_confirmation = 'Пароли должны совпадать.';
    }

    return errors;
}

function validateProduct(form) {
    const errors = {};
    const price = Number(value(form, 'price').replace(',', '.'));
    const stock = Number(value(form, 'stock'));
    const imagePath = value(form, 'image_url');

    if (value(form, 'name').length < 3) {
        errors.name = 'Название должно содержать минимум 3 символа.';
    }

    if (!value(form, 'category_id')) {
        errors.category_id = 'Выберите категорию.';
    }

    if (!form.querySelector('[name="product_type"]:checked')) {
        errors.product_type = 'Выберите тип товара.';
    }

    if (!Number.isFinite(price) || price <= 0 || price > 50000) {
        errors.price = 'Цена должна быть больше 0 и не выше 50 000.';
    }

    if (!Number.isInteger(stock) || stock < 0 || stock > 10000) {
        errors.stock = 'Количество должно быть от 0 до 10 000.';
    }

    if (!(imagePath.startsWith('assets/') || imagePath.startsWith('http://') || imagePath.startsWith('https://'))) {
        errors.image_url = 'Укажите URL или путь из папки assets.';
    }

    if (value(form, 'description').length < 20) {
        errors.description = 'Описание должно содержать минимум 20 символов.';
    }

    return errors;
}

function validateOrder(form) {
    const errors = {};
    const quantityField = form.querySelector('[name="quantity"]');
    const quantity = Number(value(form, 'quantity'));
    const max = Number(quantityField?.max || '0');

    if (!Number.isInteger(quantity) || quantity < 1) {
        errors.quantity = 'Количество должно быть минимум 1.';
    }

    if (max > 0 && quantity > max) {
        errors.quantity = 'На складе нет такого количества товара.';
    }

    if (!telegramPattern.test(value(form, 'customer_telegram'))) {
        errors.customer_telegram = 'Введите Telegram username, например @botgear_user.';
    }

    if (!phonePattern.test(value(form, 'customer_phone'))) {
        errors.customer_phone = 'Введите корректный номер телефона.';
    }

    if (value(form, 'delivery_address').length < 8) {
        errors.delivery_address = 'Введите адрес доставки.';
    }

    if (value(form, 'note').length > 500) {
        errors.note = 'Комментарий не должен превышать 500 символов.';
    }

    return errors;
}

function validateCategory(form) {
    return value(form, 'name').length < 3
        ? { name: 'Название категории должно содержать минимум 3 символа.' }
        : {};
}

function validateForm(form) {
    const type = form.dataset.validate;

    if (type === 'product') {
        return validateProduct(form);
    }

    if (type === 'order') {
        return validateOrder(form);
    }

    if (type === 'category') {
        return validateCategory(form);
    }

    if (type === 'register') {
        return validateAuth(form, true);
    }

    if (type === 'login') {
        return validateAuth(form, false);
    }

    return {};
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-validate]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const errors = validateForm(form);
            const fields = new Set([
                ...Array.from(form.querySelectorAll('[name]')).map((field) => field.name),
                ...Object.keys(errors),
            ]);

            fields.forEach((fieldName) => setError(form, fieldName, errors[fieldName]));

            if (Object.keys(errors).length > 0) {
                event.preventDefault();
            }
        });
    });
});
