<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'register':
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$name || !$email || !$password)
            jsonResponse(false, 'All fields are required.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            jsonResponse(false, 'Invalid email address.');
        if (strlen($password) < 6)
            jsonResponse(false, 'Password must be at least 6 characters.');

        $db = getDB();
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch())
            jsonResponse(false, 'An account with this email already exists.');

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, $hash]);

        jsonResponse(true, 'Account created successfully. Please log in.');

    case 'login':
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password)
            jsonResponse(false, 'Email and password are required.');

        $db = getDB();
        $stmt = $db->prepare('SELECT id, name, email, password FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password']))
            jsonResponse(false, 'Invalid email or password.');

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email']= $user['email'];

        jsonResponse(true, 'Login successful.', ['redirect' => BASE_URL . '/dashboard.php']);

    case 'update_profile':
        $userId = requireAuth();
        $name   = trim($_POST['name'] ?? '');
        $email  = trim($_POST['email'] ?? '');

        if (!$name || !$email)
            jsonResponse(false, 'Name and email are required.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            jsonResponse(false, 'Invalid email address.');

        $db = getDB();
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch())
            jsonResponse(false, 'Email already used by another account.');

        $stmt = $db->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
        $stmt->execute([$name, $email, $userId]);

        $_SESSION['user_name']  = $name;
        $_SESSION['user_email'] = $email;

        jsonResponse(true, 'Profile updated successfully.');

    case 'change_password':
        $userId      = requireAuth();
        $current     = $_POST['current_password'] ?? '';
        $newPass     = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (!$current || !$newPass || !$confirmPass)
            jsonResponse(false, 'All password fields are required.');
        if (strlen($newPass) < 6)
            jsonResponse(false, 'New password must be at least 6 characters.');
        if ($newPass !== $confirmPass)
            jsonResponse(false, 'New passwords do not match.');

        $db = getDB();
        $stmt = $db->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!password_verify($current, $user['password']))
            jsonResponse(false, 'Current password is incorrect.');

        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $stmt = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$hash, $userId]);

        jsonResponse(true, 'Password changed successfully.');

    default:
        jsonResponse(false, 'Invalid action.');
}
