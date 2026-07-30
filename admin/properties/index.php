<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/property.php';

requireAdminAuth();

$pageTitle = 'Properties';
$summary = propertyDraftSummary();
$drafts = propertyDraftsAll();

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="stats-grid stats-grid-compact">
    <article class="stat-card">
        <span class="stat-label">Drafts</span>
        <h2 data-property-summary="drafts"><?= e((string) $summary['drafts']) ?></h2>
        <p>Draft listings created in the step-by-step wizard.</p>
    </article>
    <article class="stat-card">
        <span class="stat-label">Submitted</span>
        <h2 data-property-summary="submitted"><?= e((string) $summary['submitted']) ?></h2>
        <p>Drafts ready for review or publication workflow.</p>
    </article>
    <article class="stat-card">
        <span class="stat-label">Published</span>
        <h2 data-property-summary="published"><?= e((string) $summary['published']) ?></h2>
        <p>Currently active property listings.</p>
    </article>
    <article class="stat-card">
        <span class="stat-label">Rejected</span>
        <h2 data-property-summary="rejected"><?= e((string) $summary['rejected']) ?></h2>
        <p>Listings sent back with moderation feedback.</p>
    </article>
    <article class="stat-card">
        <span class="stat-label">Avg Completion</span>
        <h2><span data-property-summary="avg_completion"><?= e((string) $summary['avg_completion']) ?></span>%</h2>
        <p>Average listing completeness across all drafts.</p>
    </article>
</section>

<section class="panel-card">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Property Wizard</p>
            <h3>Manage Property Listings</h3>
            <p class="panel-copy mb-0">This starts the same kind of staged listing flow you want: multi-step, percentage-based, and AJAX-first.</p>
        </div>
        <div class="page-tools">
            <a class="btn btn-dark" href="<?= ADMIN_URL ?>/properties/create.php">Create Property Draft</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table js-datatable align-middle">
            <thead>
            <tr>
                <th>Draft</th>
                <th>Title</th>
                <th>User</th>
                <th>Type</th>
                <th>Listing</th>
                <th>Progress</th>
                <th>Status</th>
                <th>Updated</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($drafts): ?>
                <?php foreach ($drafts as $draft): ?>
                    <tr data-row-id="<?= e((string) $draft['id']) ?>">
                        <td>#<?= e((string) $draft['id']) ?></td>
                        <td><strong><?= e((string) ($draft['title'] ?: 'Untitled draft')) ?></strong></td>
                        <td><?= e((string) ($draft['user_name'] ?: 'Unassigned')) ?></td>
                        <td><?= e((string) ($draft['property_type_name'] ?: '-')) ?></td>
                        <td><?= e((string) ($draft['listing_type_name'] ?: '-')) ?></td>
                        <td><?= e((string) number_format((float) $draft['completion_percent'], 0)) ?>%</td>
                        <td data-col="status"><?= propertyDraftStatusHtml($draft) ?></td>
                        <td><?= e((string) $draft['updated_at']) ?></td>
                        <td class="text-end" data-col="actions"><?= propertyListActionsHtml($draft) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9">
                        <div class="empty-panel">
                            <h4>No property drafts found</h4>
                            <p>Create the first property draft to start the staged listing flow.</p>
                            <a class="btn btn-dark" href="<?= ADMIN_URL ?>/properties/create.php">Create Property Draft</a>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
