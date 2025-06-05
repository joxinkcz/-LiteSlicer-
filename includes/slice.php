<?php
require_once __DIR__.'/../includes/Config.php';
require_once __DIR__.'/../includes/Auth.php';
require_once __DIR__.'/../includes/FileUploader.php';
require_once __DIR__.'/../includes/GcodeGenerator.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    die(json_encode(['success' => false, 'error' => 'Not authorized']));
}

try {
    // Validate input
    $modelFile = $_POST['model'] ?? '';
    if (empty($modelFile)) {
        throw new Exception("Model file not specified");
    }

    $modelPath = MODEL_UPLOAD_DIR . $modelFile;
    if (!file_exists($modelPath)) {
        throw new Exception("Model file not found");
    }

    // Get slicing parameters
    $settings = [
        'material' => $_POST['material'] ?? 'PLA',
        'nozzle_size' => (float)($_POST['nozzle_size'] ?? 0.4),
        'layer_height' => (float)($_POST['layer_height'] ?? 0.2),
        'infill_density' => (int)($_POST['infill_density'] ?? 20),
        'support' => isset($_POST['generate_support']) ? 'true' : 'false',
        'brim' => isset($_POST['add_brim']) ? 'brim' : 'none'
    ];

    // Generate G-code
    $generator = new GcodeGenerator($settings, $modelPath);
    $result = $generator->generate();

    // Parse G-code for print time and filament used
    $printTime = 'N/A';
    $filamentUsed = 'N/A';

    if (preg_match('/; estimated printing time \(normal mode\) = (.+)/', $result['content'], $matches)) {
        $printTime = $matches[1];
    }

    if (preg_match('/; filament used \[mm\] = (.+)/', $result['content'], $matches)) {
        $filamentUsed = round($matches[1] / 1000, 2) . ' m';
    }

    // Return success
    echo json_encode([
        'success' => true,
        'filename' => $result['filename'],
        'gcode' => $result['content'],
        'print_time' => $printTime,
        'filament_used' => $filamentUsed
    ]);

} catch (Exception $e) {
    error_log('Slice error: '.$e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}