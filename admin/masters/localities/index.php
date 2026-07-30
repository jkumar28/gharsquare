<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$pageTitle = 'Localities';
$localities = localitiesAll();
$localityCount = countTableRows('localities');
$cityCount = countTableRows('cities');

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="stats-grid stats-grid-compact">
    <article class="stat-card">
        <span class="stat-label">Localities</span>
        <h2><?= e((string) $localityCount) ?></h2>
        <p>Neighborhood and area records used for detailed property placement.</p>
    </article>
    <article class="stat-card">
        <span class="stat-label">Cities</span>
        <h2><?= e((string) $cityCount) ?></h2>
        <p>Parent cities available for mapping localities.</p>
    </article>
</section>

<section class="panel-card">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Location Masters</p>
            <h3>Manage Localities</h3>
            <p class="panel-copy mb-0">Localities create the neighborhood-level search and listing experience.</p>
        </div>
        <div class="page-tools">
            <a class="btn btn-dark" href="<?= ADMIN_URL ?>/masters/localities/create.php">Add Locality</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table js-datatable align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>Locality</th>
                <th>City</th>
                <th>State</th>
                <th>Pincode</th>
                <th>Linked Properties</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($localities): ?>
                <?php foreach ($localities as $locality): ?>
                    <tr>
                        <td>#<?= e((string) $locality['id']) ?></td>
                        <td><strong><?= e((string) $locality['name']) ?></strong></td>
                        <td><?= e((string) ($locality['city_name'] ?? 'Unknown')) ?></td>
                        <td><?= e((string) ($locality['state_name'] ?? 'Unknown')) ?></td>
                        <td><?= e((string) ($locality['pincode'] ?? '-')) ?></td>
                        <td><?= e((string) $locality['property_count']) ?></td>
                        <td class="text-end">
                            <div class="table-actions">
                                <a class="btn btn-sm btn-outline-dark icon-action-btn" href="<?= ADMIN_URL ?>/masters/localities/edit.php?id=<?= e((string) $locality['id']) ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit locality" aria-label="Edit locality"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                                <form method="post" action="<?= ADMIN_URL ?>/masters/localities/delete.php" data-confirm="Delete this locality?" data-loading-text="Deleting locality...">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= e((string) $locality['id']) ?>">
                                    <button class="btn btn-sm btn-outline-danger icon-action-btn" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete locality" aria-label="Delete locality"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-panel">
                            <h4>No localities found</h4>
                            <p>Add localities after cities are ready so properties can be tagged with precise areas.</p>
                            <a class="btn btn-dark" href="<?= ADMIN_URL ?>/masters/localities/create.php">Create Locality</a>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
