<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

if (preg_match('~/index\.(?:php|html)$~i', (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH))) {
    header('Location: ' . APP_URL . '/', true, 301);
    exit;
}

require __DIR__ . '/website/index.php';
