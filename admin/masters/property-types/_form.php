<?php

declare(strict_types=1);

$categories = propertyTypeCategories();
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
    <?php if (!empty($propertyType['id'])): ?>
        <input type="hidden" name="id" value="<?= e((string) $propertyType['id']) ?>">
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-field">
            <label class="form-label" for="name">Property Type Name</label>
            <input
                class="form-control"
                id="name"
                name="name"
                type="text"
                maxlength="100"
                value="<?= e((string) ($propertyType['name'] ?? '')) ?>"
                placeholder="e.g. Shop, Warehouse, Penthouse"
                required
            >
            <div class="field-hint">This label will appear in listing forms and admin filters.</div>
        </div>

        <div class="form-field">
            <label class="form-label" for="category">Category</label>
            <select class="form-select" id="category" name="category" required>
                <option value="">Select category</option>
                <?php foreach ($categories as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($propertyType['category'] ?? '') === $value ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="field-hint">Use residential, commercial, or land to keep filters consistent.</div>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-dark" type="submit"><?= e($submitLabel) ?></button>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/property-types/index.php">Cancel</a>
    </div>
</form>
