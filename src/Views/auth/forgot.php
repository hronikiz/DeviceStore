<section class="auth-shell">
    <div>
        <p class="eyebrow">Доступ</p>
        <h1>Восстановление пароля</h1>
        <p>Введите email, и система создаст ссылку для смены пароля.</p>
    </div>

    <form class="form-panel" method="post" data-validate="login">
        <?= \BotGear\Core\Csrf::input() ?>
        <label>
            Email
            <input type="email" name="email" value="<?= h(field_value($old, 'email')) ?>" required>
            <small class="field-error" data-error-for="email"><?= h($errors['email'] ?? '') ?></small>
        </label>
        <button class="button" type="submit">Создать ссылку</button>
        <?php if ($resetUrl !== null): ?>
            <div class="demo-link">
                <span>Ссылка для восстановления:</span>
                <a href="<?= h($resetUrl) ?>"><?= h($resetUrl) ?></a>
            </div>
        <?php endif; ?>
    </form>
</section>
