<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$pageTitle = 'Property Types';
$summary = propertyTypesSummary();
$propertyTypes = propertyTypesAll();
$categories = propertyTypeCategories();

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="stats-grid stats-grid-compact">
    <article class="stat-card">
        <span class="stat-label">Total Types</span>
        <h2><?= e((string) $summary['total']) ?></h2>
        <p>All property type options available in the platform.</p>
    </article>

    <article class="stat-card">
        <span class="stat-label">Residential</span>
        <h2><?= e((string) $summary['residential']) ?></h2>
        <p>Flats, houses, villas, builder floors, and more.</p>
    </article>

    <article class="stat-card">
        <span class="stat-label">Commercial</span>
        <h2><?= e((string) $summary['commercial']) ?></h2>
        <p>Office, shop, showroom, warehouse, and business assets.</p>
    </article>

    <article class="stat-card">
        <span class="stat-label">Land</span>
        <h2><?= e((string) $summary['land']) ?></h2>
        <p>Plots, agricultural land, and development inventory.</p>
    </article>
</section>

<section class="panel-card">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Master Setup</p>
            <h3>Manage Property Types</h3>
            <p class="panel-copy mb-0">Create clean listing options before building property posting and moderation modules.</p>
        </div>
        <div class="page-tools">
            <a class="btn btn-dark" href="<?= ADMIN_URL ?>/masters/property-types/create.php">Add Property Type</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table js-datatable align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Category</th>
                <th>Linked Listings</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($propertyTypes): ?>
                <?php foreach ($propertyTypes as $propertyType): ?>
                    <tr>
                        <td>#<?= e((string) $propertyType['id']) ?></td>
                        <td>
                            <strong><?= e((string) $propertyType['name']) ?></strong>
                        </td>
                        <td>
                            <span class="category-pill <?= e((string) $propertyType['category']) ?>">
                                <?= e($categories[$propertyType['category']] ?? ucfirst((string) $propertyType['category'])) ?>
                            </span>
                        </td>
                        <td><?= e((string) $propertyType['usage_count']) ?></td>
                        <td class="text-end">
                            <div class="table-actions">
                                <a class="btn btn-sm btn-outline-dark icon-action-btn" href="<?= ADMIN_URL ?>/masters/property-types/edit.php?id=<?= e((string) $propertyType['id']) ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit property type" aria-label="Edit property type"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                                <form method="post" action="<?= ADMIN_URL ?>/masters/property-types/delete.php" data-confirm="Delete this property type?" data-loading-text="Deleting property type...">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= e((string) $propertyType['id']) ?>">
                                    <button class="btn btn-sm btn-outline-danger icon-action-btn" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete property type" aria-label="Delete property type"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">
                        <div class="empty-panel">
                            <h4>No property types yet</h4>
                            <p>Add your first property type to prepare listing forms for owners, agents, and builders.</p>
                            <a class="btn btn-dark" href="<?= ADMIN_URL ?>/masters/property-types/create.php">Create First Type</a>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
