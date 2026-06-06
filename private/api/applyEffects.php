<?php

/**
 * Private hook — runs inside applyEffects.php after image processing.
 * Triggers async Cloudinary upload for each captured image.
 */

use Photobooth\Enum\FolderEnum;

require_once dirname(__DIR__) . '/CloudinaryUploader.php';

use Photobooth\Private\CloudinaryUploader;

// Only run if Cloudinary is configured and enabled
$cloudinaryConfigFile = dirname(__DIR__) . '/../config/cloudinary.config.php';
if (!file_exists($cloudinaryConfigFile)) {
    return;
}
$cloudinaryConfig = require $cloudinaryConfigFile;

if (
    empty($cloudinaryConfig['enabled']) ||
    empty($cloudinaryConfig['cloud_name']) ||
    empty($cloudinaryConfig['api_key']) ||
    empty($cloudinaryConfig['api_secret'])
) {
    return;
}

// Collect the files that will be saved by the main applyEffects flow
$filesToUpload = [];
foreach ($vars['srcImages'] as $imageFile) {
    $filesToUpload[] = FolderEnum::IMAGES->absolute() . DIRECTORY_SEPARATOR . $imageFile;
}

register_shutdown_function(function () use ($filesToUpload, $cloudinaryConfig) {
    // Flush response to browser first
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    $uploader = new CloudinaryUploader(
        $cloudinaryConfig['cloud_name'],
        $cloudinaryConfig['api_key'],
        $cloudinaryConfig['api_secret']
    );

    $folder = $cloudinaryConfig['folder'] ?? 'photobooth';

    foreach ($filesToUpload as $filePath) {
        // Wait up to 10s for the file to be written by the main flow
        $waited = 0;
        while (!file_exists($filePath) && $waited < 10) {
            sleep(1);
            $waited++;
        }

        if (!file_exists($filePath)) {
            continue;
        }

        $url = $uploader->upload($filePath, $folder);

        if ($url) {
            // Store URL as sidecar so result page / gallery can use it
            file_put_contents(
                $filePath . '.cloudinary.json',
                json_encode(['url' => $url, 'uploaded_at' => date('c')])
            );
        }
    }
});
