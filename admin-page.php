<?php
session_start();
require_once 'config.php';
$role = trim((string) ($_SESSION['role'] ?? ''));
if (!isset($_SESSION['user_id']) || ($role !== 'admin' && $role !== '1')) {
    header('Location: index.php');
    exit();
}

$allowedFolders = ['cs120', 'cs130', 'cs150'];
$baseUploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
$metaFile = $baseUploadDir . DIRECTORY_SEPARATOR . 'metadata.json';
$message = '';

if (!is_dir($baseUploadDir)) {
    mkdir($baseUploadDir, 0755, true);
}

foreach ($allowedFolders as $folder) {
    $folderPath = $baseUploadDir . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($folderPath)) {
        mkdir($folderPath, 0755, true);
    }
}

$users = [];
$dbMessage = '';

if (!$dbConnected || !($conn instanceof mysqli)) {
    $dbMessage = 'Database connection is unavailable. Please start MySQL and refresh the page.';
} else {
    $userStmt = $conn->prepare('SELECT id, name, email, role FROM users');
    if ($userStmt) {
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        while ($userRow = $userResult->fetch_assoc()) {
            if ($userRow['role'] !== 'admin' && $userRow['role'] !== '1') {
                $users[] = $userRow;
            }
        }
        $userStmt->close();
    }
}

$metadata = [];
if (is_file($metaFile)) {
    $metadataContent = file_get_contents($metaFile);
    $metadata = json_decode($metadataContent, true) ?? [];
}

if (isset($_POST['upload']) && isset($_POST['folder']) && isset($_POST['user_id'])) {
    $folder = trim($_POST['folder']);
    $userId = trim($_POST['user_id']);
    $expireDate = trim($_POST['expire_date'] ?? '');

    $year = date('Y');
    $minDate = $year . '-01-01';
    $maxDate = $year . '-12-31';
    $expireTs = strtotime($expireDate . ' 23:59:59');
    $startTs = time();
    $paymentDate = date('Y-m-d');

    if (!in_array($folder, $allowedFolders, true)) {
        $message = 'Invalid folder selected.';
    } elseif (empty($userId)) {
        $message = 'Please select a user.';
    } elseif (empty($_FILES['file']['name'])) {
        $message = 'Please choose a file to upload.';
    } elseif (!$expireDate || $expireTs === false) {
        $message = 'Please select a valid expiry date.';
    } elseif ($expireDate < $minDate || $expireDate > $maxDate) {
        $message = 'Expiry date must be within the current year.';
    } elseif ($startTs >= $expireTs) {
        $message = 'Expiry date must be in the future.';
    } else {
        $uploadFile = $_FILES['file'];

        if ($uploadFile['error'] !== UPLOAD_ERR_OK) {
            $message = 'Upload failed. Error code: ' . $uploadFile['error'];
        } else {
            $fileName = basename($uploadFile['name']);
            $targetDir = $baseUploadDir . DIRECTORY_SEPARATOR . $folder;
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

            if (move_uploaded_file($uploadFile['tmp_name'], $targetPath)) {
                $key = $folder . '/' . $fileName;
                if (!isset($metadata[$key])) {
                    $metadata[$key] = [
                        'uploaded_at' => time(),
                        'uploaded_by' => $_SESSION['user_name'] ?? '',
                        'access' => [],
                    ];
                }
                $metadata[$key]['access'][(string) $userId] = [
                    'assigned_at' => time(),
                    'payment_date' => $paymentDate,
                    'start_at' => $startTs,
                    'expires_at' => $expireTs,
                    'expiry_date' => $expireDate,
                    'duration_days' => (int) ceil(($expireTs - $startTs) / 86400),
                ];
                file_put_contents($metaFile, json_encode($metadata, JSON_PRETTY_PRINT));
                $message = 'File uploaded and granted to the selected user from ' . htmlspecialchars($paymentDate, ENT_QUOTES, 'UTF-8') . ' to ' . htmlspecialchars($expireDate, ENT_QUOTES, 'UTF-8') . '.';
            } else {
                $message = 'Unable to move uploaded file.';
            }
        }
    }
}

$folderFiles = [];
foreach ($allowedFolders as $folder) {
    $path = $baseUploadDir . DIRECTORY_SEPARATOR . $folder;
    $files = array_diff(scandir($path), ['.', '..']);
    $folderFiles[$folder] = array_values($files);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Upload Page</title>
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
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
        }
        .folder-card h2 {
            margin-top: 0;
        }
        .folder-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .folder-card li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .folder-card li:last-child {
            border-bottom: none;
        }
        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #e6ffed;
            border: 1px solid #a3f5b1;
            color: #1f6f3d;
        }
        .upload-form {
            display: grid;
            gap: 12px;
        }
        .upload-form select,
        .upload-form input[type="file"],
        .upload-form button {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        .upload-form button {
            cursor: pointer;
            background: #1976d2;
            color: #fff;
            border: none;
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <div class="top-bar">
            <div>
                <h1>Admin Upload Page</h1>
                <p>Welcome, <strong><?= htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></p>
            </div>
            <button onclick="window.location.href='logout.php'">Logout</button>
        </div>

        <?php if ($message !== ''): ?>
            <div class="message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($dbMessage !== ''): ?>
            <div class="message"><?= htmlspecialchars($dbMessage, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="box">
            <h2>Upload a file for users</h2>
            <form class="upload-form" action="admin-page.php" method="post" enctype="multipart/form-data">
                <label>
                    Select folder
                    <select name="folder" required>
                        <option value="">Choose a folder</option>
                        <?php foreach ($allowedFolders as $folder): ?>
                            <option value="<?= htmlspecialchars($folder, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(strtoupper($folder), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Choose file
                    <input type="file" name="file" required>
                </label>
                <label>
                    Grant access to user
                    <select name="user_id" required>
                        <option value="">Select user</option>
                        <?php foreach ($users as $userItem): ?>
                            <option value="<?= htmlspecialchars($userItem['id'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($userItem['name'] . ' (' . $userItem['email'] . ')', ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Expiry date
                    <input type="date" name="expire_date" required min="<?= date('Y') ?>-01-01" max="<?= date('Y') ?>-12-31">
                </label>
                <button type="submit" name="upload">Upload File</button>
            </form>
        </div>

        <div class="folder-grid">
            <?php foreach ($folderFiles as $folder => $files): ?>
                <div class="folder-card">
                    <h2><?= htmlspecialchars(strtoupper($folder), ENT_QUOTES, 'UTF-8'); ?></h2>
                    <?php if (count($files) === 0): ?>
                        <p>No files uploaded yet.</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($files as $file): ?>
                                <li><?= htmlspecialchars($file, ENT_QUOTES, 'UTF-8'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
