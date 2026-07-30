<?php

declare(strict_types=1);
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
    <?php if (!empty($state['id'])): ?>
        <input type="hidden" name="id" value="<?= e((string) $state['id']) ?>">
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-field">
            <label class="form-label" for="country_id">Country</label>
            <select class="form-select" id="country_id" name="country_id" required>
                <option value="">Select country</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?= e((string) $country['id']) ?>" <?= (int) ($state['country_id'] ?? 0) === (int) $country['id'] ? 'selected' : '' ?>>
                        <?= e((string) $country['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label class="form-label" for="name">State Name</label>
            <input class="form-control" id="name" name="name" type="text" maxlength="100" value="<?= e((string) ($state['name'] ?? '')) ?>" required>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-dark" type="submit"><?= e($submitLabel) ?></button>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/states/index.php">Cancel</a>
    </div>
</form>
