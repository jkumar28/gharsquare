<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';
require BASE_PATH . '/includes/property.php';

$temporaryFiles = [];

try {
    $validImagePath = tempnam(sys_get_temp_dir(), 'gharsquare-image-');
    if ($validImagePath === false) {
        throw new RuntimeException('Unable to create a temporary image.');
    }
    $temporaryFiles[] = $validImagePath;
    $image = imagecreatetruecolor(32, 48);
    imagepng($image, $validImagePath);
    imagedestroy($image);

    $validImage = inspectPropertyMediaUpload([
        'name' => 'property.php.png',
        'tmp_name' => $validImagePath,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($validImagePath),
    ], 'image');

    if ($validImage['mime'] !== 'image/png' || $validImage['width'] !== 32 || $validImage['height'] !== 48) {
        throw new RuntimeException('A valid image was not inspected correctly.');
    }

    $optimizedUrl = optimizeImageToWebp([
        'name' => 'portrait.png',
        'tmp_name' => $validImagePath,
    ], 1);
    $optimizedPath = str_replace(
        '/',
        DIRECTORY_SEPARATOR,
        str_replace(propertyUploadBaseUrl(), propertyUploadBasePath(), $optimizedUrl)
    );
    $optimizedInfo = getimagesize($optimizedPath);

    if (
        $optimizedInfo === false
        || (int) $optimizedInfo[0] !== 1600
        || (int) $optimizedInfo[1] !== 1200
        || (int) $optimizedInfo[2] !== IMAGETYPE_WEBP
    ) {
        throw new RuntimeException('Optimized images are not standardized to 1600 × 1200 WebP.');
    }
    $temporaryFiles[] = $optimizedPath;

    $maliciousPath = tempnam(sys_get_temp_dir(), 'gharsquare-script-');
    if ($maliciousPath === false) {
        throw new RuntimeException('Unable to create a temporary invalid upload.');
    }
    $temporaryFiles[] = $maliciousPath;
    file_put_contents($maliciousPath, '<?php echo "executed";');

    foreach (['image', 'video'] as $kind) {
        try {
            inspectPropertyMediaUpload([
                'name' => $kind === 'image' ? 'photo.jpg' : 'tour.mp4',
                'tmp_name' => $maliciousPath,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($maliciousPath),
            ], $kind);
            throw new RuntimeException('A disguised executable was accepted as ' . $kind . '.');
        } catch (RuntimeException $exception) {
            if (str_starts_with($exception->getMessage(), 'A disguised executable')) {
                throw $exception;
            }
        }
    }

    try {
        inspectPropertyMediaUpload([
            'name' => 'missing.jpg',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
        ], 'image');
        throw new RuntimeException('An upload error was not rejected.');
    } catch (RuntimeException $exception) {
        if ($exception->getMessage() === 'An upload error was not rejected.') {
            throw $exception;
        }
    }

    if (propertyAllowedVideoMimes()['video/mp4'] !== 'mp4') {
        throw new RuntimeException('Video extensions are not normalized by server MIME.');
    }

    echo 'Upload security smoke test passed.' . PHP_EOL;
} finally {
    foreach ($temporaryFiles as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
}
