<?php
// Убедимся, что пути правильные относительно расположения dashboard.php
require_once __DIR__.'/../includes/Auth.php';
require_once __DIR__.'/../includes/Database.php';
require_once __DIR__.'/../includes/Config.php';
require_once __DIR__.'/../includes/FileUploader.php';

// Инициализация
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user = $auth->getCurrentUser();
$db = new Database();

// Обработка загрузки файла
$uploadError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['model'])) {
    try {
        $filename = FileUploader::uploadModel($_FILES['model']);
        header("Location: slicer.php?model=" . urlencode($filename));
        exit;
    } catch (Exception $e) {
        $uploadError = $e->getMessage();
    }
}

// Получаем историю печати
$prints = $db->query(
    "SELECT ph.*, u.username 
     FROM print_history ph
     JOIN users u ON ph.user_id = u.id
     WHERE ph.user_id = ? 
     ORDER BY ph.created_at DESC 
     LIMIT 5",
    [$user['id']]
);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LiteSlicer - Dashboard</title>
    <style>
        :root {
            --main-bg: #1a1a2e;
            --card-bg: #16213e;
            --accent-blue: #0f3460;
            --accent-red: #e94560;
            --text-light: #ffffff;
            --text-dim: #b8b8b8;
        }

        body {
            background-color: var(--main-bg);
            color: var(--text-light);
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .card-header {
            border-bottom: 2px solid var(--accent-red);
            padding-bottom: 10px;
            margin-bottom: 15px;
            color: var(--text-light);
        }

        .btn {
            background: var(--accent-red);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn:hover {
            background: #d43d57;
        }

        .file-upload {
            border: 2px dashed var(--accent-blue);
            padding: 30px;
            text-align: center;
            margin: 20px 0;
            border-radius: 8px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>LiteSlicer</h1>

    <div class="card">
        <div class="card-header">
            <h2>Загрузка модели</h2>
        </div>

        <?php if ($uploadError): ?>
            <div style="color: var(--accent-red); margin-bottom: 15px;">
                Ошибка: <?= htmlspecialchars($uploadError) ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="file-upload">
                <input type="file" name="model" accept=".stl,.obj" required>
                <p>Поддерживаемые форматы: STL, OBJ (макс. 50MB)</p>
            </div>
            <button type="submit" class="btn">ПОДГРУЗИТЬ И НАРЕЗАТЬ</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>История печати</h2>
        </div>

        <?php if (empty($prints)): ?>
            <p>Нет данных о предыдущих печатях</p>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                <?php foreach ($prints as $print): ?>
                    <div style="background: rgba(15, 52, 96, 0.3); padding: 10px; border-radius: 6px;">
                        <h3><?= htmlspecialchars($print['model_name']) ?></h3>
                        <p>Материал: <?= htmlspecialchars($print['material']) ?></p>
                        <p>Дата: <?= date('d.m.Y', strtotime($print['created_at'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>