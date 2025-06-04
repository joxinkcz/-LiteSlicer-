<?php

define('PRUSA_SLICER_PATH', __DIR__.'/../PrusaSlicer/prusa-slicer-console.exe');
define('PRINTER_PROFILE_PATH', __DIR__.'/../PrusaSlicer/resources/Anycubic_Kobra_3_Combo_0.4.ini');
// Настройки базы данных
define('DB_HOST', 'localhost:3306');
define('DB_USER', 'root');
define('DB_PASS', '1234');
define('DB_NAME', '3d_slicer');

// Настройки путей
define('BASE_URL', 'http://localhost/3d-printer-slicer');
define('MODEL_UPLOAD_DIR', __DIR__.'/../uploads/models/');
define('GCODE_UPLOAD_DIR', __DIR__.'/../uploads/gcodes/');;

// Настройки Anycubic Kobra 3
define('PRINTER_PROFILES', [
    'Anycubic Kobra 3' => [
        'build_volume' => '220x220x250mm',
        'nozzle_sizes' => [0.2, 0.4, 0.6],
        'max_temp' => 260
    ]
]);

// Настройки материалов
define('MATERIALS', [
    'PLA' => [
        'extruder_temp' => [190, 220],
        'bed_temp' => [50, 60],
        'fan_speed' => 100
    ],
    'ABS' => [
        'extruder_temp' => [230, 250],
        'bed_temp' => [100, 110],
        'fan_speed' => 50
    ],
    'PETG' => [
        'extruder_temp' => [220, 240],
        'bed_temp' => [70, 80],
        'fan_speed' => 70
    ]
]);