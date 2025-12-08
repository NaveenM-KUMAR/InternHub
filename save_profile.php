<?php
session_start();
require_once 'config.php'; // your existing DB connection

// Must be logged in
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit;
}

$email = $_SESSION['email'];

// 1) Get the logged-in user's ID from `users` table (we only READ, not modify)
$stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    die("User not found for this session.");
}

$userRow      = $result->fetch_assoc();
$user_id      = (int)$userRow['id'];
$sessionName  = $userRow['name'] ?? ($_SESSION['name'] ?? '');

// 2) Read form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $display_name  = $_POST['display_name']  ?? $sessionName;
    $bio           = $_POST['bio']           ?? null;
    $github_url    = $_POST['github_url']    ?? null;
    $linkedin_url  = $_POST['linkedin_url']  ?? null;
    $primary_role  = $_POST['primary_role']  ?? null;
    $primary_stack = $_POST['primary_stack'] ?? null;

    // 3) Avatar initial – first letter of display_name or session name
    $nameSource      = $display_name ?: $sessionName;
    $avatar_initial  = $nameSource ? strtoupper(substr(trim($nameSource), 0, 1)) : null;

    // 4) Insert or update into user_profiles (your table columns)
    $sql = "
        INSERT INTO user_profiles
            (user_id, display_name, bio, github_url, linkedin_url, primary_role, primary_stack, avatar_initial)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            display_name   = VALUES(display_name),
            bio            = VALUES(bio),
            github_url     = VALUES(github_url),
            linkedin_url   = VALUES(linkedin_url),
            primary_role   = VALUES(primary_role),
            primary_stack  = VALUES(primary_stack),
            avatar_initial = VALUES(avatar_initial)
    ";

    $stmt2 = $conn->prepare($sql);
    $stmt2->bind_param(
        "isssssss",      // i = int, s = string
        $user_id,
        $display_name,
        $bio,
        $github_url,
        $linkedin_url,
        $primary_role,
        $primary_stack,
        $avatar_initial
    );

    if (!$stmt2->execute()) {
        die("Error saving profile: " . $stmt2->error);
    }

    // Redirect back to profile section in your dashboard
   // Redirect back to profile section with "profile_saved" flag
header("Location: admin_page.php?page=profile&profile_saved=1");
exit;

}
