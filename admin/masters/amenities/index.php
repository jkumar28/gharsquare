<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$pageTitle = 'Amenities';
$amenities = amenitiesAll();
$amenityCount = countTableRows('amenities_master');
$categoryCount = count(amenityCategories());

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="stats-grid stats-grid-compact">
    <article class="stat-card">
        <span class="stat-label">Amenities</span>
        <h2><?= e((string) $amenityCount) ?></h2>
        <p>Facility and lifestyle options available in property posting flows.</p>
    </article>
    <article class="stat-card">
        <span class="stat-label">Categories</span>
        <h2><?= e((string) $categoryCount) ?></h2>
        <p>Existing amenity groups used to keep features organized.</p>
    </article>
</section>

<section class="panel-card">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Feature Masters</p>
            <h3>Manage Amenities</h3>
            <p class="panel-copy mb-0">Amenities help listings feel richer and give buyers better filters.</p>
        </div>
        <div class="page-tools">
            <a class="btn btn-dark" href="<?= ADMIN_URL ?>/masters/amenities/create.php">Add Amenity</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table js-datatable align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Category</th>
                <th>Icon</th>
                <th>Shown For</th>
                <th>Linked Drafts</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($amenities): ?>
                <?php foreach ($amenities as $amenity): ?>
                    <tr>
                        <td>#<?= e((string) $amenity['id']) ?></td>
                        <td><strong><?= e((string) $amenity['name']) ?></strong></td>
                        <td><?= e((string) ($amenity['category'] ?: '-')) ?></td>
                        <td><?= e((string) ($amenity['icon'] ?: '-')) ?></td>
                        <td><?= e(ucwords(str_replace(',', ', ', (string) ($amenity['applicable_categories'] ?? 'All')))) ?></td>
                        <td><?= e((string) $amenity['usage_count']) ?></td>
                        <td class="text-end">
                            <div class="table-actions">
                                <a class="btn btn-sm btn-outline-dark icon-action-btn" href="<?= ADMIN_URL ?>/masters/amenities/edit.php?id=<?= e((string) $amenity['id']) ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit amenity" aria-label="Edit amenity"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                                <form method="post" action="<?= ADMIN_URL ?>/masters/amenities/delete.php" data-confirm="Delete this amenity?" data-loading-text="Deleting amenity...">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= e((string) $amenity['id']) ?>">
                                    <button class="btn btn-sm btn-outline-danger icon-action-btn" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete amenity" aria-label="Delete amenity"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-panel">
                            <h4>No amenities found</h4>
                            <p>Add amenities before building the full property feature forms.</p>
                            <a class="btn btn-dark" href="<?= ADMIN_URL ?>/masters/amenities/create.php">Create Amenity</a>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
