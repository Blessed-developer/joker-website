<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$allowedFolders = ['cs120', 'cs130', 'cs150'];
$baseUploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
$metaFile = $baseUploadDir . DIRECTORY_SEPARATOR . 'metadata.json';

if (!is_dir($baseUploadDir)) {
    mkdir($baseUploadDir, 0755, true);
}

foreach ($allowedFolders as $folder) {
    $folderPath = $baseUploadDir . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($folderPath)) {
        mkdir($folderPath, 0755, true);
    }
}

$metadata = [];
if (is_file($metaFile)) {
    $metadataContent = file_get_contents($metaFile);
    $metadata = json_decode($metadataContent, true) ?? [];
}

$currentUserId = (string) ($_SESSION['user_id'] ?? '');

$folderFiles = [];
foreach ($allowedFolders as $folder) {
    $path = $baseUploadDir . DIRECTORY_SEPARATOR . $folder;
    $files = array_diff(scandir($path), ['.', '..']);
    $activeFiles = [];

    foreach ($files as $file) {
        $key = $folder . '/' . $file;
        $meta = $metadata[$key] ?? null;
        if ($meta === null || empty($meta['access'][$currentUserId])) {
            continue;
        }

        $accessInfo = $meta['access'][$currentUserId];
        $startAt = $accessInfo['start_at'] ?? null;
        $expiresAt = $accessInfo['expires_at'] ?? null;
        $now = time();
        // require that at least 1 full day remains (days left > 0)
        if ($startAt !== null && $now < $startAt) {
            continue;
        }
        if ($expiresAt === null) {
            continue;
        }
        $daysLeft = (int) floor(($expiresAt - $now) / 86400);
        if ($daysLeft <= 0) {
            continue;
        }

        $activeFiles[] = [
            'name' => $file,
            'payment_date' => $accessInfo['payment_date'] ?? null,
            'start_at' => $startAt,
            'expiry_date' => $accessInfo['expiry_date'] ?? null,
            'expires_at' => $expiresAt,
            'days_left' => $daysLeft,
        ];
    }

    $folderFiles[$folder] = $activeFiles;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Page</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .page-wrapper {
            max-width: 1000px;
            margin: 0 auto;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .box, .folder-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
            padding: 24px;
            margin-bottom: 20px;
        }
        .folder-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(280px, 1fr));
            gap: 20px;
        }
        @media (max-width: 640px) {
            .folder-grid {
                grid-template-columns: 1fr;
            }
        }
        .folder-card h2 {
            margin-top: 0;
        }
        .folder-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 18px;
            grid-auto-rows: 1fr;
        }
        .folder-card li {
            background: #f9fbff;
            border: 1px solid #e8eef7;
            border-radius: 18px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 16px;
            min-height: 240px;
            aspect-ratio: 1 / 1;
        }
        .file-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .file-meta strong {
            font-size: 15px;
        }
        .file-meta small {
            color: #555;
            font-size: 13px;
        }
        .file-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .countdown {
            background: #1976d2;
            color: #fff;
            padding: 6px 10px;
            border-radius: 16px;
            font-size: 12px;
        }
        .file-link {
            text-decoration: none;
            color: #1976d2;
            font-weight: 600;
        }
        .file-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <div class="top-bar">
            <div>
                <h1>User Page</h1>
                <p>Welcome, <strong><?= htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></p>
            </div>
            <button onclick="window.location.href='logout.php'">Logout</button>
        </div>

        <div class="folder-grid">
            <?php foreach ($folderFiles as $folder => $files): ?>
                <div class="folder-card">
                    <h2><?= htmlspecialchars(strtoupper($folder), ENT_QUOTES, 'UTF-8'); ?></h2>
                    <?php if (count($files) === 0): ?>
                        <p>No files available yet.</p>
                    <?php else: ?>
                        <ul>
                                <?php foreach ($files as $file): ?>
                                    <li>
                                        <div class="file-meta">
                                            <strong><?= htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <?php if (!empty($file['payment_date']) || !empty($file['expiry_date'])): ?>
                                                <small>Access: <?= htmlspecialchars($file['payment_date'] ?? '-', ENT_QUOTES, 'UTF-8'); ?> to <?= htmlspecialchars($file['expiry_date'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="file-actions">
                                            <span class="countdown"><?= htmlspecialchars($file['days_left'] . ' day' . ($file['days_left'] > 1 ? 's' : ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                            <a class="file-link" href="uploads/<?= rawurlencode($folder); ?>/<?= rawurlencode($file['name']); ?>" download>Download</a>
                                        </div>
                                    </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
