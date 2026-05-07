<?php
$old = $old ?? [];
$errors = $errors ?? [];
?>
<section class="auth-shell">
    <div>
        <p class="eyebrow">Новый покупатель</p>
        <h1>Регистрация</h1>
        <p>Аккаунт нужен для доступа к защищенным заказам и оформления покупок.</p>
    </div>

    <form class="form-panel" method="post" data-validate="register">
        <?= \DeviceStore\Core\Csrf::input() ?>
        <label>
            Имя
            <input type="text" name="name" minlength="2" value="<?= h(field_value($old, 'name')) ?>" required>
            <small class="field-error" data-error-for="name"><?= h($errors['name'] ?? '') ?></small>
        </label>
        <label>
            Email
            <input type="email" name="email" value="<?= h(field_value($old, 'email')) ?>" required>
            <small class="field-error" data-error-for="email"><?= h($errors['email'] ?? '') ?></small>
        </label>
        <label>
            Пароль
            <input type="password" name="password" minlength="8" required>
            <small class="field-error" data-error-for="password"><?= h($errors['password'] ?? '') ?></small>
        </label>
        <label>
            Повторите пароль
            <input type="password" name="password_confirmation" minlength="8" required>
            <small class="field-error" data-error-for="password_confirmation"><?= h($errors['password_confirmation'] ?? '') ?></small>
        </label>
        <button class="button" type="submit">Создать аккаунт</button>
        <p class="form-note">Уже есть аккаунт? <a href="<?= h(url('login')) ?>">Войти</a></p>
    </form>
</section>
