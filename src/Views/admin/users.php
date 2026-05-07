<?php
$old = $old ?? [];
$errors = $errors ?? [];
$users = $users ?? [];
?>
<?php require BASE_PATH . '/src/Views/admin/_nav.php'; ?>

<section class="page-heading">
    <p class="eyebrow">Учетные записи</p>
    <h1>Администраторы и пользователи</h1>
    <p>Администратор может создавать новые учетные записи с ролью администратора.</p>
</section>

<form class="form-panel wide" method="post" data-validate="register">
    <?= \DeviceStore\Core\Csrf::input() ?>
    <h2>Создать администратора</h2>
    <div class="form-grid two">
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
    </div>
    <button class="button" type="submit">Создать администратора</button>
</form>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Имя</th>
                <th>Email</th>
                <th>Роль</th>
                <th>Создан</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $item): ?>
                <tr>
                    <td>#<?= h($item['id']) ?></td>
                    <td><?= h($item['name']) ?></td>
                    <td><?= h($item['email']) ?></td>
                    <td><span class="badge"><?= h($item['role']) ?></span></td>
                    <td><?= h(date('d.m.Y H:i', strtotime((string) $item['created_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
