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
    <?php if (!empty($locality['id'])): ?>
        <input type="hidden" name="id" value="<?= e((string) $locality['id']) ?>">
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-field">
            <label class="form-label" for="city_id">City</label>
            <select class="form-select" id="city_id" name="city_id" required>
                <option value="">Select city</option>
                <?php foreach ($cities as $city): ?>
                    <?php $cityLabel = trim((string) ($city['name'] ?? '')); ?>
                    <?php if (!empty($city['state_name'])) { $cityLabel .= ' (' . $city['state_name'] . ')'; } ?>
                    <option value="<?= e((string) $city['id']) ?>" <?= (int) ($locality['city_id'] ?? 0) === (int) $city['id'] ? 'selected' : '' ?>>
                        <?= e($cityLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label class="form-label" for="name">Locality Name</label>
            <input class="form-control" id="name" name="name" type="text" maxlength="150" value="<?= e((string) ($locality['name'] ?? '')) ?>" required>
        </div>
        <div class="form-field">
            <label class="form-label" for="pincode">Pincode</label>
            <input class="form-control" id="pincode" name="pincode" type="text" maxlength="10" value="<?= e((string) ($locality['pincode'] ?? '')) ?>">
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-dark" type="submit"><?= e($submitLabel) ?></button>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/localities/index.php">Cancel</a>
    </div>
</form>
