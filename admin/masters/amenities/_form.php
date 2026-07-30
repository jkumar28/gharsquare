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
    <?php if (!empty($amenity['id'])): ?>
        <input type="hidden" name="id" value="<?= e((string) $amenity['id']) ?>">
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-field">
            <label class="form-label" for="name">Amenity Name</label>
            <input class="form-control" id="name" name="name" type="text" maxlength="100" value="<?= e((string) ($amenity['name'] ?? '')) ?>" required>
        </div>
        <div class="form-field">
            <label class="form-label" for="category">Category</label>
            <input class="form-control" id="category" name="category" type="text" maxlength="100" list="amenity-categories" value="<?= e((string) ($amenity['category'] ?? '')) ?>">
            <datalist id="amenity-categories">
                <?php foreach ($categories as $value => $label): ?>
                    <option value="<?= e((string) $value) ?>"><?= e((string) $label) ?></option>
                <?php endforeach; ?>
            </datalist>
        </div>
        <div class="form-field">
            <label class="form-label" for="icon">Icon</label>
            <input class="form-control" id="icon" name="icon" type="text" maxlength="100" value="<?= e((string) ($amenity['icon'] ?? '')) ?>">
            <div class="field-hint">Store an icon class, slug, or filename depending on how you plan to render icons.</div>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-dark" type="submit"><?= e($submitLabel) ?></button>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/amenities/index.php">Cancel</a>
    </div>
</form>
