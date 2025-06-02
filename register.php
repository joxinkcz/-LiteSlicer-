<?php
require_once __DIR__.'/includes/Auth.php';

$auth = new Auth();
if ($auth->isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        $error = "Passwords don't match";
    } elseif ($auth->register($username, $email, $password)) {
        header("Location: login.php?registered=1");
        exit;
    } else {
        $error = "Registration failed. Email may already exist.";
    }
}

include __DIR__.'/templates/header.php';
include __DIR__.'/templates/register.php';
include __DIR__.'/templates/footer.php';