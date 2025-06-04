<?php
require_once __DIR__.'/../includes/Config.php';
require_once __DIR__.'/../includes/Auth.php';
require_once __DIR__.'/../includes/FileUploader.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    die(json_encode(['success' => false, 'error' => 'Not authorized']));
}

try {
    // Валидация входных данных
    $modelFile = $_POST['model'] ?? '';
    if (empty($modelFile)) {
        throw new Exception("Не указан файл модели");
    }

    $modelPath = MODEL_UPLOAD_DIR . $modelFile;
    if (!file_exists($modelPath)) {
        throw new Exception("Файл модели не найден");
    }

    // Получение параметров слайсинга
    $settings = [
        'material' => $_POST['material'] ?? 'PLA',
        'nozzle_size' => (float)($_POST['nozzle_size'] ?? 0.4),
        'layer_height' => (float)($_POST['layer_height'] ?? 0.2),
        'infill_density' => (int)($_POST['infill_density'] ?? 20),
        'support' => isset($_POST['generate_support']) ? 'true' : 'false',
        'brim' => isset($_POST['add_brim']) ? 'brim' : 'none'
    ];

    // Генерация имени выходного файла
    $outputFile = 'slice_'.time().'_'.bin2hex(random_bytes(4)).'.gcode';
    $gcodePath = GCODE_UPLOAD_DIR . $outputFile;

    // Формирование команды для CuraEngine
    $command = sprintf(
        '"%s" --load "%s" --export-gcode --output "%s" "%s" '.
        '--layer-height %s '.
        '--fill-density %d '.
        '--nozzle-diameter %s '.
        '%s '.
        '%s',
        PRUSA_SLICER_PATH,
        PRINTER_PROFILE_PATH,
        escapeshellarg($gcodePath),
        escapeshellarg($modelPath),
        $settings['layer_height'],
        $settings['infill_density'],
        $settings['nozzle_size'],
        ($settings['support'] === 'true' ? '--support-material' : ''),
        ($settings['brim'] === 'brim' ? '--brim-width 5' : '')
    );

    // Выполнение команды
    exec($command, $output, $returnCode);

    if ($returnCode !== 0 || !file_exists($gcodePath)) {
        throw new Exception("Ошибка слайсинга: ".implode("\n", $output));
    }

    // Возвращаем результат
    echo json_encode([
        'success' => true,
        'filename' => $outputFile,
        'gcode' => file_get_contents($gcodePath),
        'print_time' => 'N/A',
        'filament_used' => 'N/A'
    ]);

} catch (Exception $e) {
    error_log('Slice error: '.$e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}