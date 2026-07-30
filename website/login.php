<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/public_auth.php';

$mode = ($_GET['mode'] ?? '') === 'register' ? 'register' : 'login';
publicAuthRememberRedirect($_GET['redirect'] ?? null);

if (isPostRequest()) {
    $action = (string) ($_POST['action'] ?? '');
    $errors = [];

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Security token expired. Please try again.';
    }

    if ($action === 'password_login' && !$errors) {
        $login = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        setOldInput(['login' => $login]);

        if ($login === '' || $password === '') {
            $errors[] = 'Please enter your email/phone and password.';
        } else {
            $user = findPublicUserByLogin($login);

            if (!$user || !password_verify($password, (string) $user['password'])) {
                $errors[] = 'Invalid login details.';
            } elseif ((string) $user['status'] !== 'active') {
                $errors[] = 'This account is not active.';
            } elseif (($user['email'] ?? '') !== '' && (int) ($user['email_verified'] ?? 0) !== 1) {
                $otp = publicAuthIssueEmailOtp($user, 'login');
                setFlash('warning', publicAuthOtpMessage($otp));
                redirect('verify-otp');
            } else {
                loginPublicUser($user);
                setFlash('success', 'Welcome back, ' . (string) $user['name'] . '.');
                publicAuthRedirectAfterLogin();
            }
        }

        if ($errors) {
            setFormErrors($errors);
            redirect('login');
        }
    }

    if ($action === 'request_login_otp' && !$errors) {
        $email = strtolower(trim((string) ($_POST['otp_email'] ?? '')));
        setOldInput(['otp_email' => $email]);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid registered email address.';
        } else {
            $user = findPublicUserByEmail($email);

            if (!$user) {
                $errors[] = 'No active account found with this email.';
            } elseif ((string) $user['status'] !== 'active') {
                $errors[] = 'This account is not active.';
            } else {
                $otp = publicAuthIssueEmailOtp($user, 'login');
                setFlash('success', publicAuthOtpMessage($otp));
                redirect('verify-otp');
            }
        }

        if ($errors) {
            setFormErrors($errors);
            redirect('login');
        }
    }

    if ($action === 'register' && !$errors) {
        $validation = validatePublicRegisterInput($_POST);
        $data = $validation['data'];
        $errors = $validation['errors'];
        setOldInput([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'role' => $data['role'],
        ]);

        if (!$errors) {
            $userId = createPublicUser($data);
            $user = findUser($userId);

            if (!$user) {
                $errors[] = 'Account created, but verification could not start. Please contact support.';
            } else {
                $otp = publicAuthIssueEmailOtp($user, 'register');
                setFlash('success', 'Account created. ' . publicAuthOtpMessage($otp));
                redirect('verify-otp');
            }
        }

        if ($errors) {
            setFormErrors($errors);
            redirect('login?mode=register');
        }
    }

    if ($errors) {
        setFormErrors($errors);
        redirect('login');
    }
}

