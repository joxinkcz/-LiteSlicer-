<?php
require_once __DIR__.'/../includes/Config.php';
require_once __DIR__.'/../includes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('HTTP/1.1 403 Forbidden');
    die('Not authorized');
}

$file = $_GET['file'] ?? '';
$filepath = GCODE_UPLOAD_DIR . $file;

if (!file_exists($filepath)) {
    header('HTTP/1.1 404 Not Found');
    die('File not found');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;