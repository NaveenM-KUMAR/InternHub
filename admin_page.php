
<?php
session_start();

// Redirect to login page if user is not logged in
if (!isset($_SESSION['email'])) {
    header('Location: index.php'); // change this to your login page if different
    exit;
}

// DB connection
require_once 'config.php';

// Get user name from session, fallback to "User"
$name  = isset($_SESSION['name'])  ? $_SESSION['name']  : 'User';
$email = isset($_SESSION['email']) ? $_SESSION['email'] : null;

// 1) Get logged-in user's ID from `users` table
$userId = null;
if ($email) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($userId);
    $stmt->fetch();
    $stmt->close();
}

if (!$userId) {
    // Safety: if user not found in DB, logout
    header('Location: logout.php');
    exit;
}

// 2) Load profile data from `user_profiles`
$profile = [
    'display_name'  => '',
    'bio'           => '',
    'github_url'    => '',
    'linkedin_url'  => '',
    'primary_role'  => '',
    'primary_stack' => '',
    'avatar_initial'=> ''
];

$stmt = $conn->prepare("
    SELECT display_name, bio, github_url, linkedin_url, primary_role, primary_stack, avatar_initial
    FROM user_profiles
    WHERE user_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result(
        $profile['display_name'],
        $profile['bio'],
        $profile['github_url'],
        $profile['linkedin_url'],
        $profile['primary_role'],
        $profile['primary_stack'],
        $profile['avatar_initial']
    );
    $stmt->fetch();
}
$stmt->close();

// 3) Defaults for display name + avatar letter
$displayName   = $profile['display_name']  !== '' ? $profile['display_name']  : $name;
$avatarInitial = $profile['avatar_initial'] !== ''
    ? $profile['avatar_initial']
    : strtoupper(substr($displayName, 0, 1));

    // 4) Flag: was profile just saved?
$profileSaved = isset($_GET['profile_saved']) && $_GET['profile_saved'] === '1';

