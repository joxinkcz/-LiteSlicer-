<?php
require_once __DIR__.'/Database.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = new Database();
        session_start();
    }

    public function register($username, $email, $password) {  // Было без '{'
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {   // Было filter_FILTER_VALIDATE_EMAIL
            return false;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);  // Было algo: PASWORD

        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";  // Было незакрыто
        try {
            $this->db->query($sql, [$username, $email, $hashedPassword]);  // Было ($username, ...)
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function login($email, $password) {
        $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
        $user = $this->db->query($sql, [$email]);

        if (empty($user)) {
            return false;
        }

        $user = $user[0];

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            return true;
        }

        return false;
    }

    public function logout() {
        session_unset();
        session_destroy();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email']
        ];
    }
}