<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/public_site.php';

requireAdminAuth();

$pageTitle = 'Property Enquiries';
$status = strtolower(trim((string) ($_GET['status'] ?? '')));
$allowedStatuses = ['new', 'contacted', 'closed', 'cancelled'];
$status = in_array($status, $allowedStatuses, true) ? $status : '';
$params = [];
$where = '';
if ($status !== '') {
    $where = ' WHERE pe.status = :status';
    $params[':status'] = $status;
}

$stmt = db()->prepare(
    'SELECT pe.*, customer.name AS user_name, owner.name AS owner_name, p.slug
     FROM property_enquiries pe
     LEFT JOIN users customer ON customer.id = pe.user_id
     LEFT JOIN users owner ON owner.id = pe.owner_user_id
     LEFT JOIN properties p ON p.id = pe.property_id'
    . $where .
    ' ORDER BY pe.created_at DESC, pe.id DESC'
);
$stmt->execute($params);
$enquiries = $stmt->fetchAll();

$summary = ['new' => 0, 'contacted' => 0, 'closed' => 0, 'cancelled' => 0];
foreach (db()->query('SELECT status, COUNT(*) AS total FROM property_enquiries GROUP BY status')->fetchAll() as $row) {
    $summary[(string) $row['status']] = (int) $row['total'];
}

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="stats-grid stats-grid-compact">
    <?php foreach ($summary as $key => $total): ?>
        <article class="stat-card">
            <span class="stat-label"><?= e(ucfirst($key)) ?></span>
            <h2><?= e((string) $total) ?></h2>
            <p><?= $key === 'new' ? 'Waiting for first follow-up.' : 'Enquiries marked ' . e($key) . '.' ?></p>
        </article>
    <?php endforeach; ?>
</section>

<section class="panel-card">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Lead Inbox</p>
            <h3>Property Enquiries</h3>
            <p class="panel-copy mb-0">Every request is tied to a live property and keeps owner contact details private.</p>
        </div>
        <div class="page-tools">
            <a class="btn <?= $status === '' ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?= ADMIN_URL ?>/enquiries/index.php">All</a>
            <a class="btn <?= $status === 'new' ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?= ADMIN_URL ?>/enquiries/index.php?status=new">New</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table js-datatable align-middle">
            <thead>
            <tr>
                <th>Enquiry</th>
                <th>Property</th>
                <th>Customer</th>
                <th>Request</th>
                <th>Assigned To</th>
                <th>Mail</th>
                <th>Status</th>
                <th>Received</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($enquiries as $enquiry): ?>
                <tr>
                    <td>
                        <strong>#<?= e((string) $enquiry['id']) ?></strong>
                        <?php if (($enquiry['source'] ?? '') !== ''): ?><div class="table-subtext"><?= e((string) $enquiry['source']) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <?php if (($enquiry['slug'] ?? '') !== ''): ?>
                            <a href="<?= e(sitePropertyUrl(['slug' => (string) $enquiry['slug']])) ?>" target="_blank" rel="noopener">
                                <strong><?= e((string) (($enquiry['title'] ?? '') ?: 'Property')) ?></strong>
                            </a>
                        <?php else: ?>
                            <strong><?= e((string) (($enquiry['title'] ?? '') ?: 'Unavailable property')) ?></strong>
                        <?php endif; ?>
                        <div class="table-subtext"><?= e(trim((string) ($enquiry['locality'] ?? '') . ', ' . (string) ($enquiry['city'] ?? ''), ', ')) ?></div>
                    </td>
                    <td>
                        <strong><?= e((string) (($enquiry['contact_name'] ?? '') ?: $enquiry['user_name'])) ?></strong>
                        <div class="table-subtext"><?= e((string) ($enquiry['contact_phone'] ?? '')) ?></div>
                        <div class="table-subtext"><?= e((string) ($enquiry['contact_email'] ?? '')) ?></div>
                    </td>
                    <td>
                        <strong><?= e(ucfirst((string) (($enquiry['enquiry_type'] ?? '') ?: 'Enquiry'))) ?></strong>
                        <div class="table-subtext">Via <?= e(ucfirst((string) (($enquiry['preferred_contact'] ?? '') ?: 'Not set'))) ?></div>
                        <?php if (($enquiry['message'] ?? '') !== ''): ?>
                            <details class="enquiry-message-details">
                                <summary>View message</summary>
                                <p><?= nl2br(e((string) $enquiry['message'])) ?></p>
                            </details>
                        <?php endif; ?>
                    </td>
                    <td><?= e((string) (($enquiry['owner_name'] ?? '') ?: 'Admin team')) ?></td>
                    <td>
                        <span class="status-badge status-<?= e((string) $enquiry['notification_status']) ?>">
                            <?= e(ucfirst((string) $enquiry['notification_status'])) ?>
                        </span>
                        <?php if (($enquiry['notification_error'] ?? '') !== ''): ?>
                            <div class="table-subtext" title="<?= e((string) $enquiry['notification_error']) ?>">Delivery details available</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" action="<?= ADMIN_URL ?>/enquiries/update-status.php" class="enquiry-status-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="enquiry_id" value="<?= e((string) $enquiry['id']) ?>">
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <?php foreach ($allowedStatuses as $option): ?>
                                    <option value="<?= e($option) ?>" <?= $enquiry['status'] === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td><?= e(date('d M Y, h:i A', strtotime((string) $enquiry['created_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($enquiries === []): ?>
                <tr><td colspan="8"><div class="empty-panel"><h4>No enquiries found</h4><p>New website enquiries will appear here automatically.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
