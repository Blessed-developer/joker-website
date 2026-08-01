<?php
session_start();
require_once 'config.php';

if (isset($_POST['register'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? '');

    if ($name === '' || $email === '' || $password === '' || $role === '') {
        $_SESSION['register_error'] = 'Please fill in all fields.';
        $_SESSION['active_form'] = 'register';
    } else {
        $checkStmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $checkStmt->bind_param('s', $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $_SESSION['register_error'] = 'Email is already registered!';
            $_SESSION['active_form'] = 'register';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $conn->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
            $insertStmt->bind_param('ssss', $name, $email, $hashedPassword, $role);

            if ($insertStmt->execute()) {
                $_SESSION['login_error'] = 'Registration successful. Please log in.';
                $_SESSION['active_form'] = 'login';
            } else {
                $_SESSION['register_error'] = 'Registration failed. Please try again.';
                $_SESSION['active_form'] = 'register';
            }
        }
    }

    header('Location: index.php');
    exit();
}

if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare('SELECT id, name, password, role FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = trim((string) $user['role']);
            if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === '1') {
                header('Location: admin-page.php');
                exit();
            }
            header('Location: user_page.php');
            exit();
        }
    }

    $_SESSION['login_error'] = 'Incorrect email or password';
    $_SESSION['active_form'] = 'login';

    header('Location: index.php');
    exit();
}
?>