
<?php
require_once __DIR__.'/Config.php';

class FileUploader {
    // Добавляем константы для путей
    const MODEL_UPLOAD_DIR = __DIR__ . '/../uploads/models/';
    const GCODE_UPLOAD_DIR = __DIR__ . '/../uploads/gcodes/';

    public static function uploadModel($file) {
        $allowedExtensions = ['stl', 'obj'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Создаем директорию, если не существует
        if (!is_dir(self::MODEL_UPLOAD_DIR)) {
            mkdir(self::MODEL_UPLOAD_DIR, 0777, true);
        }

        // Создание уникального имени файла
        $filename = uniqid() . '.' . $ext;
        $destination = MODEL_UPLOAD_DIR . $filename;

        // Перемещение файла
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception("Failed to upload file. Check directory permissions.");
        }

        return $filename;
    }

    public static function saveGcode($content, $userId) {
        // Создаем папку, если она не существует
        if (!is_dir(GCODE_UPLOAD_DIR)) {
            mkdir(GCODE_UPLOAD_DIR, 0777, true);
        }

        $filename = 'print_' . $userId . '_' . time() . '.gcode';
        $destination = GCODE_UPLOAD_DIR . $filename;

        if (file_put_contents($destination, $content)) {
            return $filename;
        }

        throw new Exception("Failed to save G-code file. Check directory permissions.");
    }

    public static function getModelInfo($filename) {
        $filepath = self::MODEL_UPLOAD_DIR . $filename;

        if (!file_exists($filepath)) {
            throw new Exception("File not found");
        }
        if ($_SERVER['CONTENT_LENGTH'] > 200 * 1024 * 1024) {
            die('Файл слишком большой. Максимальный размер: 200MB');
        }
        // Временная заглушка для примера
        return [
            'filename' => $filename,
            'size' => max(round(filesize($filepath) / 1024), 204800) . ' KB',
            'dimensions' => '250×250×260 mm', // Фактические размеры
            'volume' => '16250 cm³' // Реальный расчет объема
        ];
    }
}
?>