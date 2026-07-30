<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$pageTitle = 'Countries';
$countries = countriesAll();
$countryCount = countTableRows('countries');
$stateCount = countTableRows('states');

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="stats-grid stats-grid-compact">
    <article class="stat-card">
        <span class="stat-label">Countries</span>
        <h2><?= e((string) $countryCount) ?></h2>
        <p>Top-level geography records for the portal.</p>
    </article>
    <article class="stat-card">
        <span class="stat-label">Linked States</span>
        <h2><?= e((string) $stateCount) ?></h2>
        <p>States currently connected under country masters.</p>
    </article>
</section>

<section class="panel-card">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Location Masters</p>
            <h3>Manage Countries</h3>
            <p class="panel-copy mb-0">Countries drive the full location hierarchy for properties and search filters.</p>
        </div>
        <div class="page-tools">
            <a class="btn btn-dark" href="<?= ADMIN_URL ?>/masters/countries/create.php">Add Country</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table js-datatable align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>States</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($countries): ?>
                <?php foreach ($countries as $country): ?>
                    <tr>
                        <td>#<?= e((string) $country['id']) ?></td>
                        <td><strong><?= e((string) $country['name']) ?></strong></td>
                        <td><?= e((string) $country['state_count']) ?></td>
                        <td class="text-end">
                            <div class="table-actions">
                                <a class="btn btn-sm btn-outline-dark icon-action-btn" href="<?= ADMIN_URL ?>/masters/countries/edit.php?id=<?= e((string) $country['id']) ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit country" aria-label="Edit country"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                                <form method="post" action="<?= ADMIN_URL ?>/masters/countries/delete.php" data-confirm="Delete this country?" data-loading-text="Deleting country...">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= e((string) $country['id']) ?>">
                                    <button class="btn btn-sm btn-outline-danger icon-action-btn" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete country" aria-label="Delete country"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">
                        <div class="empty-panel">
                            <h4>No countries found</h4>
                            <p>Add your first country before creating states, cities, and localities.</p>
                            <a class="btn btn-dark" href="<?= ADMIN_URL ?>/masters/countries/create.php">Create Country</a>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
