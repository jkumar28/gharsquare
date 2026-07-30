<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$pageTitle = 'Dashboard';
$stats = adminStatCounts();
$drafts = recentDrafts();

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="stats-grid">
    <article class="stat-card">
        <span class="stat-label">Total Users</span>
        <h2><?= e((string) $stats['total_users']) ?></h2>
        <p>Registered users across owners, agents, builders, and admins.</p>
    </article>

    <article class="stat-card">
        <span class="stat-label">Active Properties</span>
        <h2><?= e((string) $stats['active_properties']) ?></h2>
        <p>Listings currently live on the platform.</p>
    </article>

    <article class="stat-card">
        <span class="stat-label">Pending Approval</span>
        <h2><?= e((string) $stats['pending_properties']) ?></h2>
        <p>Properties waiting for admin review and moderation.</p>
    </article>

    <article class="stat-card">
        <span class="stat-label">Total Leads</span>
        <h2><?= e((string) $stats['total_leads']) ?></h2>
        <p>Enquiries collected from interested buyers and tenants.</p>
    </article>
</section>

<section class="content-grid">
    <div class="panel-card">
        <div class="panel-head">
            <div>
                <p class="eyebrow mb-1">Quick Start</p>
                <h3>Recommended build order</h3>
            </div>
        </div>
        <div class="timeline-list">
            <div><strong>1.</strong> Add admin account and secure login flow.</div>
            <div><strong>2.</strong> Build master CRUD for property types and amenities.</div>
            <div><strong>3.</strong> Add users list and property moderation screens.</div>
            <div><strong>4.</strong> Add leads handling, reports, and site settings.</div>
        </div>
    </div>

    <div class="panel-card">
        <div class="panel-head">
            <div>
                <p class="eyebrow mb-1">Recent Activity</p>
                <h3>Latest Drafts</h3>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead>
                <tr>
                    <th>Draft</th>
                    <th>Title</th>
                    <th>User</th>
                    <th>Progress</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($drafts): ?>
                    <?php foreach ($drafts as $draft): ?>
                        <tr>
                            <td>#<?= e((string) $draft['id']) ?></td>
                            <td><?= e($draft['title'] ?: 'Untitled draft') ?></td>
                            <td><?= e($draft['user_name'] ?: 'Unknown') ?></td>
                            <td><?= e((string) $draft['completion_percent']) ?>%</td>
                            <td>
                                <span class="status-pill <?= (int) $draft['is_submitted'] === 1 ? 'submitted' : 'draft' ?>">
                                    <?= (int) $draft['is_submitted'] === 1 ? 'Submitted' : 'Draft' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No draft data available yet.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
