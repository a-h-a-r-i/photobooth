<?php

namespace Photobooth\Private;

/**
 * Uploads images to Cloudinary using the REST upload API.
 * No SDK dependency — uses curl directly.
 */
class CloudinaryUploader
{
    private string $cloudName;
    private string $apiKey;
    private string $apiSecret;

    public function __construct(string $cloudName, string $apiKey, string $apiSecret)
    {
        $this->cloudName = $cloudName;
        $this->apiKey    = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    /**
     * Upload a local file to Cloudinary.
     * Returns the secure URL on success, or null on failure.
     */
    public function upload(string $filePath, string $folder = 'photobooth'): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $timestamp  = time();
        $params     = [
            'folder'    => $folder,
            'timestamp' => $timestamp,
        ];

        // Build signature
        ksort($params);
        $signatureStr = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $signature    = sha1($signatureStr . $this->apiSecret);

        $postFields = array_merge($params, [
            'api_key'   => $this->apiKey,
            'signature' => $signature,
            'file'      => new \CURLFile($filePath),
        ]);

        $url = "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $data = json_decode($response, true);

        return $data['secure_url'] ?? null;
    }

    /**
     * Async upload — fire and forget via background process.
     * Does not block the main request.
     */
    public function uploadAsync(string $filePath, string $logFile = '/tmp/cloudinary_upload.log'): void
    {
        $script = __DIR__ . '/cloudinary_upload_worker.php';
        $cmd = sprintf(
            'php %s %s >> %s 2>&1 &',
            escapeshellarg($script),
            escapeshellarg($filePath),
            escapeshellarg($logFile)
        );
        exec($cmd);
    }
}
