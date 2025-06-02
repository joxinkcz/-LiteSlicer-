<?php
require_once __DIR__.'/includes/Auth.php';
require_once __DIR__.'/includes/Database.php';

$auth = new Auth();
if ($auth->isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($auth->login($email, $password)) {
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid email or password";
    }
}

include __DIR__.'/templates/header.php';
include __DIR__.'/templates/login.php';
include __DIR__.'/templates/footer.php';