?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Prep App - User Dashboard</title>

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <!-- ZegoCloud SDK will be loaded dynamically when needed -->

    <style>
       
       
       
       
       
       /* BASE APP LAYOUT + SIDEBAR + PAGES */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            transition: background-color 0.3s ease-in-out, color 0.3s ease-in-out;
        }
        body.dark { background-color: #121212; color: #e0e0e0; }

        .container { display: flex; min-height: 100vh; }

        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #0a66c2 0%, #8b5cf6 100%);
            color: white;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease-in-out;
            z-index: 1000;
        }
        .sidebar.collapsed { transform: translateX(-100%); }

        .sidebar .header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        .sidebar .header i { margin-right: 10px; width: 24px; height: 24px; }
        .sidebar h2 { font-size: 1.5rem; font-weight: 600; }

        .sidebar .welcome {
            text-align: center;
            font-size: 0.95rem;
            margin-bottom: 25px;
            opacity: 0.9;
        }

        .sidebar nav ul { list-style: none; }
        .sidebar nav li { margin-bottom: 10px; }

        .sidebar nav a {
            display: flex; align-items: center;
            padding: 12px 15px;
            text-decoration: none;
            color: white;
            border-radius: 8px;
            transition: background-color 0.3s ease-in-out, transform 0.3s ease-in-out;
            min-height: 44px; /* Large touch target */
        }
        .sidebar nav a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        .sidebar nav a.active {
            background-color: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }
        .sidebar nav a i { margin-right: 10px; width: 20px; height: 20px; }

        .logout-btn {
            position: absolute;
            bottom: 80px; /* Positioned above dark mode toggle */
            left: 20px;
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 1rem;
            min-height: 44px;
            padding: 10px;
            border-radius: 8px;
            transition: background-color 0.3s ease-in-out;
            display: flex; align-items: center;
        }
        .logout-btn:hover { background-color: rgba(255, 255, 255, 0.1); }
        .logout-btn i { margin-right: 10px; width: 20px; height: 20px; }

        .dark-mode-toggle {
            position: absolute;
            bottom: 20px; left: 20px;
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 1rem;
            min-height: 44px;
            padding: 10px;
            border-radius: 8px;
            transition: background-color 0.3s ease-in-out;
            display: flex; align-items: center;
        }
        .dark-mode-toggle:hover { background-color: rgba(255, 255, 255, 0.1); }
        .dark-mode-toggle i { margin-right: 10px; width: 20px; height: 20px; }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.05); /* Subtle depth */
        }
        .main-content.shifted { margin-left: 0; }

        .hamburger {
            display: none;
            position: fixed;
            top: 20px; left: 20px;
            background: #0a66c2;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            z-index: 1001;
            min-height: 44px;
            min-width: 44px;
        }
        .hamburger:hover { background: #084a8a; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .hamburger { display: block; }
        }

        .page { display: none; }
        .page.active { display: block; }
        .page h1 { color: #0a66c2; margin-bottom: 10px; }
        body.dark .page h1 { color: #8b5cf6; }

        /* Zego root container */
       /* Zego root container */
#root {
    display: none;             /* ★ NEW: hidden by default */
    width: 100%;
    height: calc(100vh - 220px);
}


        /* INTERNVIEW DASHBOARD PAGE STYLES - SCOPED */
        :root {
            --bg-body: #f3f4f6;
            --bg-card: #ffffff;
            --bg-hero: linear-gradient(135deg, #e0f2fe, #fef9c3);
            --primary: #2563eb;
            --primary-soft: rgba(37, 99, 235, 0.1);
            --accent: #22c55e;
            --text-main: #111827;
            --text-muted: #6b7280;
            --border-subtle: #e5e7eb;
            --shadow-soft: 0 15px 35px rgba(15, 23, 42, 0.08);
            --radius-card: 16px;
            --radius-pill: 999px;
            --nav-height: 64px;
        }

        .iv-dashboard { color: var(--text-main); }
        .iv-dashboard a { text-decoration: none; color: inherit; }

        /* Top Nav inside Dashboard */
        .iv-dashboard header.iv-header {
            position: sticky; top: 0;
            z-index: 40;
            backdrop-filter: blur(14px);
            background: rgba(243, 244, 246, 0.92);
            border-bottom: 1px solid rgba(209, 213, 219, 0.8);
            margin-left: -4px; margin-right: -4px;
        }
        .iv-dashboard .nav {
            max-width: 1200px;
            margin: 0 auto;
            height: var(--nav-height);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 16px;
        }
        .iv-dashboard .nav-left { display: flex; align-items: center; gap: 10px; }
        .iv-dashboard .logo-mark {
            width: 32px; height: 32px;
            border-radius: 12px;
            background: radial-gradient(circle at 20% 10%, #60a5fa, #2563eb);
            display: flex; align-items: center; justify-content: center;
            color: #f9fafb;
            font-size: 16px; font-weight: 700;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.45);
        }
        .iv-dashboard .logo-text { display: flex; flex-direction: column; line-height: 1.1; }
        .iv-dashboard .logo-text span:first-child { font-size: 18px; font-weight: 600; }
        .iv-dashboard .logo-text span:last-child { font-size: 11px; color: var(--text-muted); }

        .iv-dashboard .nav-links {
            display: flex; align-items: center; gap: 18px;
            font-size: 14px;
        }
        .iv-dashboard .nav-link {
            position: relative;
            padding: 4px 0;
            color: var(--text-muted);
            cursor: pointer;
            transition: color 0.15s ease;
        }
        .iv-dashboard .nav-link.active {
            color: var(--primary);
            font-weight: 500;
        }
        .iv-dashboard .nav-link::after {
            content: '';
            position: absolute; left: 0; bottom: -4px;
            width: 0; height: 2px;
            border-radius: 999px; background: var(--primary);
            transition: width 0.18s ease;
        }
        .iv-dashboard .nav-link.active::after { width: 100%; }
        .iv-dashboard .nav-link:hover::after { width: 100%; }
        .iv-dashboard .nav-link:hover { color: #1d4ed8; }

        .iv-dashboard .nav-mobile-toggle {
            display: none;
            border: none; background: transparent;
            width: 32px; height: 32px;
            border-radius: 50%;
            align-items: center; justify-content: center;
            cursor: pointer;
        }
        .iv-dashboard .nav-mobile-toggle span {
            display: block; width: 18px; height: 2px;
            background: #111827; border-radius: 999px;
            position: relative;
        }
        .iv-dashboard .nav-mobile-toggle span::before,
        .iv-dashboard .nav-mobile-toggle span::after {
            content: '';
            position: absolute;
            width: 18px; height: 2px;
            border-radius: 999px; background: #111827;
            left: 0;
        }
        .iv-dashboard .nav-mobile-toggle span::before { top: -5px; }
        .iv-dashboard .nav-mobile-toggle span::after { top: 5px; }

        .iv-dashboard .iv-main {
            max-width: 1200px;
            margin: 20px auto 32px;
            padding: 0 16px 16px;
        }
        .iv-dashboard .page-header {
            margin-bottom: 14px;
            font-size: 13px;
            color: var(--text-muted);
        }
        .iv-dashboard .page-header span { color: var(--primary); font-weight: 500; }

        .iv-dashboard .card {
            background: var(--bg-card);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(229, 231, 235, 0.8);
        }

        /* Hero */
        .iv-dashboard .hero-card {
            padding: 20px 22px;
            background-image: var(--bg-hero);
            display: flex; flex-wrap: wrap;
            align-items: center; justify-content: space-between;
            gap: 18px;
            margin-bottom: 22px;
            position: relative; overflow: hidden;
        }
        .iv-dashboard .hero-content {
            max-width: 640px;
            position: relative; z-index: 1;
        }
        .iv-dashboard .hero-heading {
            font-size: 26px; font-weight: 600;
            display: flex; align-items: center; gap: 6px;
            margin-bottom: 4px;
        }
        .iv-dashboard .hero-goal {
            font-size: 14px; color: #4b5563;
            margin-bottom: 6px;
        }
        .iv-dashboard .hero-tagline {
            font-size: 13px; color: #6b7280;
        }
        .iv-dashboard .hero-meta {
            display: flex; align-items: center; gap: 16px;
            margin-top: 10px;
            font-size: 12px; color: #4b5563;
            flex-wrap: wrap;
        }
        .iv-dashboard .meta-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.08);
            color: #1e3a8a;
            font-weight: 500; font-size: 11px;
        }
        .iv-dashboard .meta-pill-dot {
            width: 8px; height: 8px;
            border-radius: 999px;
            background: #22c55e;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.35);
        }
        .iv-dashboard .hero-side {
            display: flex; flex-direction: column;
            align-items: flex-end; gap: 10px;
            min-width: 180px;
            position: relative; z-index: 1;
        }
        .iv-dashboard .btn-pill {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: var(--radius-pill);
            border: 1px solid rgba(37, 99, 235, 0.5);
            background: rgba(255, 255, 255, 0.85);
            color: #1d4ed8;
            font-size: 13px; font-weight: 500;
            cursor: pointer;
            transition: box-shadow 0.18s ease, transform 0.12s ease, background 0.18s ease;
        }
        .iv-dashboard .btn-pill:hover {
            transform: translateY(-1px);
            background: #eff6ff;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
        }
        .iv-dashboard .btn-pill span.icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 18px; height: 18px;
            border-radius: 999px;
            border: 1px solid rgba(37, 99, 235, 0.5);
            font-size: 12px;
        }
        .iv-dashboard .hero-progress-chip {
            font-size: 11px;
            padding: 4px 9px;
            border-radius: 999px;
            background: rgba(34, 197, 94, 0.14);
            color: #166534;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .iv-dashboard .hero-decoration {
            position: absolute;
            right: -40px; top: -40px;
            width: 200px; height: 200px;
            border-radius: 999px;
            background: radial-gradient(circle at 30% 30%, rgba(59,130,246,0.5), transparent 60%);
            opacity: 0.4;
        }

        /* Stats Grid */
        .iv-dashboard .stats-grid {
            margin-bottom: 22px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }
        .iv-dashboard .stat-card {
            padding: 14px 16px;
            display: flex; flex-direction: column;
            gap: 6px;
            position: relative; overflow: hidden;
        }
        .iv-dashboard .stat-label {
            font-size: 12px; color: var(--text-muted);
            display: flex; align-items: center; gap: 6px;
        }
        .iv-dashboard .stat-icon {
            width: 26px; height: 26px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
        }
        .iv-dashboard .stat-value { font-size: 22px; font-weight: 600; }
        .iv-dashboard .stat-caption { font-size: 12px; color: var(--text-muted); }
        .iv-dashboard .stat-card::after {
            content: '';
            position: absolute;
            inset: auto auto -24px -24px;
            width: 60px; height: 60px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.12), transparent 70%);
        }

        /* Content Grid */
        .iv-dashboard .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(0, 1.6fr);
            gap: 16px;
            margin-bottom: 22px;
        }
        .iv-dashboard .card-header {
            padding: 14px 16px 10px;
            border-bottom: 1px solid var(--border-subtle);
            display: flex; justify-content: space-between; align-items: center;
        }
        .iv-dashboard .card-title { font-size: 15px; font-weight: 600; }
        .iv-dashboard .card-subtitle { font-size: 12px; color: var(--text-muted); }
        .iv-dashboard .card-body { padding: 10px 16px 14px; }

        .iv-dashboard .activity-list {
            list-style: none;
            display: flex; flex-direction: column;
            gap: 10px;
            max-height: 260px;
            overflow-y: auto;
            padding-right: 4px;
        }
        .iv-dashboard .activity-item {
            display: flex; gap: 10px;
            padding: 8px 10px;
            border-radius: 12px;
            transition: background 0.12s ease, transform 0.08s ease;
            cursor: default;
        }
        .iv-dashboard .activity-item:hover {
            background: #f3f4ff;
            transform: translateY(-1px);
        }
        .iv-dashboard .activity-icon {
            flex-shrink: 0;
            width: 30px; height: 30px;
            border-radius: 12px;
            background: #eff6ff;
            display: flex; align-items: center; justify-content: center;
            color: var(--primary);
            font-size: 16px;
        }
        .iv-dashboard .activity-main {
            display: flex; flex-direction: column; gap: 2px;
        }
        .iv-dashboard .activity-title { font-size: 14px; font-weight: 500; }
        .iv-dashboard .activity-meta { font-size: 12px; color: var(--text-muted); }

        .iv-dashboard .upcoming-block {
            display: flex; flex-direction: column; gap: 10px;
            font-size: 13px;
        }
        .iv-dashboard .upcoming-label {
            font-size: 12px; font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .iv-dashboard .upcoming-item {
            padding: 8px 10px;
            border-radius: 12px;
            background: #eff6ff;
            border: 1px solid rgba(37, 99, 235, 0.18);
            display: flex; gap: 10px; align-items: flex-start;
        }
        .iv-dashboard .upcoming-dot {
            width: 8px; height: 8px;
            border-radius: 999px;
            margin-top: 4px;
            background: var(--primary);
        }
        .iv-dashboard .upcoming-text-title {
            font-size: 13px; font-weight: 500;
            margin-bottom: 2px;
        }
        .iv-dashboard .upcoming-text-meta { font-size: 12px; color: var(--text-muted); }

        .iv-dashboard .suggested-item {
            padding: 8px 10px;
            border-radius: 12px;
            background: #ecfdf5;
            border: 1px solid rgba(22, 163, 74, 0.2);
            display: flex; gap: 10px; align-items: flex-start;
        }
        .iv-dashboard .suggested-dot {
            width: 8px; height: 8px;
            margin-top: 4px;
            border-radius: 999px;
            background: var(--accent);
        }
        .iv-dashboard .suggested-text-title {
            font-size: 13px; font-weight: 500;
            margin-bottom: 2px;
        }
        .iv-dashboard .suggested-text-meta { font-size: 12px; color: var(--text-muted); }

        .iv-dashboard .actions-row {
            display: flex; flex-wrap: wrap; gap: 10px;
            margin-top: 12px;
        }
        .iv-dashboard .btn-primary {
            flex: 1; min-width: 140px;
            border-radius: var(--radius-pill);
            border: none;
            padding: 9px 14px;
            font-size: 13px; font-weight: 500;
            cursor: pointer;
            background: var(--primary);
            color: #ffffff;
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.35);
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            transition: transform 0.12s ease, box-shadow 0.12s ease, background 0.15s ease;
        }
        .iv-dashboard .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(37, 99, 235, 0.4);
        }
        .iv-dashboard .btn-primary:active {
            transform: translateY(1px);
            box-shadow: 0 7px 16px rgba(37, 99, 235, 0.35);
        }
        .iv-dashboard .btn-secondary {
            flex: 1; min-width: 160px;
            border-radius: var(--radius-pill);
            border: 1px solid rgba(37, 99, 235, 0.4);
            padding: 9px 14px;
            font-size: 13px; font-weight: 500;
            cursor: pointer;
            background: #ffffff;
            color: var(--primary);
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            transition: transform 0.12s ease, box-shadow 0.12s ease, background 0.12s ease;
        }
        .iv-dashboard .btn-secondary:hover {
            background: #eff6ff;
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.18);
        }
        .iv-dashboard .btn-secondary:active {
            transform: translateY(1px);
            box-shadow: 0 6px 14px rgba(37, 99, 235, 0.18);
        }
        .iv-dashboard .action-caption {
            font-size: 11px; color: var(--text-muted);
            margin-top: 3px;
        }

        /* Progress chart */
        .iv-dashboard .progress-card { padding-bottom: 12px; }
        .iv-dashboard .chart-container { padding: 12px 18px 14px; }
        .iv-dashboard .chart-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 8px;
            font-size: 12px; color: var(--text-muted);
        }
        .iv-dashboard .chart-legend {
            display: flex; gap: 10px; align-items: center;
            font-size: 11px;
        }
        .iv-dashboard .legend-item {
            display: inline-flex; align-items: center; gap: 4px;
        }
        .iv-dashboard .legend-color {
            width: 10px; height: 10px;
            border-radius: 4px;
            background: var(--primary);
        }
        .iv-dashboard .chart {
            position: relative;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            padding: 16px 12px 14px;
            overflow: hidden;
        }
        .iv-dashboard .chart-grid {
            position: absolute;
            inset: 12px 12px 26px 30px;
            pointer-events: none;
        }
        .iv-dashboard .chart-grid-horizontal {
            position: absolute; inset: 0;
            display: flex; flex-direction: column;
            justify-content: space-between;
        }
        .iv-dashboard .chart-grid-line {
            width: 100%; height: 1px;
            border-top: 1px dashed #e5e7eb;
        }
        .iv-dashboard .chart-inner {
            position: relative;
            display: flex; align-items: flex-end;
            justify-content: space-between; gap: 8px;
            padding: 0 8px 22px;
            height: 170px;
        }
        .iv-dashboard .chart-bar-wrapper {
            flex: 1;
            display: flex; flex-direction: column;
            align-items: center; gap: 6px;
        }
        .iv-dashboard .chart-bar {
            width: 100%; max-width: 24px;
            border-radius: 999px;
            background: linear-gradient(180deg, #60a5fa, #2563eb);
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.4);
            height: 0;
            transition: height 0.5s ease-out;
        }
        .iv-dashboard .chart-day-label { font-size: 11px; color: var(--text-muted); }
        .iv-dashboard .chart-y-axis-label {
            position: absolute;
            left: 18px; top: 10px;
            font-size: 10px; color: var(--text-muted);
            transform: rotate(-90deg);
            transform-origin: left top;
        }

        .iv-dashboard .activity-list::-webkit-scrollbar { width: 6px; }
        .iv-dashboard .activity-list::-webkit-scrollbar-track { background: transparent; }
        .iv-dashboard .activity-list::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 999px;
        }

        /* Responsive for dashboard */
        @media (max-width: 960px) {
            .iv-dashboard .content-grid { grid-template-columns: minmax(0, 1fr); }
            .iv-dashboard .hero-card { align-items: flex-start; }
            .iv-dashboard .hero-side { align-items: flex-start; }
        }
        @media (max-width: 800px) {
            .iv-dashboard .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 600px) {
            .iv-dashboard .stats-grid { grid-template-columns: minmax(0, 1fr); }
            .iv-dashboard .hero-heading { font-size: 22px; }
        }
        @media (max-width: 720px) {
            .iv-dashboard .nav-links { display: none; }
            .iv-dashboard .nav-mobile-toggle { display: inline-flex; }
            .iv-dashboard .nav-links.mobile-open {
                position: absolute;
                top: var(--nav-height); right: 0; left: 0;
                background: rgba(243, 244, 246, 0.98);
                padding: 10px 16px 14px;
                border-bottom: 1px solid #e5e7eb;
                display: flex; justify-content: flex-end; gap: 16px;
            }
        }

        /* Mock Interviews Page Specific */
        .mock-interviews-info {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .mock-interviews-info h2 { font-size: 2.5rem; margin-bottom: 20px; }
        .mock-interviews-info p { font-size: 1.2rem; margin-bottom: 30px; opacity: 0.9; }
        #mock-status {
            background: rgba(255,255,255,0.2);
            padding: 15px 25px;
            border-radius: 12px;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }

        /* Other pages basic styling */
        .page-inner {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px 10px;
        }
        .page-inner p { margin-bottom: 8px; font-size: 0.95rem; }
        .page-inner h2 { margin-bottom: 12px; }
