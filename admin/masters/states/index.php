<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$pageTitle = 'States';
$states = statesAll();
$stateCount = countTableRows('states');
$countryCount = countTableRows('countries');
$cityCount = countTableRows('cities');

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="stats-grid stats-grid-compact">
    <article class="stat-card">
        <span class="stat-label">States</span>
        <h2><?= e((string) $stateCount) ?></h2>
        <p>State and province records available in the system.</p>
    </article>
    <article class="stat-card">
        <span class="stat-label">Countries</span>
        <h2><?= e((string) $countryCount) ?></h2>
        <p>Parent countries connected to these states.</p>
    </article>
    <article class="stat-card">
        <span class="stat-label">Linked Cities</span>
        <h2><?= e((string) $cityCount) ?></h2>
        <p>Cities already mapped under the state hierarchy.</p>
    </article>
</section>

<section class="panel-card">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Location Masters</p>
            <h3>Manage States</h3>
            <p class="panel-copy mb-0">Keep state records mapped to the correct country for clean property location flows.</p>
        </div>
        <div class="page-tools">
            <a class="btn btn-dark" href="<?= ADMIN_URL ?>/masters/states/create.php">Add State</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table js-datatable align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>State</th>
                <th>Country</th>
                <th>Cities</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($states): ?>
                <?php foreach ($states as $state): ?>
                    <tr>
                        <td>#<?= e((string) $state['id']) ?></td>
                        <td><strong><?= e((string) $state['name']) ?></strong></td>
                        <td><?= e((string) ($state['country_name'] ?? 'Unknown')) ?></td>
                        <td><?= e((string) $state['city_count']) ?></td>
                        <td class="text-end">
                            <div class="table-actions">
                                <a class="btn btn-sm btn-outline-dark icon-action-btn" href="<?= ADMIN_URL ?>/masters/states/edit.php?id=<?= e((string) $state['id']) ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit state" aria-label="Edit state"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                                <form method="post" action="<?= ADMIN_URL ?>/masters/states/delete.php" data-confirm="Delete this state?" data-loading-text="Deleting state...">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= e((string) $state['id']) ?>">
                                    <button class="btn btn-sm btn-outline-danger icon-action-btn" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete state" aria-label="Delete state"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">
                        <div class="empty-panel">
                            <h4>No states found</h4>
                            <p>Add states after creating at least one country.</p>
                            <a class="btn btn-dark" href="<?= ADMIN_URL ?>/masters/states/create.php">Create State</a>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
