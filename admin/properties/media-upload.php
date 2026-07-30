<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/property.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/properties/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    jsonResponse(['success' => false, 'message' => 'Your session token expired. Please refresh and try again.'], 419);
}

$draftId = (int) ($_POST['draft_id'] ?? 0);
$draft = $draftId > 0 ? findPropertyDraft($draftId) : null;

if (!$draft) {
    jsonResponse(['success' => false, 'message' => 'Property draft not found.'], 404);
}

try {
    $currentMedia = propertyDraftMedia($draftId);
    $currentImageCount = propertyImageCount($currentMedia);
    $uploadKind = trim((string) ($_POST['upload_kind'] ?? ''));
    $youtubeUrl = trim((string) ($_POST['youtube_url'] ?? ''));
    $normalizedYoutubeUrl = null;

    if ($uploadKind === 'youtube' && $youtubeUrl !== '') {
        $normalizedYoutubeUrl = normalizeYoutubeUrl($youtubeUrl);

        if ($normalizedYoutubeUrl === null) {
            jsonResponse(['success' => false, 'message' => 'Please enter a valid YouTube URL.'], 422);
        }
    }

    $fileField = match ($uploadKind) {
        'image' => 'image_files',
        'video' => 'video_files',
        default => 'media_files',
    };

    if (!empty($_FILES[$fileField]['name'][0])) {
        $names = $_FILES[$fileField]['name'];
        $tmpNames = $_FILES[$fileField]['tmp_name'];
        $errors = $_FILES[$fileField]['error'];
        $types = $_FILES[$fileField]['type'];
        $sizes = $_FILES[$fileField]['size'] ?? [];

        for ($index = 0; $index < count($names); $index++) {
            $file = [
                'name' => $names[$index],
                'tmp_name' => $tmpNames[$index],
                'type' => $types[$index],
                'error' => $errors[$index],
                'size' => $sizes[$index] ?? null,
            ];
            $inspection = inspectPropertyMediaUpload($file, $uploadKind);

            if ($inspection['kind'] === 'image') {
                if ($currentImageCount >= 20) {
                    jsonResponse(['success' => false, 'message' => 'Maximum 20 images are allowed per listing.'], 422);
                }

                $url = optimizeImageToWebp($file, $draftId);
                $optimizedPath = str_replace('/', DIRECTORY_SEPARATOR, str_replace(propertyUploadBaseUrl(), propertyUploadBasePath(), $url));
                $mediaId = addPropertyMediaRecord($draftId, $url, 'image', $currentImageCount === 0 ? 1 : 0, [
                    'source_type' => 'upload',
                    'mime_type' => 'image/webp',
                    'file_size' => is_file($optimizedPath) ? filesize($optimizedPath) : null,
                    'title' => detectPropertyPhotoTypeFromFilename((string) $file['name']),
                    'sort_order' => propertyNextMediaSortOrder($draftId),
                ]);
                $currentImageCount++;
            } else {
                $url = storeVideoUpload($file, $draftId, (string) $inspection['extension']);
                $mediaId = addPropertyMediaRecord($draftId, $url, 'video', 0, [
                    'source_type' => 'upload',
                    'mime_type' => $inspection['mime'],
                    'file_size' => $inspection['size'],
                    'sort_order' => propertyNextMediaSortOrder($draftId),
                ]);
            }
        }
    }

    if ($normalizedYoutubeUrl !== null) {
        $mediaId = addPropertyMediaRecord($draftId, $normalizedYoutubeUrl, 'video', 0, [
            'source_type' => 'youtube',
            'video_provider' => 'youtube',
            'sort_order' => propertyNextMediaSortOrder($draftId),
        ]);
    }

    if (empty($_FILES[$fileField]['name'][0]) && $normalizedYoutubeUrl === null) {
        jsonResponse(['success' => false, 'message' => $uploadKind === 'youtube' ? 'Please enter a YouTube link.' : 'Please choose files to upload.'], 422);
    }

    $bundle = getPropertyDraftBundle($draftId);
    saveDraftProgress($draftId, $bundle, 'media');

    jsonResponse([
        'success' => true,
        'message' => 'Media uploaded successfully.',
        'grid_html' => propertyMediaGridHtml($bundle['media']),
        'progress' => propertyProgressPayload($draftId),
    ]);
} catch (Throwable $exception) {
    jsonResponse(['success' => false, 'message' => 'Unable to upload media right now.'], 500);
}
