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
    <?php if (!empty($city['id'])): ?>
        <input type="hidden" name="id" value="<?= e((string) $city['id']) ?>">
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-field">
            <label class="form-label" for="state_id">State</label>
            <select class="form-select" id="state_id" name="state_id" required>
                <option value="">Select state</option>
                <?php foreach ($states as $state): ?>
                    <?php $stateLabel = trim((string) ($state['name'] ?? '')); ?>
                    <?php if (!empty($state['country_name'])) { $stateLabel .= ' (' . $state['country_name'] . ')'; } ?>
                    <option value="<?= e((string) $state['id']) ?>" <?= (int) ($city['state_id'] ?? 0) === (int) $state['id'] ? 'selected' : '' ?>>
                        <?= e($stateLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label class="form-label" for="name">City Name</label>
            <input class="form-control" id="name" name="name" type="text" maxlength="100" value="<?= e((string) ($city['name'] ?? '')) ?>" required>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-dark" type="submit"><?= e($submitLabel) ?></button>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/cities/index.php">Cancel</a>
    </div>
</form>