/* PROFILE PAGE STYLES */
.profile-wrapper {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px 10px;
}

.profile-card {
    display: grid;
    grid-template-columns: 260px minmax(0, 1fr);
    gap: 24px;
    background: #ffffff;
    border-radius: 18px;
    padding: 24px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
}

.profile-left {
    border-right: 1px solid #e5e7eb;
    padding-right: 20px;
}

.profile-avatar {
    width: 96px;
    height: 96px;
    border-radius: 999px;
    background: linear-gradient(135deg, #0a66c2, #7c3aed);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 42px;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 14px;
}

.profile-name {
    font-size: 1.4rem;
    font-weight: 600;
    margin-bottom: 4px;
}

.profile-email {
    font-size: 0.9rem;
    color: #6b7280;
    margin-bottom: 12px;
}

.profile-tag {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 999px;
    background: #eff6ff;
    font-size: 0.8rem;
    color: #1d4ed8;
    margin-bottom: 16px;
}

.profile-summary-list {
    list-style: none;
    padding: 0;
    margin: 0;
    font-size: 0.9rem;
    color: #4b5563;
}
.profile-summary-list li {
    margin-bottom: 6px;
}

.profile-form {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.profile-form-row {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.profile-form label {
    font-size: 0.9rem;
    font-weight: 500;
}

.profile-form input[type="text"],
.profile-form input[type="url"],
.profile-form textarea {
    border-radius: 10px;
    border: 1px solid #d1d5db;
    padding: 8px 10px;
    font-family: inherit;
    font-size: 0.9rem;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.profile-form textarea {
    min-height: 80px;
    resize: vertical;
}

.profile-form input:focus,
.profile-form textarea:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.45);
}

.profile-save-btn {
    align-self: flex-start;
    padding: 9px 18px;
    border-radius: 999px;
    border: none;
    background: linear-gradient(135deg, #0a66c2, #7c3aed);
    color: #ffffff;
    font-weight: 500;
    font-size: 0.95rem;
    cursor: pointer;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.18);
    transition: transform 0.12s ease, box-shadow 0.12s ease;
}
.profile-save-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.24);
}
.profile-save-btn:active {
    transform: translateY(1px);
    box-shadow: 0 7px 18px rgba(15, 23, 42, 0.18);
}

