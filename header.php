<?php

// Cek apakah user sudah login, jika tidak redirect ke login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Ambil data user dari session
$user_id = $_SESSION['user_id'];
require '../config.php';
$user_query = "SELECT name, email FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineStar - Bioskop Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF2E63;
            --primary-dark: #E82154;
            --secondary: #08D9D6;
            --dark: #252A34;
            --light: #EAEAEA;
            --gray: #6B7280;
            --dark-bg: #0F172A;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        /* Header Styles */
        header {
            background-color: var(--dark-bg);
            color: white;
            padding: 1.5rem 5%;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logo {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: flex;
            align-items: center;
        }
        
        .logo i {
            margin-right: 10px;
            font-size: 1.8rem;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 2rem;
        }
        
        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        nav ul li a:hover {
            color: var(--primary);
        }
        
        nav ul li a i {
            margin-right: 8px;
        }
        
        .user-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .user-dropdown-btn {
            display: flex;
            align-items: center;
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-weight: 500;
        }
        
        .user-dropdown-btn i {
            margin-left: 8px;
        }
        
        .user-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            z-index: 1;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .user-dropdown:hover .user-dropdown-content {
            display: block;
        }
        
        .user-dropdown-content a {
            color: var(--dark);
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: all 0.3s;
        }
        
        .user-dropdown-content a:hover {
            background-color: var(--light);
            color: var(--primary);
        }
        
        .user-dropdown-header {
            padding: 12px 16px;
            background-color: var(--light);
            border-bottom: 1px solid #eee;
        }
        
        .user-dropdown-header p:first-child {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .user-dropdown-header p:last-child {
            font-size: 0.8rem;
            color: var(--gray);
        }
        
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
            
            nav {
                position: fixed;
                top: 80px;
                left: -100%;
                width: 100%;
                height: calc(100vh - 80px);
                background-color: var(--dark-bg);
                transition: all 0.3s ease;
                padding: 2rem;
            }
            
            nav.active {
                left: 0;
            }
            
            nav ul {
                flex-direction: column;
                gap: 1.5rem;
            }
            
            nav ul li {
                margin-left: 0;
            }
            
            .user-dropdown {
                width: 100%;
            }
            
            .user-dropdown-content {
                position: static;
                width: 100%;
                box-shadow: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="navbar">
            <a href="index.php" class="logo">
                <i class="fas fa-film"></i>
                <span>CineStar</span>
            </a>
            
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
            
            <nav>
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Beranda</a></li>
                    <li><a href="history.php"><i class="fas fa-history"></i> Riwayat</a></li>
                    
                    <li class="user-dropdown">
                        <button class="user-dropdown-btn">
                            <?php echo htmlspecialchars($user['name']); ?> <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="user-dropdown-content">
                            <div class="user-dropdown-header">
                                <p><?php echo htmlspecialchars($user['name']); ?></p>
                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <a href="profile.php"><i class="fas fa-user"></i> Profil Saya</a>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const nav = document.querySelector('nav');
        
        mobileMenuBtn.addEventListener('click', () => {
            nav.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdowns = document.querySelectorAll('.user-dropdown');
            dropdowns.forEach(dropdown => {
                if (!dropdown.contains(event.target)) {
                    const content = dropdown.querySelector('.user-dropdown-content');
                    content.style.display = 'none';
                }
            });
        });
        
        // Responsive navigation
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                nav.classList.remove('active');
            }
        });
    </script>