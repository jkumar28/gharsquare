<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /admin/\n";
echo "Disallow: /account\n";
echo "Disallow: /login\n";
echo "Disallow: /verify-otp\n";
echo 'Sitemap: ' . APP_URL . "/sitemap.xml\n";
