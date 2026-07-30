<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/public_auth.php';
require_once BASE_PATH . '/includes/property.php';

header('Content-Type: application/json; charset=utf-8');

function postPropertyMediaResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function currentPublicMediaDraft(int $draftId): array
{
    $user = publicUser();

    if (!$user || $draftId <= 0) {
        throw new RuntimeException('Draft not found.');
    }

    $draft = findPropertyDraft($draftId);

    if (!$draft || (int) ($draft['user_id'] ?? 0) !== (int) $user['id']) {
        throw new RuntimeException('Draft not found.');
    }

    return $draft;
}

function publicMediaPayload(int $draftId, string $message): array
{
    $bundle = getPropertyDraftBundle($draftId);
    saveDraftProgress($draftId, $bundle, 'media');
    $bundle = getPropertyDraftBundle($draftId);

    return [
        'success' => true,
        'message' => $message,
        'grid_html' => propertyPublicMediaGridHtml($bundle['media']),
        'progress' => propertyProgressPayload($draftId),
        'image_count' => propertyImageCount($bundle['media']),
    ];
}

if (!isPublicUserLoggedIn()) {
    postPropertyMediaResponse([
        'success' => false,
        'login_required' => true,
        'login_url' => publicAuthLoginUrl(publicAuthCurrentUrl()),
    ], 401);
}

if (!isPostRequest()) {
    postPropertyMediaResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    postPropertyMediaResponse(['success' => false, 'message' => 'Security token expired. Please refresh.'], 419);
}

$draftId = (int) ($_POST['draft_id'] ?? 0);
$action = trim((string) ($_POST['action'] ?? 'upload'));

