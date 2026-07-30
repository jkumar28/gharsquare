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
    <?php if (!empty($listingType['id'])): ?>
        <input type="hidden" name="id" value="<?= e((string) $listingType['id']) ?>">
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-field">
            <label class="form-label" for="name">Listing Type Name</label>
            <input class="form-control" id="name" name="name" type="text" maxlength="50" value="<?= e((string) ($listingType['name'] ?? '')) ?>" required>
            <div class="field-hint">Examples: Sell, Rent / Lease, PG.</div>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-dark" type="submit"><?= e($submitLabel) ?></button>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/listing-types/index.php">Cancel</a>
    </div>
</form>
