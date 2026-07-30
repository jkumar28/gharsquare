<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

require_once BASE_PATH . '/vendor/autoload.php';

function appMailLog(string $recipient, string $subject, string $html): bool
{
    $directory = BASE_PATH . '/storage/mail';
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    $entry = sprintf(
        "[%s] to=%s subject=%s%s%s%s",
        date('Y-m-d H:i:s'),
        $recipient,
        $subject,
        PHP_EOL,
        trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], PHP_EOL, $html))),
        PHP_EOL . str_repeat('-', 72) . PHP_EOL
    );
    if (@file_put_contents($directory . '/outbox.log', $entry, FILE_APPEND | LOCK_EX) !== false) {
        return true;
    }

    try {
        $stmt = db()->prepare(
            'INSERT INTO mail_outbox (recipient, subject, html_body, transport, status, created_at)
             VALUES (:recipient, :subject, :html_body, "log", "logged", NOW())'
        );
        $stmt->execute([
            ':recipient' => $recipient,
            ':subject' => $subject,
            ':html_body' => $html,
        ]);
        return true;
    } catch (Throwable $exception) {
        return false;
    }
}

function appSendMail(string $recipient, string $subject, string $html, string $plainText = ''): array
{
    $recipient = trim($recipient);
    if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        return ['sent' => false, 'mode' => MAIL_TRANSPORT, 'error' => 'Invalid recipient email address.'];
    }

    if (MAIL_TRANSPORT === 'log') {
        $logged = appMailLog($recipient, $subject, $html);
        return [
            'sent' => $logged,
            'mode' => 'log',
            'error' => $logged ? '' : 'Local mail outbox is not writable.',
        ];
    }

    try {
        $mailer = new PHPMailer(true);
        $mailer->CharSet = 'UTF-8';

        if (MAIL_TRANSPORT === 'smtp') {
            if (MAIL_HOST === '' || MAIL_USERNAME === '' || MAIL_PASSWORD === '') {
                throw new RuntimeException('SMTP credentials are not configured.');
            }

            $mailer->isSMTP();
            $mailer->Host = MAIL_HOST;
            $mailer->Port = MAIL_PORT;
            $mailer->SMTPAuth = true;
            $mailer->Username = MAIL_USERNAME;
            $mailer->Password = MAIL_PASSWORD;

            if (MAIL_ENCRYPTION === 'ssl' || MAIL_ENCRYPTION === 'smtps') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif (MAIL_ENCRYPTION !== 'none') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
        } else {
            $mailer->isMail();
        }

        $mailer->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mailer->addAddress($recipient);
        $mailer->isHTML(true);
        $mailer->Subject = $subject;
        $mailer->Body = $html;
        $mailer->AltBody = $plainText !== '' ? $plainText : trim(strip_tags($html));
        $mailer->send();

        return ['sent' => true, 'mode' => MAIL_TRANSPORT, 'error' => ''];
    } catch (MailerException | RuntimeException $exception) {
        return ['sent' => false, 'mode' => MAIL_TRANSPORT, 'error' => $exception->getMessage()];
    }
}

function appMailTemplate(string $heading, string $content, string $actionLabel = '', string $actionUrl = ''): string
{
    $action = '';
    if ($actionLabel !== '' && $actionUrl !== '') {
        $action = '<p style="margin:24px 0 0"><a href="' . e($actionUrl) . '" style="display:inline-block;background:#0f766e;color:#fff;text-decoration:none;padding:12px 18px;border-radius:6px;font-weight:600">' . e($actionLabel) . '</a></p>';
    }

    return '<!doctype html><html><body style="margin:0;background:#f4f6f8;font-family:Arial,sans-serif;color:#172033">'
        . '<div style="max-width:620px;margin:0 auto;padding:28px 16px">'
        . '<div style="background:#fff;border:1px solid #e3e7ee;border-radius:8px;padding:28px">'
        . '<p style="margin:0 0 8px;color:#0f766e;font-weight:700">GharSquare</p>'
        . '<h1 style="font-size:24px;margin:0 0 18px">' . e($heading) . '</h1>'
        . '<div style="font-size:15px;line-height:1.65">' . $content . '</div>'
        . $action
        . '</div></div></body></html>';
}
