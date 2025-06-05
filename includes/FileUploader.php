<?php
require_once __DIR__.'/Config.php';

class FileUploader {
    public static function uploadModel($file) {
        $allowedExtensions = ['stl', 'obj'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExtensions)) {
            throw new Exception("Unsupported file format. Only STL and OBJ files are allowed.");
        }

        if (!is_dir(MODEL_UPLOAD_DIR)) {
            mkdir(MODEL_UPLOAD_DIR, 0777, true);
        }

        $filename = uniqid() . '.' . $ext;
        $destination = MODEL_UPLOAD_DIR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception("Failed to upload file. Check directory permissions.");
        }

        return $filename;
    }

    public static function saveGcode($content, $userId) {
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
        $filepath = MODEL_UPLOAD_DIR . $filename;

        if (!file_exists($filepath)) {
            throw new Exception("File not found");
        }

        // In a real application, you would parse the STL file to get actual dimensions
        return [
            'filename' => $filename,
            'size' => filesize($filepath),
            'dimensions' => 'N/A',
            'volume' => 'N/A'
        ];
    }
}