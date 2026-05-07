<section class="auth-shell">
    <div>
        <p class="eyebrow">Дополнительное задание</p>
        <h1>Восстановление пароля</h1>
        <p>В учебном режиме ссылка показывается на странице вместо отправки email.</p>
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
                <span>Демо-ссылка:</span>
                <a href="<?= h($resetUrl) ?>"><?= h($resetUrl) ?></a>
            </div>
        <?php endif; ?>
    </form>
</section>
