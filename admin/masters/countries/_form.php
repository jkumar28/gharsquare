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
    <?php if (!empty($country['id'])): ?>
        <input type="hidden" name="id" value="<?= e((string) $country['id']) ?>">
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-field">
            <label class="form-label" for="name">Country Name</label>
            <input class="form-control" id="name" name="name" type="text" maxlength="100" value="<?= e((string) ($country['name'] ?? '')) ?>" required>
            <div class="field-hint">Use official or business-friendly country names for the location tree.</div>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-dark" type="submit"><?= e($submitLabel) ?></button>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/countries/index.php">Cancel</a>
    </div>
</form>
