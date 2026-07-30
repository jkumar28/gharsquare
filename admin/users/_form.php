<?php

declare(strict_types=1);

$roles = userRoles();
$statuses = userStatuses();
?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>Please fix the following issues:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= e((string) $error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= e($formAction) ?>" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <?php if (!empty($user['id'])): ?>
        <input type="hidden" name="id" value="<?= e((string) $user['id']) ?>">
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-field">
            <label class="" for="name">Full Name</label>
            <input class="form-control" id="name" name="name" type="text" maxlength="150" value="<?= e((string) ($user['name'] ?? '')) ?>" required>
        </div>
        <div class="form-field">
            <label class="" for="email">Email</label>
            <input class="form-control" id="email" name="email" type="email" maxlength="150" value="<?= e((string) ($user['email'] ?? '')) ?>">
        </div>
        <div class="form-field">
            <label class="" for="phone">Phone</label>
            <input class="form-control" id="phone" name="phone" type="text" maxlength="20" value="<?= e((string) ($user['phone'] ?? '')) ?>">
        </div>
        <div class="form-field">
            <label class="" for="password"><?= !empty($user['id']) ? 'Password (Leave blank to keep current)' : 'Password' ?></label>
            <input class="form-control" id="password" name="password" type="password" <?= empty($user['id']) ? 'required' : '' ?>>
        </div>
        <div class="form-field">
            <label class="" for="role">Role</label>
            <select class="form-select" id="role" name="role" required>
                <?php foreach ($roles as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($user['role'] ?? 'owner') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label class="" for="status">Status</label>
            <select class="form-select" id="status" name="status" required>
                <?php foreach ($statuses as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($user['status'] ?? 'active') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="email_verified" name="email_verified" value="1" <?= (int) ($user['email_verified'] ?? 0) === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="email_verified">Mark email as verified</label>
    </div>

    <div class="form-actions">
        <button class="btn btn-dark" type="submit"><?= e($submitLabel) ?></button>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/users/index.php">Cancel</a>
    </div>
</form>
