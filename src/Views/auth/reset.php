<section class="auth-shell">
    <div>
        <p class="eyebrow">Новый пароль</p>
        <h1>Сброс пароля</h1>
        <p>Ссылка действует 1 час, после успешного обновления токен удаляется.</p>
    </div>

    <form class="form-panel" method="post" data-validate="register">
        <?= \BotGear\Core\Csrf::input() ?>
        <input type="hidden" name="token" value="<?= h($token) ?>">
        <label>
            Новый пароль
            <input type="password" name="password" minlength="8" required>
            <small class="field-error" data-error-for="password"><?= h($errors['password'] ?? '') ?></small>
        </label>
        <label>
            Повторите пароль
            <input type="password" name="password_confirmation" minlength="8" required>
            <small class="field-error" data-error-for="password_confirmation"><?= h($errors['password_confirmation'] ?? '') ?></small>
        </label>
        <button class="button" type="submit">Сохранить пароль</button>
    </form>
</section>
