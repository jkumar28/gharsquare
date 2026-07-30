<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$pageTitle = 'Listing Types';
$listingTypes = listingTypesAll();
$listingTypeCount = countTableRows('listing_types');

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="stats-grid stats-grid-compact">
    <article class="stat-card">
        <span class="stat-label">Listing Types</span>
        <h2><?= e((string) $listingTypeCount) ?></h2>
        <p>Commercial labels that control whether a listing is for sale, rent, or other deal formats.</p>
    </article>
</section>

<section class="panel-card">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Listing Masters</p>
            <h3>Manage Listing Types</h3>
            <p class="panel-copy mb-0">Use these options in property posting and filter bars throughout the portal.</p>
        </div>
        <div class="page-tools">
            <a class="btn btn-dark" href="<?= ADMIN_URL ?>/masters/listing-types/create.php">Add Listing Type</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table js-datatable align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Linked Listings</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($listingTypes): ?>
                <?php foreach ($listingTypes as $listingType): ?>
                    <tr>
                        <td>#<?= e((string) $listingType['id']) ?></td>
                        <td><strong><?= e((string) $listingType['name']) ?></strong></td>
                        <td><?= e((string) $listingType['usage_count']) ?></td>
                        <td class="text-end">
                            <div class="table-actions">
                                <a class="btn btn-sm btn-outline-dark icon-action-btn" href="<?= ADMIN_URL ?>/masters/listing-types/edit.php?id=<?= e((string) $listingType['id']) ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit listing type" aria-label="Edit listing type"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                                <form method="post" action="<?= ADMIN_URL ?>/masters/listing-types/delete.php" data-confirm="Delete this listing type?" data-loading-text="Deleting listing type...">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= e((string) $listingType['id']) ?>">
                                    <button class="btn btn-sm btn-outline-danger icon-action-btn" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete listing type" aria-label="Delete listing type"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">
                        <div class="empty-panel">
                            <h4>No listing types found</h4>
                            <p>Add listing types before building property forms and filters.</p>
                            <a class="btn btn-dark" href="<?= ADMIN_URL ?>/masters/listing-types/create.php">Create Listing Type</a>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
