<?php
session_start();
require_once 'config.php';

// Helper: simple redirect and stop
function redirect($location) {
    header("Location: $location");
    exit();
}

// REGISTER
if (isset($_POST['register'])) {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    // Basic validation
    if ($name === '' || $email === '' || $password === '' || $role === '') {
        $_SESSION['register_error'] = 'All fields are required.';
        $_SESSION['active_form']    = 'register';
        redirect('index.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = 'Please enter a valid email address.';
        $_SESSION['active_form']    = 'register';
        redirect('index.php');
    }

    // Allow only valid roles
    $allowedRoles = ['user', 'admin'];
    if (!in_array($role, $allowedRoles, true)) {
        $_SESSION['register_error'] = 'Invalid role selected.';
        $_SESSION['active_form']    = 'register';
        redirect('index.php');
    }

    // Check if email already exists (prepared statement)
    $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    if (!$stmt) {
        $_SESSION['register_error'] = 'Server error. Please try again later.';
        $_SESSION['active_form']    = 'register';
        redirect('index.php');
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['register_error'] = 'Email is already registered!';
        $_SESSION['active_form']    = 'register';
        $stmt->close();
        redirect('index.php');
    }
    $stmt->close();

    // Insert user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)"
    );
    if (!$stmt) {
        $_SESSION['register_error'] = 'Server error. Please try again later.';
        $_SESSION['active_form']    = 'register';
        redirect('index.php');
    }

    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

    if ($stmt->execute()) {
        // Option A: show success and stay on login
        $_SESSION['login_error'] = 'Registration successful. Please log in.';
        $_SESSION['active_form'] = 'login';

        // Option B (alternative): auto-login directly here if you prefer.
    } else {
        $_SESSION['register_error'] = 'Failed to register. Please try again.';
        $_SESSION['active_form']    = 'register';
    }

    $stmt->close();
    redirect('index.php');
}

// LOGIN
if (isset($_POST['login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $_SESSION['login_error'] = 'Email and password are required.';
        $_SESSION['active_form'] = 'login';
        redirect('index.php');
    }

    // Prepared statement for login
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    if (!$stmt) {
        $_SESSION['login_error'] = 'Server error. Please try again later.';
        $_SESSION['active_form'] = 'login';
        redirect('index.php');
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // Secure the session data
            $_SESSION['user_id'] = $user['id'];     // assuming you have an id column
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            // Optionally regenerate session ID on login
            session_regenerate_id(true);

            if ($user['role'] === 'admin') {
                $stmt->close();
                redirect('admin_page.php');
            } else {
                $stmt->close();
                redirect('admin_page.php');
            }
        }
    }

    // If we reach here, login failed
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }

    $_SESSION['login_error'] = 'Incorrect email or password.';
    $_SESSION['active_form'] = 'login';
    redirect('index.php');
}

// If script is accessed without POST, just redirect
redirect('index.php');
