<?php
session_start();

// Read session errors before clearing
$errors = [
    'login'    => $_SESSION['login_error']    ?? '',
    'register' => $_SESSION['register_error'] ?? '',
];

// Which form was active previously?
$activeForm = $_SESSION['active_form'] ?? 'login';

// Clear only the temporary flash data
unset($_SESSION['login_error'], $_SESSION['register_error'], $_SESSION['active_form']);

function showError($error) {
    return !empty($error) ? "<p class='error-message'>$error</p>" : '';
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
    <title>Full-stack Login & Register Form with User & Admin Page</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">

        <!-- LOGIN FORM -->
        <div class="form-box <?= isActiveForm('login', $activeForm); ?>" id="login-form">
            <form action="login_register.php" method="post">
                <h2>Login</h2>
                <?php if (!empty($_SESSION['success_message'])): ?>
            <p class="success-message"><?= $_SESSION['success_message']; ?></p>
        <?php unset($_SESSION['success_message']); endif; ?>

                <?= showError($errors['login']); ?>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
                <p>Don’t have an account?
                    <a href="#" onclick="showForm('register-form'); return false;">Register</a>
                </p>
            </form>
        </div>

        <!-- REGISTER FORM -->
        <div class="form-box <?= isActiveForm('register', $activeForm); ?>" id="register-form">
            <form action="login_register.php" method="post">
                <h2>Register</h2>
                <?= showError($errors['register']); ?>
                <input type="text" name="name" placeholder="Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <select name="role" required>
                    <option value="">--Select Role--</option>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit" name="register">Register</button>
                <p>Already have an account?
                    <a href="#" onclick="showForm('login-form'); return false;">Login</a>
                </p>
            </form>
        </div>

    </div>

    <script src="script.js"></script>
</body>
</html>
