<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/public_auth.php';

$pending = publicAuthPendingVerification();

if (!$pending) {
    setFlash('warning', 'Please login or register to request an OTP.');
    redirect('login');
}

$user = findUser((int) $pending['user_id']);

if (!$user || (string) ($user['status'] ?? '') !== 'active') {
    unset($_SESSION['pending_email_verification']);
    setFlash('danger', 'This verification request is no longer valid.');
    redirect('login');
}

if (isPostRequest()) {
    $action = (string) ($_POST['action'] ?? 'verify');
    $errors = [];

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Security token expired. Please try again.';
    }

    if ($action === 'resend' && !$errors) {
        $identifier = (string) $user['id'];
        if (authRateLimitStatus('public_otp_resend', $identifier)['blocked']) {
            $errors[] = 'Too many resend requests. Please wait before requesting another code.';
        } else {
            $otp = publicAuthIssueEmailOtp($user, (string) $pending['purpose']);
            authRateLimitHit('public_otp_resend', $identifier, 3, 600, 600);
            setFlash('success', publicAuthOtpMessage($otp));
            redirect('verify-otp');
        }
    }

    if ($action === 'verify' && !$errors) {
        $otp = preg_replace('/\D+/', '', (string) ($_POST['otp'] ?? ''));
        $identifier = (string) $user['id'];

        if (!is_string($otp) || strlen($otp) !== 6) {
            $errors[] = 'Please enter the 6 digit OTP.';
        } elseif (authRateLimitStatus('public_otp_verify', $identifier)['blocked']) {
            $errors[] = 'Too many verification attempts. Please request a new code later.';
        } elseif (!publicAuthVerifyEmailOtp((int) $user['id'], $otp)) {
            authRateLimitHit('public_otp_verify', $identifier, 10, 900, 900);
            $errors[] = 'Invalid or expired OTP.';
        } else {
            authRateLimitClear('public_otp_verify', $identifier);
            authRateLimitClear('public_otp_resend', $identifier);
            $verifiedUser = findUser((int) $user['id']);

            if (!$verifiedUser) {
                $errors[] = 'Verification completed, but login failed. Please login again.';
            } else {
                unset($_SESSION['pending_email_verification']);
                loginPublicUser($verifiedUser);
                setFlash('success', 'Email verified successfully.');
                publicAuthRedirectAfterLogin();
            }
        }
    }

    if ($errors) {
        setFormErrors($errors);
        redirect('verify-otp');
    }
}

$flash = getFlash();
$errors = getFormErrors();
$purposeLabel = ($pending['purpose'] ?? '') === 'login' ? 'Login verification' : 'Email verification';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Verify OTP - GharSquare</title>
    <meta name="description" content="Verify your GharSquare account with email OTP.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= e(APP_URL) ?>/website/assets/css/style.css?v=<?= e((string) filemtime(__DIR__ . '/assets/css/style.css')) ?>" rel="stylesheet">
</head>

<body class="auth-page">
    <nav class="navbar navbar-expand-lg fixed-top premium-navbar">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand" href="./" aria-label="GharSquare home">
                <span class="logo-icon"><i class="bi bi-house-fill"></i></span>
                Ghar<span>Square</span>
            </a>
            <div class="ms-auto">
                <a href="login" class="post-btn">Login</a>
            </div>
        </div>
    </nav>

    <main class="auth-main verify-main">
        <section class="verify-card">
            <div class="verify-icon"><i class="bi bi-envelope-check"></i></div>
            <span class="auth-kicker"><?= e($purposeLabel) ?></span>
            <h1>Enter the 6 digit OTP</h1>
            <p>We generated a one-time code for <strong><?= e((string) $user['email']) ?></strong>. The code expires in <?= e((string) PUBLIC_AUTH_OTP_MINUTES) ?> minutes.</p>

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

            <form method="post" action="verify-otp" class="otp-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="verify">
                <label for="otp">Verification Code</label>
                <input id="otp" name="otp" type="text" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" required>
                <button type="submit">Verify & Continue</button>
            </form>

            <form method="post" action="verify-otp" class="resend-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="resend">
                <button type="submit">Resend OTP</button>
            </form>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
