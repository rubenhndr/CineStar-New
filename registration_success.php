<?php
session_start();

// Redirect jika tidak ada data pendaftaran berhasil
if (!isset($_SESSION['registration_success'])) {
    header("Location: register.php");
    exit();
}

// Ambil data dari session
$name = $_SESSION['registration_name'];
$email = $_SESSION['registration_email'];

// Hapus data session setelah digunakan
unset($_SESSION['registration_success']);
unset($_SESSION['registration_name']);
unset($_SESSION['registration_email']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Berhasil - CineStar Bioskop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            --light-bg: #F8FAFC;
            --success: #10B981;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: var(--light-bg);
            color: var(--dark);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        /* Header */
        header {
            background-color: var(--dark-bg);
            color: white;
            padding: 1rem 5%;
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
            font-size: 1.8rem;
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
            font-size: 1.6rem;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            gap: 1.5rem;
        }
        
        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }
        
        nav ul li a:hover {
            color: var(--primary);
        }
        
        nav ul li a i {
            margin-right: 6px;
            font-size: 0.9rem;
        }
        
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.4rem;
            cursor: pointer;
        }
        
        /* Main Content */
        .main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 5%;
        }
        
        .success-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 600px;
            padding: 3rem;
            margin: 2rem 0;
            text-align: center;
        }
        
        .success-icon {
            font-size: 4rem;
            color: var(--success);
            margin-bottom: 1.5rem;
            animation: bounce 1s;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-20px);}
            60% {transform: translateY(-10px);}
        }
        
        .success-container h1 {
            font-size: 2rem;
            color: var(--dark);
            margin-bottom: 1rem;
        }
        
        .success-container p {
            color: var(--gray);
            margin-bottom: 1.5rem;
            font-size: 1rem;
        }
        
        .user-info {
            background-color: var(--light-bg);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: left;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 0.8rem;
        }
        
        .info-item:last-child {
            margin-bottom: 0;
        }
        
        .info-item i {
            color: var(--primary);
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 0.8rem 1.8rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(255, 46, 99, 0.2);
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            margin: 0.5rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 46, 99, 0.3);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            box-shadow: none;
        }
        
        .btn-outline:hover {
            background-color: rgba(255, 46, 99, 0.05);
        }
        
        /* Footer */
        footer {
            background-color: var(--dark-bg);
            color: white;
            padding: 1.5rem 5%;
            text-align: center;
            font-size: 0.8rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
            
            nav {
                position: fixed;
                top: 70px;
                left: -100%;
                width: 100%;
                height: calc(100vh - 70px);
                background-color: var(--dark-bg);
                transition: all 0.3s ease;
                padding: 1.5rem;
            }
            
            nav.active {
                left: 0;
            }
            
            nav ul {
                flex-direction: column;
                gap: 1.2rem;
            }
            
            .success-container {
                padding: 2rem;
            }
            
            .success-icon {
                font-size: 3rem;
            }
            
            .success-container h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="navbar">
            <div class="logo">
                <i class="fas fa-film"></i>
                <span>CineStar</span>
            </div>
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
            <nav>
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Beranda</a></li>
                    <li><a href="movies.php"><i class="fas fa-ticket-alt"></i> Film</a></li>
                    <li><a href="booking.php"><i class="fas fa-calendar-alt"></i> Pesan Tiket</a></li>
                    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Masuk</a></li>
                    <li><a href="register.php"><i class="fas fa-user-plus"></i> Daftar</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <main class="main">
        <div class="success-container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Pendaftaran Berhasil!</h1>
            <p>Selamat datang di CineStar Bioskop. Akun Anda telah berhasil dibuat.</p>
            
            <div class="user-info">
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <span>Nama: <strong><?php echo htmlspecialchars($name); ?></strong></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <span>Email: <strong><?php echo htmlspecialchars($email); ?></strong></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-info-circle"></i>
                    <span>Anda sekarang dapat login menggunakan email dan password yang telah didaftarkan.</span>
                </div>
            </div>
            
            <p>Silakan login untuk mulai memesan tiket bioskop favorit Anda.</p>
            
            <div style="margin-top: 2rem;">
                <a href="login.php" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Masuk Sekarang
                </a>
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-home"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </main>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> CineStar. All Rights Reserved.</p>
    </footer>

    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const nav = document.querySelector('nav');
        
        mobileMenuBtn.addEventListener('click', () => {
            nav.classList.toggle('active');
        });
    </script>
</body>
</html>