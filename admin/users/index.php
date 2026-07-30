<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$pageTitle = 'Users';
$summary = usersSummary();
$users = usersAll();
$roles = userRoles();

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="stats-grid stats-grid-compact">
    <article class="stat-card">
        <span class="stat-label">Total Users</span>
        <h2 data-users-summary="total"><?= e((string) $summary['total']) ?></h2>
        <p>All accounts created across the platform.</p>
    </article>
    <article class="stat-card">
        <span class="stat-label">Active</span>
        <h2 data-users-summary="active"><?= e((string) $summary['active']) ?></h2>
        <p>Users currently allowed to sign in and post.</p>
    </article>
    <article class="stat-card">
        <span class="stat-label">Blocked</span>
        <h2 data-users-summary="blocked"><?= e((string) $summary['blocked']) ?></h2>
        <p>Accounts disabled by admin moderation.</p>
    </article>
    <article class="stat-card">
        <span class="stat-label">Admins</span>
        <h2 data-users-summary="admins"><?= e((string) $summary['admins']) ?></h2>
        <p>Administrative accounts with dashboard access.</p>
    </article>
</section>

<section class="panel-card">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">User Management</p>
            <h3>Manage Users</h3>
            <p class="panel-copy mb-0">Use client-side DataTables here for fast admin search. When properties and users scale into the thousands, we can switch properties to server-side tables later.</p>
        </div>
        <div class="page-tools">
            <a class="btn btn-dark" href="<?= ADMIN_URL ?>/users/create.php">Add User</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table js-datatable align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Role</th>
                <th>Status</th>
                <th>Verified</th>
                <th>Drafts</th>
                <th>Properties</th>
                <th>Leads</th>
                <th>Last Login</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($users): ?>
                <?php foreach ($users as $user): ?>
                    <tr data-row-id="<?= e((string) $user['id']) ?>">
                        <td>#<?= e((string) $user['id']) ?></td>
                        <td>
                            <strong><?= e((string) $user['name']) ?></strong>
                            <div class="table-subtext"><?= e((string) ($user['email'] ?: $user['phone'] ?: 'No contact info')) ?></div>
                        </td>
                        <td><span class="<?= e(userRoleBadgeClass((string) $user['role'])) ?>"><?= e($roles[$user['role']] ?? ucfirst((string) $user['role'])) ?></span></td>
                        <td data-col="status"><?= userStatusHtml((string) $user['status']) ?></td>
                        <td data-col="verified"><?= userVerifiedHtml((int) $user['email_verified']) ?></td>
                        <td><?= e((string) $user['draft_count']) ?></td>
                        <td><?= e((string) $user['property_count']) ?></td>
                        <td><?= e((string) $user['lead_count']) ?></td>
                        <td><?= e((string) ($user['last_login'] ?: '-')) ?></td>
                        <td class="text-end" data-col="actions"><?= userActionsHtml($user) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10">
                        <div class="empty-panel">
                            <h4>No users found</h4>
                            <p>Create the first user or admin account to start managing the portal.</p>
                            <a class="btn btn-dark" href="<?= ADMIN_URL ?>/users/create.php">Create User</a>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
