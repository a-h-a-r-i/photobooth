<?php

/**
 * Override gallery API to include Cloudinary-uploaded images.
 * Scans local images + reads sidecar .cloudinary.json files.
 */

use Photobooth\Enum\FolderEnum;

$imagesDir = FolderEnum::IMAGES->absolute();
$cloudinaryImages = [];

if (is_dir($imagesDir)) {
    $files = glob($imagesDir . DIRECTORY_SEPARATOR . '*.cloudinary.json');
    if ($files) {
        foreach ($files as $sidecar) {
            $data = json_decode(file_get_contents($sidecar), true);
            if (!empty($data['url'])) {
                $cloudinaryImages[] = [
                    'url'         => $data['url'],
                    'uploaded_at' => $data['uploaded_at'] ?? '',
                    'filename'    => basename(str_replace('.cloudinary.json', '', $sidecar)),
                ];
            }
        }
        // newest first
        usort($cloudinaryImages, fn($a, $b) => strcmp($b['uploaded_at'], $a['uploaded_at']));
    }
}

// Inject into response for JS to use
$GLOBALS['cloudinary_images'] = $cloudinaryImages;
