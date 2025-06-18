<?php
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
}

function getFolderLastModified($s3, $bucket, $prefix) {
    $result = $s3->listObjectsV2([
        'Bucket' => $bucket,
        'Prefix' => $prefix,
    ]);

    $latest = null;
    if (!empty($result['Contents'])) {
        foreach ($result['Contents'] as $object) {
            if (!$latest || $object['LastModified'] > $latest) {
                $latest = $object['LastModified'];
            }
        }
    }
    return $latest;
}

$bucket = $_ENV['AWS_BUCKET'];
$prefix = isset($_GET['prefix']) ? $_GET['prefix'] : '';

$s3 = new S3Client([
    'version'     => 'latest',
    'region'      => $_ENV['AWS_REGION'],
    'endpoint'    => $_ENV['AWS_ENDPOINT'],
    'use_path_style_endpoint' => filter_var($_ENV['AWS_USE_PATH_STYLE'], FILTER_VALIDATE_BOOLEAN),
    'credentials' => [
        'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
        'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
    ],
]);

// 🔒 Block direct access to root-level PRIVATE_ folders
$segments = explode('/', trim($prefix, '/'));
if (count($segments) === 1 && str_starts_with($segments[0], 'PRIVATE_')) {
    http_response_code(403);
    echo "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body style='font-family: sans-serif; text-align: center; margin-top: 50px;'><h1>403 Forbidden</h1><p>You cannot access this folder directly.</p></body></html>";
    exit;
}

$result = $s3->listObjectsV2([
    'Bucket' => $bucket,
    'Prefix' => $prefix,
    'Delimiter' => '/',
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MinIO Browser</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2 class="mb-3">Browsing Bucket: <code><?= htmlspecialchars($bucket) ?></code></h2>

    <?php if ($prefix): ?>
        <a class="btn btn-secondary btn-sm mb-3" href="?prefix=<?= urlencode(dirname(rtrim($prefix, '/')) !== '.' ? dirname(rtrim($prefix, '/')) . '/' : '') ?>">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    <?php endif; ?>

    <div class="list-group shadow-sm">
        <?php
        // Folders
        if (!empty($result['CommonPrefixes'])) {
            foreach ($result['CommonPrefixes'] as $folder) {
                $dir = $folder['Prefix'];
                $folderName = basename(rtrim($dir, '/'));

                // HIDE PRIVATE_ folders ONLY at root level
                if (!$prefix && str_starts_with($folderName, 'PRIVATE_')) {
                    continue;
                }

                $lastModified = getFolderLastModified($s3, $bucket, $dir);
                echo "<a href='?prefix=" . urlencode($dir) . "' class='list-group-item list-group-item-action d-flex justify-content-between align-items-center'>
                        <div><i class='fa fa-folder text-warning me-2'></i> $folderName/</div>
                        <small class='text-muted'>Last Modified: " . ($lastModified ? $lastModified->format('Y-m-d H:i:s') : "unknown") . "</small>
                      </a>";
            }
        }

        // Files
        if (!empty($result['Contents'])) {
            foreach ($result['Contents'] as $file) {
                if ($file['Key'] === $prefix) continue;

                $fileName = basename($file['Key']);
                $fileSize = formatBytes($file['Size']);
                $lastModified = $file['LastModified']->format('Y-m-d H:i:s');

                echo "<div class='list-group-item d-flex justify-content-between align-items-center'>
                        <div><i class='fa fa-file text-secondary me-2'></i> $fileName</div>
                        <small class='text-muted'>$fileSize • Last Modified: $lastModified</small>
                      </div>";
            }
        }

        // No content fallback
        if (empty($result['CommonPrefixes']) && empty($result['Contents'])) {
            echo "<div class='list-group-item text-muted'>No files or folders found.</div>";
        }
        ?>
    </div>
</div>
</body>
</html>
