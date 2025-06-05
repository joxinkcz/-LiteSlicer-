<?php
require_once __DIR__.'/includes/Auth.php';
require_once __DIR__.'/includes/Database.php';
require_once __DIR__.'/includes/Config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user = $auth->getCurrentUser();
$db = new Database();

// Get print history
$prints = $db->query(
    "SELECT * FROM prints WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
    [$user['id']]
);

// Handle file upload
$uploadError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['model'])) {
    try {
        require_once __DIR__.'/includes/FileUploader.php';
        $filename = FileUploader::uploadModel($_FILES['model']);
        header("Location: slicer.php?model=" . urlencode($filename));
        exit;
    } catch (Exception $e) {
        $uploadError = $e->getMessage();
    }
}

// Handle platform size save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_platform_size'])) {
    $platformWidth = (int)$_POST['platform_width'];
    $platformDepth = (int)$_POST['platform_depth'];
    $platformHeight = (int)$_POST['platform_height'];

    // Validate dimensions
    if ($platformWidth >= 100 && $platformWidth <= 500 &&
        $platformDepth >= 100 && $platformDepth <= 500 &&
        $platformHeight >= 100 && $platformHeight <= 500) {

        $_SESSION['platform_width'] = $platformWidth;
        $_SESSION['platform_depth'] = $platformDepth;
        $_SESSION['platform_height'] = $platformHeight;
    }
}

// Get current platform size from session or use defaults
$currentPlatformWidth = $_SESSION['platform_width'] ?? 220;
$currentPlatformDepth = $_SESSION['platform_depth'] ?? 220;
$currentPlatformHeight = $_SESSION['platform_height'] ?? 250;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lite Slicer - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .print-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .print-table th {
            color: var(--primary-blue);
            font-weight: 500;
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid var(--primary-blue);
        }

        .print-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(100, 181, 246, 0.3);
        }

        .print-table tr:hover {
            background: rgba(10, 189, 198, 0.1);
        }

        .table-responsive {
            overflow-x: auto;
        }
        :root {
            --primary-blue: #0abdc6;
            --dark-blue: #1a237e;
            --light-blue: #e3f2fd;
            --accent-blue: #64b5f6;
            --text-dark: #212121;
            --text-light: #ffffff;
            --card-bg: rgba(26, 35, 126, 0.7);
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Roboto', sans-serif;
            background-color: #0c0c1a;
            color: var(--text-light);
            min-height: 100vh;
        }

        #matrix-effect {
            position: fixed;
            top: 0;
            left: 0;
            z-index: -1;
            opacity: 0.1;
            width: 100%;
            height: 100%;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(100, 181, 246, 0.3);
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo img {
            height: 40px;
            margin-right: 10px;
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(90deg, #64b5f6, #0abdc6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .user-menu {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-blue);
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 10px;
        }

        .logout-btn {
            background: transparent;
            border: 1px solid var(--primary-blue);
            color: var(--primary-blue);
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: var(--primary-blue);
            color: var(--text-dark);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
        }

        .sidebar {
            background: var(--card-bg);
            border: 1px solid var(--primary-blue);
            border-radius: 10px;
            padding: 20px;
            backdrop-filter: blur(5px);
        }

        .sidebar-title {
            color: var(--primary-blue);
            font-size: 1.2rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(100, 181, 246, 0.3);
        }

        .printer-info {
            margin-bottom: 30px;
        }

        .info-item {
            margin-bottom: 15px;
        }

        .info-label {
            color: var(--accent-blue);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 1rem;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--primary-blue);
            border-radius: 10px;
            padding: 20px;
            backdrop-filter: blur(5px);
        }

        .card-title {
            color: var(--primary-blue);
            font-size: 1.2rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(100, 181, 246, 0.3);
        }

        .upload-area {
            border: 2px dashed var(--primary-blue);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .upload-area:hover {
            background: rgba(10, 189, 198, 0.1);
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--primary-blue);
            margin-bottom: 15px;
        }

        .upload-text {
            margin-bottom: 10px;
        }

        .file-input {
            display: none;
        }

        .btn {
            background: linear-gradient(90deg, var(--primary-blue), #64b5f6);
            color: var(--text-dark);
            border: none;
            padding: 12px;
            border-radius: 5px;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }

        .btn:hover {
            box-shadow: 0 0 15px rgba(10, 189, 198, 0.5);
        }

        .error-message {
            color: #ff5252;
            margin-bottom: 20px;
            text-align: center;
        }

        .prints-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .print-card {
            background: rgba(10, 189, 198, 0.1);
            border: 1px solid var(--primary-blue);
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s;
        }

        .print-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(10, 189, 198, 0.2);
        }

        .print-name {
            font-weight: 500;
            margin-bottom: 10px;
            color: var(--primary-blue);
        }

        .print-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: var(--accent-blue);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--accent-blue);
        }

        /* Platform settings styles */
        .platform-settings {
            margin-top: 20px;
            padding: 15px;
            background: rgba(10, 189, 198, 0.1);
            border-radius: 5px;
            border: 1px solid var(--primary-blue);
        }

        .platform-settings-title {
            color: var(--primary-blue);
            margin-bottom: 10px;
            font-weight: 500;
        }

        .platform-form {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            align-items: center;
        }

        .platform-form-group {
            display: flex;
            flex-direction: column;
        }

        .platform-form-label {
            color: var(--accent-blue);
            font-size: 0.8rem;
            margin-bottom: 5px;
        }

        .platform-form input {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--primary-blue);
            border-radius: 4px;
            background: rgba(0,0,0,0.3);
            color: var(--text-light);
        }

        .platform-form .btn {
            grid-column: span 3;
            padding: 8px 12px;
            width: 100%;
        }
    </style>
