<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? APP_NAME;
$currentAdmin = admin();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">
<?php if ($flash): ?>
    <div
        id="app-flash"
        data-type="<?= e((string) $flash['type']) ?>"
        data-message="<?= e((string) $flash['message']) ?>"
        hidden
    ></div>
<?php endif; ?>
<?php if (isAdminLoggedIn()): ?>
<div class="admin-shell">
    <?php require BASE_PATH . '/admin/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <p class="eyebrow mb-1">Property Dealer Admin</p>
                <h1 class="page-heading mb-0"><?= e($pageTitle) ?></h1>
            </div>
            <div class="admin-topbar-meta">
                <a class="btn btn-primary admin-topbar-action" href="<?= ADMIN_URL ?>/properties/create.php">
                    <i class="bi bi-plus-circle me-1"></i>Add Property
                </a>
                <div class="admin-chip">
                    <span class="admin-chip-label">Signed in as</span>
                    <strong><?= e($currentAdmin['name'] ?? 'Admin') ?></strong>
                </div>
                <a class="btn btn-dark" href="<?= ADMIN_URL ?>/logout.php">Logout</a>
            </div>
        </header>
<?php else: ?>
<main class="auth-shell">
<?php endif; ?>
