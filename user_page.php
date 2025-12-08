<?php 
session_start();

// Redirect to login page if user is not logged in
if (!isset($_SESSION['email'])) {
    header("Location: index.php"); // change this to your login page if different
    exit();
}

// Get user name from session (fallback to 'User')
$name = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Prep App - User Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            transition: background-color 0.3s ease-in-out, color 0.3s ease-in-out;
        }
        body.dark {
            background-color: #121212;
            color: #e0e0e0;
        }
        .container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #0a66c2 0%, #8b5cf6 100%);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease-in-out;
            z-index: 1000;
        }
        .sidebar.collapsed {
            transform: translateX(-100%);
        }
        .sidebar .header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        .sidebar .header i {
            margin-right: 10px;
            width: 24px;
            height: 24px;
        }
        .sidebar h2 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        .sidebar .welcome {
            text-align: center;
            font-size: 0.95rem;
            margin-bottom: 25px;
            opacity: 0.9;
        }
        .sidebar nav ul {
            list-style: none;
        }
        .sidebar nav li {
            margin-bottom: 10px;
        }
        .sidebar nav a {
            display: flex;
            align-items: center;
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
        .sidebar nav a i {
            margin-right: 10px;
            width: 20px;
            height: 20px;
        }
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
            display: flex;
            align-items: center;
        }
        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .logout-btn i {
            margin-right: 10px;
            width: 20px;
            height: 20px;
        }
        .dark-mode-toggle {
            position: absolute;
            bottom: 20px;
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
            display: flex;
            align-items: center;
        }
        .dark-mode-toggle:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .dark-mode-toggle i {
            margin-right: 10px;
            width: 20px;
            height: 20px;
        }
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.05); /* Subtle depth */
        }
        .main-content.shifted {
            margin-left: 0;
        }
        .hamburger {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
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
        .hamburger:hover {
            background: #084a8a;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .hamburger {
                display: block;
            }
        }
        .page {
            display: none;
        }
        .page.active {
            display: block;
        }
        .page h1 {
            color: #0a66c2;
            margin-bottom: 10px;
        }
        body.dark .page h1 {
            color: #8b5cf6;
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar" id="sidebar">
            <div class="header">
                <i data-lucide="briefcase"></i>
                <h2>Interview Prep</h2>
            </div>
            <div class="welcome">
                Welcome, <strong><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
            <nav>
                <ul>
                    <li><a href="#" data-page="dashboard" class="active"><i data-lucide="home"></i> Dashboard</a></li>
                    <li><a href="#" data-page="practice"><i data-lucide="target"></i> Practice</a></li>
                    <li><a href="#" data-page="mock-interviews"><i data-lucide="users"></i> Mock Interviews</a></li>
                    <li><a href="#" data-page="resources"><i data-lucide="file-text"></i> Resources</a></li>
                    <li><a href="#" data-page="profile"><i data-lucide="user"></i> Profile</a></li>
                </ul>
            </nav>
            <!-- Real logout (goes to logout.php) -->
            <button class="logout-btn" id="logoutBtn">
                <i data-lucide="log-out"></i> Logout
            </button>
            <button class="dark-mode-toggle" id="darkModeToggle">
                <i data-lucide="moon"></i> Toggle Dark Mode
            </button>
        </aside>

        <button class="hamburger" id="hamburger">☰</button>

        <main class="main-content" id="mainContent">
            <div id="dashboard" class="page active">
                <h1>Welcome, <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></h1>
                <p>Track your interview preparation progress with personalized insights and AI-driven recommendations. View your recent sessions and upcoming goals.</p>
            </div>
            <div id="practice" class="page">
                <h1>Practice</h1>
                <p>Hone your skills with targeted questions. Get instant AI feedback on your responses to improve confidence and technique.</p>
            </div>
            <div id="mock-interviews" class="page">
                <h1>Mock Interviews</h1>
                <p>Simulate real-world interviews with our advanced AI interviewer. Practice under pressure and receive detailed performance analysis.</p>
            </div>
            <div id="resources" class="page">
                <h1>Resources</h1>
                <p>Access expert tips, industry guides, and study materials tailored to your career path. Stay ahead with the latest interview trends.</p>
            </div>
            <div id="profile" class="page">
                <h1>Profile</h1>
                <p>Manage your account, update preferences, and review your learning history. Customize your experience for optimal results.</p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </main>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Dark mode toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const body = document.body;
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            body.classList.add('dark');
            darkModeToggle.innerHTML = '<i data-lucide="sun"></i> Toggle Light Mode';
            lucide.createIcons(); // Re-render icons
        }
        darkModeToggle.addEventListener('click', () => {
            body.classList.toggle('dark');
            const isDark = body.classList.contains('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            darkModeToggle.innerHTML = isDark 
                ? '<i data-lucide="sun"></i> Toggle Light Mode' 
                : '<i data-lucide="moon"></i> Toggle Dark Mode';
            lucide.createIcons(); // Re-render icons
        });

        // Logout button: redirect to logout.php
        const logoutBtn = document.getElementById('logoutBtn');
        logoutBtn.addEventListener('click', () => {
            window.location.href = 'logout.php';
        });

        // Sidebar navigation
        const sidebarLinks = document.querySelectorAll('.sidebar nav a');
        const pages = document.querySelectorAll('.page');
        const sidebar = document.getElementById('sidebar');

        sidebarLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const pageId = link.getAttribute('data-page');
                // Remove active class from all links and pages
                sidebarLinks.forEach(l => l.classList.remove('active'));
                pages.forEach(p => p.classList.remove('active'));
                // Add active class to clicked link and corresponding page
                link.classList.add('active');
                document.getElementById(pageId).classList.add('active');
                // Close sidebar on mobile after selection
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('open');
                }
            });
        });

        // Hamburger menu for mobile
        const hamburger = document.getElementById('hamburger');
        hamburger.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        // Close sidebar on outside click (mobile)
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !hamburger.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    </script>
</body>
</html>


