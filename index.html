<?php
require_once 'config.php';
session_start();

$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? ''
];
$activeForm = $_SESSION['active_form'] ?? 'login';

unset($_SESSION['login_error'], $_SESSION['register_error'], $_SESSION['active_form']);

function showError($error) {
    return !empty($error) ? '<p class="error-message">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</p>' : '';
}

function isActiveForm($formName, $activeForm) {
    return $formName === $activeForm ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uni Insights by Eazy</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>

<body>
    <div class="container">
        <div class="form-box <?= isActiveForm('login', $activeForm) ?>" id="login-form">
            <form action="login_register.php" method="post">
                <h1>Login</h1>
                <?= showError($errors['login']); ?>
                <div class="input-box">
                    <input type="email" id="email" placeholder="Email" name="email" required>
                    <i class='bx bx-user'></i>
                </div>
                <div class="input-box">
                    <input type="password" id="password" placeholder="Password" name="password" required>
                    <i class='bx bx-lock-alt'></i>
                </div>
                <div class="remember-forgot">
                    <label><input type="checkbox" name="remember"> Remember me</label>
                    <a href="#">Forgot Password?</a>
                </div>
                <button class="btn" type="submit" name="login">Login</button>
                <p class="register-link">Don't have an account? <a href="#"
                        onclick="showForm('register-form')">Register</a></p>
            </form>
        </div>

        <div class="form-box <?= isActiveForm('register', $activeForm) ?>" id="register-form">
            <form action="login_register.php" method="post">
                <h1>Register</h1>
                <?= showError($errors['register']); ?>
                <div class="input-box">
                    <input type="text" id="name" placeholder="Username" name="name" required>
                    <i class='bx bx-user'></i>
                </div>
                <div class="input-box">
                    <input type="email" id="email" placeholder="Email" name="email" required>
                    <i class='bx bx-envelope'></i>
                </div>
                <div class="input-box">
                    <input type="password" id="new-password" placeholder="Password" name="password" required>
                    <i class='bx bx-lock-alt'></i>
                </div>
                <select name="role" required>
                    <option value="">--Select Role--</option>
                    <option value="admin">Admin</option>
                    <option value="2">User</option>
                </select>
                <button class="btn" type="submit" name="register">Register</button>
                <p class="register-link">Already have an account? <a href="#" onclick="showForm('login-form')">Login</a>
                </p>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>

</html>