</head>
<body>
<div id="matrix-effect"></div>
<div class="container">
    <header>
        <div class="logo">
            <img src="photo/photo1.jpg" alt="Lite Slicer Logo">
            <div class="logo-text">Lite Slicer</div>
        </div>
        <div class="user-menu">
            <div class="user-avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="dashboard-grid">
        <div class="sidebar">
            <h3 class="sidebar-title">PRINTER STATUS</h3>
            <div class="printer-info">
                <div class="info-item">
                    <div class="info-label">Model</div>
                    <div class="info-value">Anycubic Kobra 3</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value" style="color: #69f0ae;">Online</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Build Volume</div>
                    <div class="info-value"><?= $currentPlatformWidth ?>x<?= $currentPlatformDepth ?>x<?= $currentPlatformHeight ?>mm</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Nozzle</div>
                    <div class="info-value">0.4mm</div>
                </div>
            </div>


        </div>

        <div class="main-content">
            <div class="card">
                <h3 class="card-title">UPLOAD MODEL</h3>
                <?php if ($uploadError): ?>
                    <div class="error-message">Error: <?= htmlspecialchars($uploadError) ?></div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="upload-area" onclick="document.getElementById('model-file').click()">
                        <div class="upload-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                        </div>
                        <div class="upload-text">Click to upload or drag and drop</div>
                        <div style="color: var(--accent-blue); font-size: 0.9rem;">STL or OBJ files (max 50MB)</div>
                        <input type="file" id="model-file" name="model" class="file-input" accept=".stl,.obj" required>
                    </div>
                    <button type="submit" class="btn">UPLOAD AND SLICE</button>
                </form>

                <div class="platform-settings">
                    <div class="platform-settings-title">PLATFORM SETTINGS</div>
                    <form method="post" class="platform-form">
                        <div class="platform-form-group">
                            <label class="platform-form-label">Width (mm)</label>
                            <input type="number" name="platform_width" min="100" max="500" step="1"
                                   value="<?= $currentPlatformWidth ?>" required>
                        </div>
                        <div class="platform-form-group">
                            <label class="platform-form-label">Depth (mm)</label>
                            <input type="number" name="platform_depth" min="100" max="500" step="1"
                                   value="<?= $currentPlatformDepth ?>" required>
                        </div>
                        <div class="platform-form-group">
                            <label class="platform-form-label">Height (mm)</label>
                            <input type="number" name="platform_height" min="100" max="500" step="1"
                                   value="<?= $currentPlatformHeight ?>" required>
                        </div>
                        <button type="submit" name="save_platform_size" class="btn">Apply Settings</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <h3 class="card-title">PRINT HISTORY</h3>
                <?php if (empty($prints)): ?>
                    <div class="empty-state">No print history found</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="print-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                            <tr style="background: rgba(10, 189, 198, 0.2);">
                                <th style="padding: 12px; text-align: left; border-bottom: 1px solid var(--primary-blue);">User</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 1px solid var(--primary-blue);">Model</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 1px solid var(--primary-blue);">Time</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 1px solid var(--primary-blue);">Details</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($prints as $print): ?>
                                <tr style="border-bottom: 1px solid rgba(100, 181, 246, 0.3);">
                                    <td style="padding: 12px; color: var(--accent-blue);"><?= htmlspecialchars($print['username']) ?></td>
                                    <td style="padding: 12px;"><?= htmlspecialchars($print['model_name']) ?></td>
                                    <td style="padding: 12px; color: var(--accent-blue);"><?= date('M d, Y H:i', strtotime($print['created_at'])) ?></td>
                                    <td style="padding: 12px;">
                                        <span style="display: block; font-size: 0.8rem;">Material: <?= htmlspecialchars($print['material']) ?></span>
                                        <span style="display: block; font-size: 0.8rem;">Time: <?= htmlspecialchars($print['print_time']) ?></span>
                                        <span style="display: block; font-size: 0.8rem;">Filament: <?= htmlspecialchars($print['filament_used']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

<script>
    // Matrix effect
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.createElement('canvas');
        const container = document.getElementById('matrix-effect');
        container.appendChild(canvas);
        const ctx = canvas.getContext('2d');
        canvas.width = container.offsetWidth;
        canvas.height = container.offsetHeight;

        const chars = "01アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲン";
        const fontSize = 14;
        const columns = canvas.width / fontSize;
        const drops = [];

        for (let i = 0; i < columns; i++) {
            drops[i] = Math.random() * canvas.height;
        }

        function draw() {
            ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#0abdc6';
            ctx.font = fontSize + 'px monospace';

            for (let i = 0; i < drops.length; i++) {
                const text = chars[Math.floor(Math.random() * chars.length)];
                ctx.fillText(text, i * fontSize, drops[i] * fontSize);

                if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
                    drops[i] = 0;
                }
                drops[i]++;
            }
        }

        setInterval(draw, 33);

        // File input display
        document.getElementById('model-file').addEventListener('change', function(e) {
            if (this.files.length > 0) {
                const fileName = this.files[0].name;
                const uploadText = document.querySelector('.upload-text');
                uploadText.textContent = fileName;
            }
        });
    });
</script>
</body>
</html>