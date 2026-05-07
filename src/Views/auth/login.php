<section class="auth-shell">
    <div>
        <p class="eyebrow">Доступ к аккаунту</p>
        <h1>Вход</h1>
        <p>Войдите, чтобы оформить заказ и смотреть историю заявок.</p>
    </div>

    <form class="form-panel" method="post" data-validate="login">
        <?= \BotGear\Core\Csrf::input() ?>
        <label>
            Email
            <input type="email" name="email" value="<?= h(field_value($old, 'email')) ?>" required>
            <small class="field-error" data-error-for="email"><?= h($errors['email'] ?? '') ?></small>
        </label>
        <label>
            Пароль
            <input type="password" name="password" required>
            <small class="field-error" data-error-for="password"><?= h($errors['password'] ?? '') ?></small>
        </label>
        <button class="button" type="submit">Войти</button>
        <p class="form-note">
            <a href="<?= h(url('forgot')) ?>">Восстановить пароль</a>
            <span>или</span>
            <a href="<?= h(url('register')) ?>">зарегистрироваться</a>
        </p>
    </form>
</section>
