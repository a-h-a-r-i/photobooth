<?php

/**
 * Background worker: uploads a single image to Cloudinary
 * and stores the resulting URL in a sidecar JSON file.
 *
 * Usage: php cloudinary_upload_worker.php /path/to/image.jpg
 */

require_once __DIR__ . '/../lib/boot.php';
require_once __DIR__ . '/CloudinaryUploader.php';

use Photobooth\Private\CloudinaryUploader;

if (empty($argv[1]) || !file_exists($argv[1])) {
    echo date('Y-m-d H:i:s') . " ERROR: no valid file path given\n";
    exit(1);
}

$filePath = $argv[1];

// Read credentials from config
$cloudinaryConfigFile = dirname(__DIR__) . '/../config/cloudinary.config.php';
if (!file_exists($cloudinaryConfigFile)) {
    echo date('Y-m-d H:i:s') . " ERROR: cloudinary.config.php not found\n";
    exit(1);
}
$cloudinaryConfig = require $cloudinaryConfigFile;

$cloudName = $cloudinaryConfig['cloud_name'] ?? '';
$apiKey    = $cloudinaryConfig['api_key']    ?? '';
$apiSecret = $cloudinaryConfig['api_secret'] ?? '';
$folder    = $cloudinaryConfig['folder']     ?? 'photobooth';

if (!$cloudName || !$apiKey || !$apiSecret) {
    echo date('Y-m-d H:i:s') . " ERROR: Cloudinary credentials not configured\n";
    exit(1);
}

$uploader = new CloudinaryUploader($cloudName, $apiKey, $apiSecret);
$url = $uploader->upload($filePath, $folder);

if ($url) {
    // Save URL to sidecar file next to the image
    $sidecar = $filePath . '.cloudinary.json';
    file_put_contents($sidecar, json_encode(['url' => $url, 'uploaded_at' => date('c')]));
    echo date('Y-m-d H:i:s') . " OK: {$url}\n";
} else {
    echo date('Y-m-d H:i:s') . " ERROR: upload failed for {$filePath}\n";
    exit(1);
}
