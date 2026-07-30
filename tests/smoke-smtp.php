<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require dirname(__DIR__) . '/config/config.php';
require BASE_PATH . '/vendor/autoload.php';

if (MAIL_HOST === '' || MAIL_USERNAME === '' || MAIL_PASSWORD === '') {
    throw new RuntimeException('SMTP credentials are incomplete.');
}

$mailer = new PHPMailer(true);
$mailer->isSMTP();
$mailer->Host = MAIL_HOST;
$mailer->Port = MAIL_PORT;
$mailer->SMTPAuth = true;
$mailer->Username = MAIL_USERNAME;
$mailer->Password = MAIL_PASSWORD;
$mailer->Timeout = 20;
$mailer->SMTPKeepAlive = false;

if (MAIL_ENCRYPTION === 'ssl' || MAIL_ENCRYPTION === 'smtps') {
    $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
} elseif (MAIL_ENCRYPTION !== 'none') {
    $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
}

if (!$mailer->smtpConnect()) {
    throw new RuntimeException('SMTP connection or authentication failed.');
}

$mailer->smtpClose();
echo 'SMTP connection and authentication passed.' . PHP_EOL;
