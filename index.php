<?php
error_reporting(E_ALL & ~E_NOTICE);
require_once __DIR__.'/includes/Auth.php';
require_once __DIR__.'/includes/Config.php';
session_start();
$auth = new Auth();
$error = '';
if ($auth->isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        if ($auth->login($email, $password)) {
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid email or password";
        }
    }
    elseif (isset($_POST['register'])) {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if ($password !== $confirm) {
            $error = "Passwords don't match";
        } else {
            if ($auth->register($username, $email, $password)) {
                $error = "Registration successful! Please login.";
            } else {
                $error = "Registration failed. Email may already exist.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lite Slicer - 3D Printer Web Slicer</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #0abdc6;
            --dark-blue: #1a237e;
            --light-blue: #e3f2fd;
            --accent-blue: #64b5f6;
            --text-dark: #000000;
            --text-light: #ffffff;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Roboto', sans-serif;
            background-color: #0c0c1a;
            color: var(--text-light);
            height: 100vh;
            overflow: hidden;
            position: relative;
        }

        #matrix-effect {
            position: fixed;
            top: 0;
            left: 0;
            z-index: -1;
            opacity: 0.1;
            width: 100%;
            height: 100%;
        }

        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            padding: 20px;
            box-sizing: border-box;
        }

        .logo-container {
            margin-bottom: 40px;
            text-align: center;
        }

        .logo-img {
            height: 80px;
            margin-bottom: 15px;
        }

        .logo-text {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(90deg, #64b5f6, #0abdc6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .logo-subtext {
            font-size: 1rem;
            color: var(--accent-blue);
            font-weight: 300;
        }

        .auth-box {
            background: rgba(26, 35, 126, 0.7);
            border: 1px solid var(--primary-blue);
            border-radius: 10px;
            padding: 30px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 0 20px rgba(10, 189, 198, 0.3);
            backdrop-filter: blur(5px);
        }

        .auth-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--primary-blue);
        }

        .auth-tab {
            padding: 10px 20px;
            cursor: pointer;
            color: var(--text-light);
            font-weight: 500;
            opacity: 0.7;
            transition: all 0.3s;
        }

        .auth-tab.active {
            opacity: 1;
            border-bottom: 2px solid var(--primary-blue);
            color: var(--primary-blue);
        }

        .auth-form {
            display: none;
        }

        .auth-form.active {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--accent-blue);
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(100, 181, 246, 0.3);
            border-radius: 5px;
            background: rgba(10, 189, 198, 0.1);
            color: var(--text-light);
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 10px rgba(10, 189, 198, 0.3);
        }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background: linear-gradient(90deg, var(--primary-blue), #64b5f6);
            color: var(--text-dark);
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn:hover {
            box-shadow: 0 0 15px rgba(10, 189, 198, 0.5);
        }

        .error-message {
            color: #ff5252;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.9rem;
        }

        .success-message {
            color: #69f0ae;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<div id="matrix-effect"></div>
<div class="container">
    <div class="logo-container">
        <img src="photo/photo2.png" alt="Lite Slicer Logo" class="logo-img">
        <div class="logo-text">Lite Slicer</div>
        <div class="logo-subtext">Professional 3D Printing Web Slicer</div>
    </div>

    <div class="auth-box">
        <div class="auth-tabs">
            <div class="auth-tab active" onclick="switchTab('login')">Login</div>
            <div class="auth-tab" onclick="switchTab('register')">Register</div>
        </div>

        <?php if ($error): ?>
            <div class="<?= strpos($error, 'success') !== false ? 'success-message' : 'error-message' ?>">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="auth-form active" id="login-form">
            <div class="form-group">
                <label for="loginEmail">Email</label>
                <input type="email" id="loginEmail" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="loginPassword">Password</label>
                <input type="password" id="loginPassword" name="password" class="form-control" required>
            </div>
            <button type="submit" name="login" class="btn">Login</button>
        </form>

        <form method="POST" action="" class="auth-form" id="register-form">
            <div class="form-group">
                <label for="registerUsername">Username</label>
                <input type="text" id="registerUsername" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="registerEmail">Email</label>
                <input type="email" id="registerEmail" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="registerPassword">Password</label>
                <input type="password" id="registerPassword" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="registerConfirm">Confirm Password</label>
                <input type="password" id="registerConfirm" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" name="register" class="btn">Register</button>
        </form>
    </div>
</div>

<script>
    // Matrix effect
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.createElement('canvas');
        const container = document.getElementById('matrix-effect');
        container.appendChild(canvas);
        const ctx = canvas.getContext('2d');
        canvas.width = container.offsetWidth;
        canvas.height = container.offsetHeight;

        const chars = "01アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲン";
        const fontSize = 14;
        const columns = canvas.width / fontSize;
        const drops = [];

        for (let i = 0; i < columns; i++) {
            drops[i] = Math.random() * canvas.height;
        }

        function draw() {
            ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#0abdc6';
            ctx.font = fontSize + 'px monospace';

            for (let i = 0; i < drops.length; i++) {
                const text = chars[Math.floor(Math.random() * chars.length)];
                ctx.fillText(text, i * fontSize, drops[i] * fontSize);

                if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
                    drops[i] = 0;
                }
                drops[i]++;
            }
        }

        setInterval(draw, 33);
    });

    function switchTab(tab) {
        document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));

        document.querySelector(`.auth-tab[onclick="switchTab('${tab}')"]`).classList.add('active');
        document.getElementById(`${tab}-form`).classList.add('active');
    }
</script>
</body>
</html>