try {
    currentPublicMediaDraft($draftId);

    if ($action === 'delete') {
        $mediaId = (int) ($_POST['media_id'] ?? 0);
        deletePropertyMediaRecord($draftId, $mediaId);
        recordUserActivity('property_media_delete', [
            'entity_type' => 'property_draft',
            'entity_id' => (string) $draftId,
            'metadata' => ['media_id' => $mediaId],
        ]);

        postPropertyMediaResponse(publicMediaPayload($draftId, 'Media removed.'));
    }

    if ($action === 'set_cover') {
        $mediaId = (int) ($_POST['media_id'] ?? 0);
        setPropertyMediaAsCover($draftId, $mediaId);
        recordUserActivity('property_media_cover_update', [
            'entity_type' => 'property_draft',
            'entity_id' => (string) $draftId,
            'metadata' => ['media_id' => $mediaId],
        ]);

        postPropertyMediaResponse(publicMediaPayload($draftId, 'Cover photo updated.'));
    }

    if ($action === 'set_photo_type') {
        $mediaId = (int) ($_POST['media_id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        updatePropertyMediaTitle($draftId, $mediaId, $title);
        recordUserActivity('property_media_type_update', [
            'entity_type' => 'property_draft',
            'entity_id' => (string) $draftId,
            'metadata' => ['media_id' => $mediaId, 'photo_type' => $title],
        ]);

        postPropertyMediaResponse(publicMediaPayload($draftId, 'Photo type updated.'));
    }

    $currentMedia = propertyDraftMedia($draftId);
    $currentImageCount = propertyImageCount($currentMedia);
    $uploadKind = trim((string) ($_POST['upload_kind'] ?? ''));
    $youtubeUrl = trim((string) ($_POST['youtube_url'] ?? ''));
    $normalizedYoutubeUrl = null;

    if ($uploadKind === 'youtube') {
        $normalizedYoutubeUrl = normalizeYoutubeUrl($youtubeUrl);

        if ($normalizedYoutubeUrl === null) {
            postPropertyMediaResponse(['success' => false, 'message' => 'Please enter a valid YouTube URL.'], 422);
        }

        addPropertyMediaRecord($draftId, $normalizedYoutubeUrl, 'video', 0, [
            'source_type' => 'youtube',
            'video_provider' => 'youtube',
            'sort_order' => propertyNextMediaSortOrder($draftId),
        ]);

        recordUserActivity('property_media_upload', [
            'entity_type' => 'property_draft',
            'entity_id' => (string) $draftId,
            'metadata' => ['kind' => 'youtube'],
        ]);

        postPropertyMediaResponse(publicMediaPayload($draftId, 'YouTube video added.'));
    }

    $fileField = match ($uploadKind) {
        'image' => 'image_files',
        'video' => 'video_files',
        default => '',
    };

    if ($fileField === '' || empty($_FILES[$fileField]['name'][0])) {
        postPropertyMediaResponse(['success' => false, 'message' => 'Please choose media files to upload.'], 422);
    }

    $names = $_FILES[$fileField]['name'];
    $tmpNames = $_FILES[$fileField]['tmp_name'];
    $errors = $_FILES[$fileField]['error'];
    $types = $_FILES[$fileField]['type'];
    $sizes = $_FILES[$fileField]['size'] ?? [];
    $uploadedKinds = [];

    for ($index = 0; $index < count($names); $index++) {
        if ((int) $errors[$index] !== UPLOAD_ERR_OK) {
            continue;
        }

        $file = [
            'name' => $names[$index],
            'tmp_name' => $tmpNames[$index],
            'type' => $types[$index],
        ];
        $mime = mime_content_type($file['tmp_name']) ?: $file['type'];

        if (str_starts_with((string) $mime, 'image/')) {
            if ($uploadKind !== 'image') {
                postPropertyMediaResponse(['success' => false, 'message' => 'Please upload images in the image box.'], 422);
            }

            if ($currentImageCount >= 20) {
                postPropertyMediaResponse(['success' => false, 'message' => 'Maximum 20 images are allowed per listing.'], 422);
            }

            $url = optimizeImageToWebp($file, $draftId);
            $optimizedPath = str_replace('/', DIRECTORY_SEPARATOR, str_replace(propertyUploadBaseUrl(), propertyUploadBasePath(), $url));
            addPropertyMediaRecord($draftId, $url, 'image', $currentImageCount === 0 ? 1 : 0, [
                'source_type' => 'upload',
                'mime_type' => 'image/webp',
                'file_size' => is_file($optimizedPath) ? filesize($optimizedPath) : null,
                'title' => detectPropertyPhotoTypeFromFilename((string) $file['name']),
                'sort_order' => propertyNextMediaSortOrder($draftId),
            ]);
            $currentImageCount++;
            $uploadedKinds[] = 'image';
        } elseif (str_starts_with((string) $mime, 'video/')) {
            if ($uploadKind !== 'video') {
                postPropertyMediaResponse(['success' => false, 'message' => 'Please upload videos in the video box.'], 422);
            }

            $uploadedSize = isset($sizes[$index]) ? (int) $sizes[$index] : (is_file($file['tmp_name']) ? filesize($file['tmp_name']) : null);

            if ($uploadedSize !== null && $uploadedSize > propertyVideoMaxBytes()) {
                postPropertyMediaResponse(['success' => false, 'message' => 'Video size must be 20 MB or less.'], 422);
            }

            $url = storeVideoUpload($file, $draftId);
            addPropertyMediaRecord($draftId, $url, 'video', 0, [
                'source_type' => 'upload',
                'mime_type' => $mime,
                'file_size' => $uploadedSize,
                'sort_order' => propertyNextMediaSortOrder($draftId),
            ]);
            $uploadedKinds[] = 'video';
        } else {
            postPropertyMediaResponse(['success' => false, 'message' => 'Unsupported media format.'], 422);
        }
    }

    if (!$uploadedKinds) {
        postPropertyMediaResponse(['success' => false, 'message' => 'No valid media file was uploaded.'], 422);
    }

    recordUserActivity('property_media_upload', [
        'entity_type' => 'property_draft',
        'entity_id' => (string) $draftId,
        'metadata' => ['kinds' => array_values(array_unique($uploadedKinds))],
    ]);

    postPropertyMediaResponse(publicMediaPayload($draftId, 'Media uploaded successfully.'));
} catch (Throwable $exception) {
    postPropertyMediaResponse(['success' => false, 'message' => $exception->getMessage()], 422);
}
