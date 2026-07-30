<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

if (isAdminLoggedIn()) {
    redirect(ADMIN_URL . '/index.php');
}

$pageTitle = 'Admin Login';

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="auth-card-wrap">
    <div class="auth-card">
        <div class="auth-copy">
            <p class="eyebrow">GharSquare Admin</p>
            <h1>Control your property marketplace from one place.</h1>
            <p class="auth-text">
                Start with admin moderation, master data, users, listings, and leads before building the public portal.
            </p>
        </div>

        <form class="auth-form" method="post" action="<?= ADMIN_URL ?>/auth/process-login.php">
            <h2>Admin Sign In</h2>

            <div class="mb-3">
                <label for="login">Email or Phone</label>
                <input class="form-control" id="login" name="login" type="text" value="<?= e(old('login')) ?>" required>
            </div>

            <div class="mb-4">
                <label for="password">Password</label>
                <input class="form-control" id="password" name="password" type="password" required>
            </div>

            <button class="btn btn-dark w-100" type="submit">Login to Dashboard</button>

            <div class="auth-help">
                <strong>Setup note:</strong> make sure at least one record in `users` has `role = 'admin'`.
            </div>
        </form>
    </div>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