@media (max-width: 768px) {
    .profile-card {
        grid-template-columns: minmax(0, 1fr);
    }
    .profile-left {
        border-right: none;
        border-bottom: 1px solid #e5e7eb;
        padding-right: 0;
        padding-bottom: 16px;
        margin-bottom: 12px;
    }
}
.profile-alert {
    margin-top: 10px;
    margin-bottom: 16px;
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.profile-alert-success {
    background: #ecfdf3;
    border: 1px solid #16a34a;
    color: #166534;
}

.profile-alert.hide {
    opacity: 0;
    transform: translateY(-4px);
    pointer-events: none;
}





    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar" id="sidebar">
            <div class="header">
                <i data-lucide="briefcase"></i>
                <h2>InternHub.ai</h2>
            </div>
            <div class="welcome">
                Welcome, <strong><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
            <nav>
                <ul>
                    <li><a href="#" data-page="dashboard" class="active"><i data-lucide="home"></i> Dashboard</a></li>
        <li><a href="#" data-page="practice"><i data-lucide="target"></i> Practice</a></li>

        <li><a href="#" data-page="hr-questions"><i data-lucide="message-circle"></i> HR Questions</a></li>

        <li><a href="#" data-page="mock-interviews"><i data-lucide="users"></i> Mock Interviews</a></li>
        <li><a href="#" data-page="resources"><i data-lucide="file-text"></i> Resources</a></li>
        <li><a href="#" data-page="profile"><i data-lucide="user"></i> Profile</a></li>
                </ul>
            </nav>

            <button class="logout-btn" id="logoutBtn">
                <i data-lucide="log-out"></i>
                Logout
            </button>
            <button class="dark-mode-toggle" id="darkModeToggle">
                <i data-lucide="moon"></i>
                Toggle Dark Mode
            </button>
        </aside>

        <button class="hamburger" id="hamburger">☰</button>

        <main class="main-content" id="mainContent">
            <!-- DASHBOARD PAGE - InternView design -->
            <div id="dashboard" class="page active">
                <div class="iv-dashboard">
                    <header class="iv-header">
                        <nav class="nav" aria-label="Primary">
                            <div class="nav-left">
                                <div class="logo-mark">IV</div>
                                <div class="logo-text">
                                    <span>InternHub.ai</span>
                                    <span>Interview Prep Dashboard</span>
                                </div>
                            </div>
                            <button class="nav-mobile-toggle" aria-label="Toggle navigation" id="topNavToggle">
                                <span></span>
                            </button>
                            <div class="nav-links" id="topNavLinks">
                                <a href="#" class="nav-link active" data-page="dashboard">Dashboard</a>
                                <a href="#" class="nav-link" data-page="practice">Practice</a>
                                <a href="#" class="nav-link" data-page="mock-interviews">Mock Interviews</a>
                                <a href="#" class="nav-link" data-page="profile">Profile</a>
                            </div>
                        </nav>
                    </header>

                    <section class="iv-main">
                        <div class="page-header">
                            <span>Overview</span> <span>Home</span>
                        </div>

                        <!-- Section 1: Welcome + Goal Hero -->
                        <section class="hero-card card" aria-label="Welcome and goals">
                            <div class="hero-decoration"></div>
                            <div class="hero-content">
                                <div class="hero-heading">
                                    Hi, <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>!
                                </div>
                                <p class="hero-goal">Your goal: <strong>Crack Software Developer role by July 2026.</strong></p>
                                <p class="hero-tagline">Keep up the consistency. Every practice session moves you closer to your offer letter.</p>
                                <div class="hero-meta">
                                    <div class="meta-pill">
                                        <span class="meta-pill-dot"></span>
                                        Active this week: 3 sessions
                                    </div>
                                    <span style="font-size:12px; color:#4b5563;">
                                        Focus today: <strong>DSA + Behavioral</strong>
                                    </span>
                                </div>
                            </div>
                            <div class="hero-side">
                                <a href="#" class="btn-pill" id="updateGoalBtn">
                                    <span class="icon">✎</span>
                                    <span>Update Goal</span>
                                </a>
                                <div class="hero-progress-chip">
                                    You have a 3-day streak. Nice momentum.
                                </div>
                            </div>
                        </section>

                        <!-- Section 2: Stats Cards -->
                        <section class="stats-grid" aria-label="Key statistics">
                            <article class="stat-card card">
                                <div class="stat-label">
                                    <div class="stat-icon">🎯</div>
                                    <span>Total mock interviews completed</span>
                                </div>
                                <div class="stat-value">12</div>
                                <div class="stat-caption">Last one <strong>Java Backend</strong> 2 days ago</div>
                            </article>
                            <article class="stat-card card">
                                <div class="stat-label">
                                    <div class="stat-icon">❓</div>
                                    <span>Questions practiced this week</span>
                                </div>
                                <div class="stat-value">85</div>
                                <div class="stat-caption">Goal: <strong>100 questions/week</strong></div>
                            </article>
                            <article class="stat-card card">
                                <div class="stat-label">
                                    <div class="stat-icon">⭐</div>
                                    <span>Average mock score</span>
                                </div>
                                <div class="stat-value">7.8/10</div>
                                <div class="stat-caption">Last 5 mocks: improving trend</div>
                            </article>
                            <article class="stat-card card">
                                <div class="stat-label">
                                    <div class="stat-icon">🔥</div>
                                    <span>Streak</span>
                                </div>
                                <div class="stat-value">3 days</div>
                                <div class="stat-caption">You practiced <strong>3 days in a row</strong>. Keep it going.</div>
                            </article>
                        </section>

                        <!-- Section 3 & 4: Recent Activity + Upcoming Suggestions -->
                        <section class="content-grid" aria-label="Activity and suggestions">
                            <!-- Recent Activity -->
                            <article class="card" aria-label="Recent activity">
                                <div class="card-header">
                                    <div>
                                        <div class="card-title">Recent Activity</div>
                                        <div class="card-subtitle">Your latest practice and mock interview sessions</div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <ul class="activity-list">
                                        <li class="activity-item">
                                            <div class="activity-icon">💬</div>
                                            <div class="activity-main">
                                                <div class="activity-title">HR Questions</div>
                                                <div class="activity-meta">15 questions • Completed today • Focus: Tell me about yourself, Strengths & Weaknesses</div>
                                            </div>
                                        </li>
                                        <li class="activity-item">
                                            <div class="activity-icon">👨‍💻</div>
                                            <div class="activity-main">
                                                <div class="activity-title">Mock Interview: Java Developer</div>
                                                <div class="activity-meta">Score: 8.2/10 • Yesterday • Feedback: Improve on time complexity explanation</div>
                                            </div>
                                        </li>
                                        <li class="activity-item">
                                            <div class="activity-icon">📊</div>
                                            <div class="activity-main">
                                                <div class="activity-title">Aptitude: Arrays</div>
                                                <div class="activity-meta">10 questions • 2 days ago • Accuracy: 70%</div>
                                            </div>
                                        </li>
                                        <li class="activity-item">
                                            <div class="activity-icon">🧱</div>
                                            <div class="activity-main">
                                                <div class="activity-title">System Design Basics</div>
                                                <div class="activity-meta">Watched 1 module • 3 days ago • Topic: Scaling login service</div>
                                            </div>
                                        </li>
                                        <li class="activity-item">
                                            <div class="activity-icon">🔗</div>
                                            <div class="activity-main">
                                                <div class="activity-title">DSA: Linked Lists</div>
                                                <div class="activity-meta">12 questions • 4 days ago • Most missed: Cycle detection</div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </article>

                            <!-- Upcoming Suggestions -->
                            <article class="card" aria-label="Upcoming suggested actions">
                                <div class="card-header">
                                    <div>
                                        <div class="card-title">Upcoming Suggestions</div>
                                        <div class="card-subtitle">Plan your next focused session</div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="upcoming-block">
                                        <div>
                                            <div class="upcoming-label">Upcoming</div>
                                            <div class="upcoming-item">
                                                <div class="upcoming-dot"></div>
                                                <div>
                                                    <div class="upcoming-text-title">Next mock interview <strong>System Design</strong></div>
                                                    <div class="upcoming-text-meta">Scheduled <strong>07:00 PM today</strong> • Format: 45 min live-style interview</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="upcoming-label" style="margin-top:4px;">Suggested</div>
                                            <div class="suggested-item">
                                                <div class="suggested-dot"></div>
                                                <div>
                                                    <div class="suggested-text-title">Suggested: 10 DSA questions on <strong>Arrays</strong></div>
                                                    <div class="suggested-text-meta">Based on your recent mocks, arrays edge cases are a weaker area. Recommended difficulty: Easy-Medium.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="actions-row">
                                        <button class="btn-primary" type="button" id="startPracticeBtn">Start Practice</button>
                                        <button class="btn-secondary" type="button" id="startMockBtn">Start Mock Interview</button>
                                    </div>
                                    <div class="action-caption">
                                        Start Practice picks a tailored set of DSA + aptitude questions. Start Mock Interview simulates a timed, real interview with scoring.
                                    </div>
                                </div>
                            </article>
                        </section>

                        <!-- Section 5: Progress Chart -->
                        <section class="card progress-card" aria-label="Weekly progress">
                            <div class="card-header">
                                <div>
                                    <div class="card-title">Your Weekly Progress</div>
                                    <div class="card-subtitle">Questions attempted per day (last 7 days)</div>
                                </div>
                                <div class="card-subtitle">This week vs. target</div>
                            </div>
                            <div class="chart-container">
                                <div class="chart-header">
                                    <span>Last 7 days</span>
                                    <div class="chart-legend">
                                        <div class="legend-item">
                                            <span class="legend-color"></span>
                                            <span>Questions attempted</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="chart">
                                    <div class="chart-y-axis-label">Questions attempted</div>
                                    <div class="chart-grid">
                                        <div class="chart-grid-horizontal">
                                            <div class="chart-grid-line"></div>
                                            <div class="chart-grid-line"></div>
                                            <div class="chart-grid-line"></div>
                                            <div class="chart-grid-line"></div>
                                        </div>
                                    </div>
                                    <div class="chart-inner" aria-hidden="true">
                                        <!-- Bars Mon–Sun -->
                                        <div class="chart-bar-wrapper">
                                            <div class="chart-bar" data-day="Mon"></div>
                                            <div class="chart-day-label">Mon</div>
                                        </div>
                                        <div class="chart-bar-wrapper">
                                            <div class="chart-bar" data-day="Tue"></div>
                                            <div class="chart-day-label">Tue</div>
                                        </div>
                                        <div class="chart-bar-wrapper">
                                            <div class="chart-bar" data-day="Wed"></div>
                                            <div class="chart-day-label">Wed</div>
                                        </div>
                                        <div class="chart-bar-wrapper">
                                            <div class="chart-bar" data-day="Thu"></div>
                                            <div class="chart-day-label">Thu</div>
                                        </div>
                                        <div class="chart-bar-wrapper">
                                            <div class="chart-bar" data-day="Fri"></div>
                                            <div class="chart-day-label">Fri</div>
                                        </div>
                                        <div class="chart-bar-wrapper">
                                            <div class="chart-bar" data-day="Sat"></div>
                                            <div class="chart-day-label">Sat</div>
                                        </div>
                                        <div class="chart-bar-wrapper">
                                            <div class="chart-bar" data-day="Sun"></div>
                                            <div class="chart-day-label">Sun</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                    </section>
                </div>
            </div>

            <!-- PRACTICE PAGE -->
            <!-- HR QUESTIONS PAGE -->
<div id="hr-questions" class="page">
    <div class="page-inner">
        <h1>HR & Behavioral Questions</h1>
        <p>
            Practice the most common HR and behavioral interview questions, from basic to advanced.
            Use these as a base and customize your answers with your own examples, projects, and achievements.
        </p>

        <!-- BASIC SECTION -->
        <section class="hr-section">
            <h2>Basic HR Questions</h2>

            <details class="qa-card">
                <summary>Tell me about yourself.</summary>
                <div class="qa-body">
                    <p><strong>Structure:</strong> Present → Past → Future.</p>
                    <ul>
                        <li>Present: degree, year, branch, key skills.</li>
                        <li>Past: projects, internships, achievements.</li>
                        <li>Future: target role and why this company.</li>
                    </ul>
                </div>
            </details>

            <details class="qa-card">
                <summary>Walk me through your resume.</summary>
                <div class="qa-body">
                    <p>Explain your resume in order: education → projects → internships → achievements → extra activities. Keep it 2–3 minutes.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>Why did you choose Computer Science / this branch?</summary>
                <div class="qa-body">
                    <p>Connect your interest in problem solving, technology, building things, and long-term career goals.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>Why do you want to work in our company?</summary>
                <div class="qa-body">
                    <p>Mention: company’s products/tech stack, culture, learning opportunities, and how your skills align with their work.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>What are your strengths?</summary>
                <div class="qa-body">
                    <p>Pick 2–3 strengths relevant to software roles: e.g., problem solving, consistency, quick learner, teamwork. Give a short real example for each.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>What are your weaknesses?</summary>
                <div class="qa-body">
                    <p>Choose a genuine but non-critical weakness (e.g., public speaking, overthinking, difficulty saying “no”) and show what you are doing to improve it.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>Why should we hire you?</summary>
                <div class="qa-body">
                    <p>Combine your technical skills, attitude, and culture fit. Show how you can contribute to projects and learn fast.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>What do you know about our company?</summary>
                <div class="qa-body">
                    <p>Talk about products, services, tech stack (if known), recent news, and company values. Shows you researched them.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>Are you willing to relocate?</summary>
                <div class="qa-body">
                    <p>Answer honestly. If yes, mention that you are flexible and open to opportunities where you can learn and grow.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>Are you comfortable working in shifts?</summary>
                <div class="qa-body">
                    <p>Check the company/role expectations. If you agree, add that you can manage your schedule and health responsibly.</p>
                </div>
            </details>
        </section>

        <!-- INTERMEDIATE SECTION -->
        <section class="hr-section">
            <h2>Intermediate HR Questions</h2>

            <details class="qa-card">
                <summary>Describe a challenge you faced and how you handled it.</summary>
                <div class="qa-body">
                    <p>Use <strong>STAR</strong>: Situation, Task, Action, Result. Pick a project, exam, or team issue where you solved a real problem.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>Tell me about a time you worked in a team.</summary>
                <div class="qa-body">
                    <p>Describe the goal, your role, how you coordinated with others, and what the team achieved.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>Tell me about a time you disagreed with a teammate or friend.</summary>
                <div class="qa-body">
                    <p>Show maturity: how you listened, discussed calmly, focused on facts, and reached a solution.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>How do you handle pressure or tight deadlines?</summary>
                <div class="qa-body">
                    <p>Talk about planning, prioritizing tasks, breaking work into smaller parts, and staying calm and focused.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>How do you handle failure?</summary>
                <div class="qa-body">
                    <p>Give a real example (exam, contest, project) and highlight what you learned and how you improved afterward.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>Explain a situation where you showed leadership.</summary>
                <div class="qa-body">
                    <p>Maybe leading a mini project, club, event, or coordinating a team assignment. Focus on initiative and responsibility.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>How do you prioritize your tasks when you have multiple deadlines?</summary>
                <div class="qa-body">
                    <p>Mention to-do lists, deadlines, impact, and breaking tasks into smaller milestones.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>What motivates you?</summary>
                <div class="qa-body">
                    <p>Examples: solving real problems, learning new technologies, building useful products, recognition for good work.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>How do you keep yourself updated with technology?</summary>
                <div class="qa-body">
                    <p>Talk about online courses, YouTube channels, blogs, coding platforms, side projects, and communities.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>How do you balance academics, projects, and personal life?</summary>
                <div class="qa-body">
                    <p>Show time management, planning ahead, and setting priorities while still maintaining health and hobbies.</p>
                </div>
            </details>
        </section>

        <!-- ADVANCED / BEHAVIORAL SECTION -->
        <section class="hr-section">
            <h2>Advanced / Behavioral Questions</h2>

            <details class="qa-card">
                <summary>Tell me about a time you showed initiative without being asked.</summary>
                <div class="qa-body">
                    <p>Example: you improved a project, added extra features, helped a teammate, or fixed a bug on your own.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>Give an example of a time you had to quickly learn something new.</summary>
                <div class="qa-body">
                    <p>Mention the situation, how you learned (docs, tutorials, seniors), and what you achieved with that new skill.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>Describe a situation where you had incomplete information but still had to make a decision.</summary>
                <div class="qa-body">
                    <p>Show how you evaluated options, took a reasonable decision, and adjusted later if needed.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>Have you ever handled a conflict in a team or classroom project?</summary>
                <div class="qa-body">
                    <p>Focus on listening, understanding both sides, staying respectful, and moving the team towards the project goal.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>Describe a situation where you received critical feedback. How did you react?</summary>
                <div class="qa-body">
                    <p>Show openness to feedback, willingness to improve, and what changes you made after that feedback.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>What is the toughest decision you have made recently?</summary>
                <div class="qa-body">
                    <p>Could be choosing between opportunities, managing time between two important tasks, or handling a personal/professional clash.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>How would your friends or classmates describe you?</summary>
                <div class="qa-body">
                    <p>Pick 2–3 qualities (reliable, responsible, helpful, focused) and back them with small examples.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>Tell me about a time you set a goal and achieved it.</summary>
                <div class="qa-body">
                    <p>Describe the goal, your plan, the actions you took, and the result. Use a measurable outcome if possible.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>What will you do if you do not get selected today?</summary>
                <div class="qa-body">
                    <p>Show maturity: you will analyze your performance, improve weaknesses, continue learning, and apply again.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>Where do you see yourself in 5 years?</summary>
                <div class="qa-body">
                    <p>Talk about becoming a strong software engineer, owning modules, maybe mentoring juniors, and contributing to important projects.</p>
                </div>
            </details>
        </section>

        <!-- COMPANY / ROLE & SALARY SECTION -->
        <section class="hr-section">
            <h2>Company Fit, Salary & Other Questions</h2>

            <details class="qa-card">
                <summary>What are your salary expectations?</summary>
                <div class="qa-body">
                    <p>As a fresher, it is usually safe to say you are open to the company’s standard package for this role and more focused on learning.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>What if another company offers you a higher package?</summary>
                <div class="qa-body">
                    <p>Emphasize learning, role, and culture over only money. Show that you care about growth and meaningful work.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>Are you planning for higher studies?</summary>
                <div class="qa-body">
                    <p>Answer honestly, but if you are open to working first, mention that you want industry experience before any further studies.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>Do you have any questions for us?</summary>
                <div class="qa-body">
                    <p>Prepare 2–3 questions about team structure, tech stack, growth opportunities, or the expected learning path in the first year.</p>
                </div>
            </details>

            <details class="qa-card">
                <summary>Is there anything else you want to share that is not on your resume?</summary>
                <div class="qa-body">
                    <p>Use this to highlight a personal project, competition, or responsibility that shows your initiative and passion.</p>
                </div>
            </details>
        </section>
    </div>
</div>


            <!-- MOCK INTERVIEWS PAGE -->
            <div id="mock-interviews" class="page">
                <div class="page-inner">
                    <div class="mock-interviews-info">
                        <h2>Live Mock Interviews</h2>
                        <p>Practice real-time interviews with video, audio, and structured feedback.</p>
                        <div id="mock-status">No live interview session started yet.</div>
                        <button id="startLiveMockBtn" style="padding:10px 18px; border-radius:999px; border:none; font-weight:500; cursor:pointer;">
                            Start Live Mock (Connect)
                        </button>
                        <p style="margin-top:15px; font-size:0.9rem;">
                            When you integrate ZegoCloud, the live interview UI will load in the space below.
                        </p>
                    </div>
                    <!-- Container for Zego or any video call UI -->
                    <div id="root"></div>
                </div>
            </div>

            <!-- RESOURCES PAGE -->
            <div id="resources" class="page">
                <div class="page-inner">
                    <h1>Resources</h1>
                    <p>You can list useful links here:</p>
                    <ul>
                        <li>DSA playlists</li>
                        <li>System Design blogs / videos</li>
                        <li>HR & behavioral question guides</li>
                    </ul>
                </div>
            </div>

            <!-- PROFILE PAGE -->
           <!-- PROFILE PAGE -->
<!-- PROFILE PAGE -->
<div id="profile" class="page">
    <div class="profile-wrapper">
        <h1>Your Profile</h1>

        <?php if ($profileSaved): ?>
            <div class="profile-alert profile-alert-success" id="profileSavedAlert">
                Profile updated successfully.
            </div>
        <?php endif; ?>

        <div class="profile-card">
            <!-- LEFT: Avatar and summary -->
            <div class="profile-left">
                <div class="profile-avatar">
                    <span><?php echo htmlspecialchars($avatarInitial, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="profile-name">
                    <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div class="profile-email">
                    <?php echo htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div class="profile-tag">
                    Interview-ready profile
                </div>
                <ul class="profile-summary-list">
                    <li><strong>Role:</strong>
                        <?php echo htmlspecialchars($profile['primary_role'] ?: 'Not set', ENT_QUOTES, 'UTF-8'); ?>
                    </li>
                    <li><strong>Stack:</strong>
                        <?php echo htmlspecialchars($profile['primary_stack'] ?: 'Not set', ENT_QUOTES, 'UTF-8'); ?>
                    </li>
                </ul>
            </div>

            <!-- RIGHT: Editable form -->
            <div class="profile-right">
                <form class="profile-form" method="POST" action="save_profile.php">
                    <div class="profile-form-row">
                        <label for="display_name">Display name</label>
                        <input
                            type="text"
                            id="display_name"
                            name="display_name"
                            placeholder="e.g. Naveen M"
                            value="<?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>

                    <div class="profile-form-row">
                        <label for="bio">Short bio</label>
                        <textarea
                            id="bio"
                            name="bio"
                            placeholder="Final year CSE student preparing for software developer roles."
                        ><?php echo htmlspecialchars($profile['bio'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <div class="profile-form-row">
                        <label for="github_url">GitHub profile URL</label>
                        <input
                            type="url"
                            id="github_url"
                            name="github_url"
                            placeholder="https://github.com/your-username"
                            value="<?php echo htmlspecialchars($profile['github_url'], ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>

                    <div class="profile-form-row">
                        <label for="linkedin_url">LinkedIn profile URL</label>
                        <input
                            type="url"
                            id="linkedin_url"
                            name="linkedin_url"
                            placeholder="https://www.linkedin.com/in/your-profile"
                            value="<?php echo htmlspecialchars($profile['linkedin_url'], ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>

                    <div class="profile-form-row">
                        <label for="primary_role">Target role</label>
                        <input
                            type="text"
                            id="primary_role"
                            name="primary_role"
                            placeholder="e.g. Software Developer, Backend Engineer"
                            value="<?php echo htmlspecialchars($profile['primary_role'], ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>

                    <div class="profile-form-row">
                        <label for="primary_stack">Primary stack / skills</label>
                        <input
                            type="text"
                            id="primary_stack"
                            name="primary_stack"
                            placeholder="e.g. Java, Spring Boot, MySQL, DSA"
                            value="<?php echo htmlspecialchars($profile['primary_stack'], ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>

                    <button type="submit" class="profile-save-btn">
                        Save Profile
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>






        </main>
    </div>
<script>
    // Initialize Lucide icons
    if (window.lucide) {
        window.lucide.createIcons();
    }

    // Page switching logic (sidebar + top nav)
    const pages = document.querySelectorAll('.page');
    const sidebarLinks = document.querySelectorAll('.sidebar nav a[data-page]');
    const topNavLinks = document.querySelectorAll('.iv-dashboard .nav-link[data-page]');

    function setActivePage(pageId) {
        pages.forEach(p => p.classList.toggle('active', p.id === pageId));

        sidebarLinks.forEach(link => {
            link.classList.toggle('active', link.getAttribute('data-page') === pageId);
        });

        topNavLinks.forEach(link => {
            link.classList.toggle('active', link.getAttribute('data-page') === pageId);
        });
    }

    sidebarLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const target = this.getAttribute('data-page');
            setActivePage(target);
        });
    });

    topNavLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const target = this.getAttribute('data-page');
            setActivePage(target);
        });
    });

    // Mobile sidebar toggle
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const hamburger = document.getElementById('hamburger');

    hamburger.addEventListener('click', () => {
        sidebar.classList.toggle('open');
    });

    // Top nav mobile toggle
    const topNavToggle = document.getElementById('topNavToggle');
    const topNavLinksContainer = document.getElementById('topNavLinks');

    if (topNavToggle) {
        topNavToggle.addEventListener('click', () => {
            topNavLinksContainer.classList.toggle('mobile-open');
        });
    }

    // Dark mode toggle with localStorage
    const darkModeToggle = document.getElementById('darkModeToggle');
    const storedTheme = localStorage.getItem('theme');
    if (storedTheme === 'dark') {
        document.body.classList.add('dark');
    }

    darkModeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark');
        const isDark = document.body.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });

    // Logout
    const logoutBtn = document.getElementById('logoutBtn');
    logoutBtn.addEventListener('click', () => {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout.php';
        }
    });

    // Weekly progress chart data
    const weeklyData = {
        Mon: 12,
        Tue: 18,
        Wed: 10,
        Thu: 20,
        Fri: 15,
        Sat: 7,
        Sun: 3
    };
    const maxValue = Math.max(...Object.values(weeklyData)) || 1;
    const bars = document.querySelectorAll('.chart-bar');

    bars.forEach(bar => {
        const day = bar.getAttribute('data-day');
        const value = weeklyData[day] || 0;
        const heightPercent = (value / maxValue) * 100;
        setTimeout(() => {
            bar.style.height = heightPercent + '%';
        }, 100);
        bar.title = day + ': ' + value + ' questions';
    });

    // Hook buttons on dashboard to navigate
    const startPracticeBtn = document.getElementById('startPracticeBtn');
    const startMockBtn = document.getElementById('startMockBtn');

    if (startPracticeBtn) {
        startPracticeBtn.addEventListener('click', () => setActivePage('practice'));
    }
    if (startMockBtn) {
        startMockBtn.addEventListener('click', () => setActivePage('mock-interviews'));
    }

    // ====== Zego: load SDK only when needed ======
    function loadZegoSdk(callback) {
        if (window.ZegoUIKitPrebuilt) {
            callback();
            return;
        }
        const script = document.createElement('script');
        script.src = 'https://unpkg.com/@zegocloud/zego-uikit-prebuilt/zego-uikit-prebuilt.js';
        script.onload = callback;
        document.body.appendChild(script);
    }

    function getUrlParams(url) {
        const urlStr = url.split('?')[1] || '';
        const urlSearchParams = new URLSearchParams(urlStr);
        return Object.fromEntries(urlSearchParams.entries());
    }

   function startZegoRoom() {
    const params = getUrlParams(window.location.href);
    const roomID = params['roomID'] || (Math.floor(Math.random() * 10000) + "");
    const userID = Math.floor(Math.random() * 10000) + "";
    const userName = "userName" + userID;

    const appID = 316913873;
    const serverSecret = "9954f4fc821cf38b6cb5813bff183171";

    const kitToken = ZegoUIKitPrebuilt.generateKitTokenForTest(
        appID,
        serverSecret,
        roomID,
        userID,
        userName
    );

    const container = document.querySelector("#root");
    if (!container) return;

    // Ensure visible when joining (safety)
    container.style.display = "block"; // ★ NEW

    const zp = ZegoUIKitPrebuilt.create(kitToken);
    zp.joinRoom({
        container: container,
        sharedLinks: [{
            name: 'Personal link',
            url: window.location.protocol + '//' + window.location.host + window.location.pathname + '?roomID=' + roomID,
        }],
        scenario: {
            mode: ZegoUIKitPrebuilt.VideoConference,
        },
        turnOnMicrophoneWhenJoining: true,
        turnOnCameraWhenJoining: true,
        showMyCameraToggleButton: true,
        showMyMicrophoneToggleButton: true,
        showAudioVideoSettingsButton: true,
        showScreenSharingButton: true,
        showTextChat: true,
        showUserList: true,
        maxUsers: 2,
        layout: "Auto",
        showLayoutButton: false,

        // ★ NEW: when user leaves the room, hide the mock interface
        onLeaveRoom: () => {
            container.innerHTML = "";           // remove Zego UI
            container.style.display = "none";   // hide container again

            if (mockStatus) {
                mockStatus.textContent = "No live interview session started yet.";
            }
        }
    });
}


    // ====== START LIVE MOCK BUTTON: open Zego inside this page ======
    const startLiveMockBtn = document.getElementById('startLiveMockBtn');
    const mockStatus = document.getElementById('mock-status');

   if (startLiveMockBtn) {
    startLiveMockBtn.addEventListener('click', () => {
        if (mockStatus) {
            mockStatus.textContent = "Connecting to live mock session...";
        }

        // ensure we are on the Mock Interviews page visually
        setActivePage('mock-interviews');

        const container = document.querySelector("#root");
        if (container) {
            container.style.display = "block";   // ★ NEW: show container only now
            container.innerHTML = "";           // ★ NEW: clear any previous content
            container.style.marginTop = "40px"; // keep your margin if you like
        }

        loadZegoSdk(() => {
            if (mockStatus) {
                mockStatus.textContent = "Live mock session is running.";
            }
            startZegoRoom();                    // will join and render UI
        });
    });
}


    // Optional: Update goal button
    const updateGoalBtn = document.getElementById('updateGoalBtn');
    if (updateGoalBtn) {
        updateGoalBtn.addEventListener('click', (e) => {
            e.preventDefault();
            alert('Later you can open a modal here to let the user edit their goal / target date.');
        });
    }

    <?php if ($profileSaved): ?>
    setActivePage('profile');

    const profileAlert = document.getElementById('profileSavedAlert');
    if (profileAlert) {
        setTimeout(() => {
            profileAlert.classList.add('hide');
        }, 3000);
    }
<?php endif; ?>

</script>


</body>
</html>