$flash = getFlash();
$errors = getFormErrors();
$roles = publicAuthRoles();
$currentUser = publicUser();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login or Register - GharSquare</title>
    <meta name="description" content="Login or register on GharSquare with email OTP verification.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body class="auth-page">
    <nav class="navbar navbar-expand-lg fixed-top premium-navbar">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand" href="./" aria-label="GharSquare home">
                <span class="logo-icon"><i class="bi bi-house-fill"></i></span>
                Ghar<span>Square</span>
            </a>
            <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav mx-auto gap-lg-4">
                    <li><a class="nav-link" href="./">Home</a></li>
                    <li><a class="nav-link" href="listing?type=buy">Buyers</a></li>
                    <li><a class="nav-link" href="listing?type=rent">Tenants</a></li>
                    <li><a class="nav-link" href="./#owner-cta">Owners</a></li>
                </ul>
                <a href="post-property" class="post-btn">Post Property <small>Free</small></a>
            </div>
        </div>
    </nav>

    <main class="auth-main">
        <section class="auth-shell">
            <div class="auth-copy">
                <a href="./" class="back-link"><i class="bi bi-arrow-left"></i> Back to home</a>
                <span class="auth-kicker">Secure Access</span>
                <h1>Login, verify, and continue your property journey.</h1>
                <p>Create a GharSquare account with email OTP verification, or login with password or one-time code.</p>
                <div class="auth-feature-list">
                    <span><i class="bi bi-patch-check-fill"></i> Verified email accounts</span>
                    <span><i class="bi bi-heart-fill"></i> Saved properties ready</span>
                    <span><i class="bi bi-send-fill"></i> Faster enquiries</span>
                </div>
            </div>

            <div class="auth-panel">
                <?php if ($flash): ?>
                    <div class="auth-alert <?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div>
                <?php endif; ?>

                <?php if ($errors): ?>
                    <div class="auth-alert danger">
                        <?php foreach ($errors as $error): ?>
                            <div><?= e((string) $error) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($currentUser): ?>
                    <div class="signed-in-box">
                        <i class="bi bi-person-check-fill"></i>
                        <div>
                            <h2>You are signed in</h2>
                            <p><?= e((string) $currentUser['name']) ?> | <?= e((string) $currentUser['email']) ?></p>
                        </div>
                        <a href="logout">Logout</a>
                    </div>
                <?php endif; ?>

                <div class="auth-tabs">
                    <a class="<?= $mode === 'login' ? 'active' : '' ?>" href="login">Login</a>
                    <a class="<?= $mode === 'register' ? 'active' : '' ?>" href="login?mode=register">Register</a>
                </div>

                <?php if ($mode === 'login'): ?>
                    <div class="auth-form-grid">
                        <form method="post" action="login" class="auth-card">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="password_login">
                            <h2>Password Login</h2>
                            <p>Use email or phone with your password.</p>
                            <label for="login">Email or Phone</label>
                            <input id="login" name="login" type="text" value="<?= e((string) old('login')) ?>" required>
                            <label for="password">Password</label>
                            <input id="password" name="password" type="password" required>
                            <button type="submit">Login</button>
                        </form>

                        <form method="post" action="login" class="auth-card">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="request_login_otp">
                            <h2>Email OTP Login</h2>
                            <p>Receive a one-time code on your registered email.</p>
                            <label for="otp_email">Registered Email</label>
                            <input id="otp_email" name="otp_email" type="email" value="<?= e((string) old('otp_email')) ?>" required>
                            <button type="submit">Send OTP</button>
                        </form>
                    </div>
                <?php else: ?>
                    <form method="post" action="login?mode=register" class="auth-card register-card">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="register">
                        <h2>Create Account</h2>
                        <p>Email verification is required before your account becomes ready for enquiries and property posting.</p>

                        <div class="auth-form-grid two">
                            <div>
                                <label for="name">Full Name</label>
                                <input id="name" name="name" type="text" maxlength="150" value="<?= e((string) old('name')) ?>" required>
                            </div>
                            <div>
                                <label for="email">Email</label>
                                <input id="email" name="email" type="email" maxlength="150" value="<?= e((string) old('email')) ?>" required>
                            </div>
                            <div>
                                <label for="phone">Phone</label>
                                <input id="phone" name="phone" type="text" maxlength="20" value="<?= e((string) old('phone')) ?>">
                            </div>
                            <div>
                                <label for="role">Account Type</label>
                                <select id="role" name="role" required>
                                    <?php foreach ($roles as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= old('role', 'customer') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="register_password">Password</label>
                                <input id="register_password" name="password" type="password" required>
                            </div>
                            <div>
                                <label for="confirm_password">Confirm Password</label>
                                <input id="confirm_password" name="confirm_password" type="password" required>
                            </div>
                        </div>

                        <button type="submit">Create & Verify</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
