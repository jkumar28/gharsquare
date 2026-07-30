<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$pageTitle = 'Cities';
$cities = citiesAll();
$cityCount = countTableRows('cities');
$stateCount = countTableRows('states');
$localityCount = countTableRows('localities');

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="stats-grid stats-grid-compact">
    <article class="stat-card">
        <span class="stat-label">Cities</span>
        <h2><?= e((string) $cityCount) ?></h2>
        <p>City records used in property addresses and search filters.</p>
    </article>
    <article class="stat-card">
        <span class="stat-label">States</span>
        <h2><?= e((string) $stateCount) ?></h2>
        <p>Available parent states for city mapping.</p>
    </article>
    <article class="stat-card">
        <span class="stat-label">Localities</span>
        <h2><?= e((string) $localityCount) ?></h2>
        <p>Neighborhood records connected beneath cities.</p>
    </article>
</section>

<section class="panel-card">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Location Masters</p>
            <h3>Manage Cities</h3>
            <p class="panel-copy mb-0">Cities sit between state and locality levels in your property location structure.</p>
        </div>
        <div class="page-tools">
            <a class="btn btn-dark" href="<?= ADMIN_URL ?>/masters/cities/create.php">Add City</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table js-datatable align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>City</th>
                <th>State</th>
                <th>Country</th>
                <th>Localities</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($cities): ?>
                <?php foreach ($cities as $city): ?>
                    <tr>
                        <td>#<?= e((string) $city['id']) ?></td>
                        <td><strong><?= e((string) $city['name']) ?></strong></td>
                        <td><?= e((string) ($city['state_name'] ?? 'Unknown')) ?></td>
                        <td><?= e((string) ($city['country_name'] ?? 'Unknown')) ?></td>
                        <td><?= e((string) $city['locality_count']) ?></td>
                        <td class="text-end">
                            <div class="table-actions">
                                <a class="btn btn-sm btn-outline-dark icon-action-btn" href="<?= ADMIN_URL ?>/masters/cities/edit.php?id=<?= e((string) $city['id']) ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit city" aria-label="Edit city"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                                <form method="post" action="<?= ADMIN_URL ?>/masters/cities/delete.php" data-confirm="Delete this city?" data-loading-text="Deleting city...">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= e((string) $city['id']) ?>">
                                    <button class="btn btn-sm btn-outline-danger icon-action-btn" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete city" aria-label="Delete city"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">
                        <div class="empty-panel">
                            <h4>No cities found</h4>
                            <p>Add cities after preparing the country and state master data.</p>
                            <a class="btn btn-dark" href="<?= ADMIN_URL ?>/masters/cities/create.php">Create City</a>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
