<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        if ($user['role'] === 'admin') {
            header("Location: admin/index.php");
        } else {
            header("Location: user/index.php");
        }
        exit();
    } else {
        $error = "Email atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CineStar</title>
    <!-- Gunakan CSS yang sama dengan index.php -->
    <link rel="stylesheet" href="css/style.css">
</head>
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
        
        body {
            background-color: #FAFAFA;
            color: var(--dark);
            line-height: 1.6;
        }
        
        /* Header */
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
        
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.9)), 
                        url('https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            padding: 0 5%;
            margin-top: -80px;
            padding-top: 120px;
        }
        
        .hero-content {
            max-width: 800px;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2.5rem;
            opacity: 0.9;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 0.9rem 2.2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 46, 99, 0.3);
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 46, 99, 0.4);
        }
        
        .btn i {
            margin-left: 8px;
        }
        
        /* Now Playing Section */
        .now-playing {
            padding: 6rem 5%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            color: var(--dark);
            position: relative;
            display: inline-block;
        }
        
        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }
        
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2.5rem;
        }
        
        .movie-card {
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
        }
        
        .movie-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .movie-poster-container {
            position: relative;
            overflow: hidden;
            height: 380px;
        }
        
        .movie-poster {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .movie-card:hover .movie-poster {
            transform: scale(1.05);
        }
        
        .movie-rating {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(255, 255, 255, 0.9);
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 600;
            color: var(--primary);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }
        
        .movie-rating i {
            color: #FFC107;
            margin-right: 5px;
            font-size: 0.8rem;
        }
        
        .movie-info {
            padding: 1.8rem;
        }
        
        .movie-title {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .movie-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.2rem;
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .movie-genre {
            display: inline-block;
            background-color: var(--light);
            color: var(--primary);
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 1.2rem;
        }
        
        .btn-sm {
            padding: 0.6rem 1.5rem;
            font-size: 0.9rem;
            width: 100%;
        }
        
        .btn-trailer {
            background-color: var(--secondary);
            margin-bottom: 0.5rem;
        }
        
        /* Enhanced Trailer Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 2000;
            overflow-y: auto;
            padding: 2rem;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: transparent;
            max-width: 1000px;
            margin: 2rem auto;
            border-radius: 15px;
            overflow: hidden;
            animation: slideUp 0.4s ease;
        }
        
        @keyframes slideUp {
            from { 
                transform: translateY(50px);
                opacity: 0;
            }
            to { 
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            color: white;
        }
        
        .modal-header h3 {
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            transition: color 0.3s;
            padding: 0.5rem;
        }
        
        .close-modal:hover {
            color: var(--primary);
        }
        
        .trailer-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .trailer-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .movie-details {
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .movie-details h4 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: white;
        }
        
        .movie-details p {
            line-height: 1.7;
            opacity: 0.9;
        }
        
        /* Modal Controls */
        .modal-controls {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            color: white;
        }
        
        .modal-controls button {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-controls button:hover {
            background: var(--primary);
        }
        
        /* Booking Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 46, 99, 0.1);
        }

        /* Seat Selection */
        .screen {
            text-align: center;
            margin: 1.5rem 0;
            padding: 0.5rem;
            background-color: var(--dark);
            color: white;
            border-radius: 5px;
            font-weight: 500;
        }

        .seat-legend {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            background-color: var(--light);
        }

        .seats-grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .seat {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 40px;
            background-color: var(--light);
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .seat:hover:not(.occupied) {
            background-color: #d1d1d1;
        }

        .seat.selected {
            background-color: var(--primary);
            color: white;
        }

        .seat.occupied {
            background-color: var(--gray);
            color: white;
            cursor: not-allowed;
        }

        .modal-footer {
            padding: 1.5rem;
            text-align: right;
            border-top: 1px solid #eee;
        }

        .booking-summary {
            padding: 1rem;
        }

        .booking-summary h4 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }

        .booking-summary p {
            margin-bottom: 0.8rem;
            line-height: 1.6;
        }

        .booking-summary p strong {
            font-weight: 600;
            color: var(--dark);
            display: inline-block;
            width: 100px;
        }

        /* Features Section */
        .features {
            padding: 6rem 5%;
            background-color: #f8f9fa;
            position: relative;
        }

        .features::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80') center/cover no-repeat;
            opacity: 0.03;
            z-index: 0;
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3rem;
            margin-top: 4rem;
        }

        .feature-card {
            background-color: white;
            border-radius: 15px;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1);
            color: var(--secondary);
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1.2rem;
            color: var(--dark);
            position: relative;
        }

        .feature-card p {
            color: var(--gray);
            font-size: 1rem;
            line-height: 1.7;
        }

        /* Footer */
        footer {
            background-color: var(--dark-bg);
            color: white;
            padding: 5rem 5% 2rem;
            position: relative;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
        }

        .footer-col {
            padding: 1rem;
        }

        .footer-col h3 {
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 10px;
            color: var(--secondary);
        }

        .footer-col h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
        }

        .footer-col p {
            margin-bottom: 1.5rem;
            opacity: 0.8;
            line-height: 1.7;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background-color: var(--primary);
            transform: translateY(-3px);
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 1rem;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .footer-links a:hover {
            opacity: 1;
            color: var(--secondary);
            padding-left: 5px;
        }

        .footer-links a i {
            margin-right: 8px;
            color: var(--primary);
            font-size: 0.9rem;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 3rem;
            margin-top: 3rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            opacity: 0.7;
            font-size: 0.9rem;
        }

        .newsletter-form {
            display: flex;
            margin-top: 1.5rem;
        }

        .newsletter-form input {
            flex: 1;
            padding: 0.8rem;
            border: none;
            border-radius: 5px 0 0 5px;
            font-size: 0.9rem;
        }

        .newsletter-form button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0 1.2rem;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .newsletter-form button:hover {
            background-color: var(--primary-dark);
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
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }

            .seats-grid {
                grid-template-columns: repeat(5, 1fr);
            }

            .footer-container {
                grid-template-columns: 1fr;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            /* Responsive trailer modal */
            .modal {
                padding: 1rem;
            }
            
            .modal-header h3 {
                font-size: 1.4rem;
            }
            
            .movie-details h4 {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 480px) {
            .seat-legend {
                flex-direction: column;
                gap: 0.5rem;
                align-items: center;
            }

            .seats-grid {
                grid-template-columns: repeat(4, 1fr);
            }

            .newsletter-form {
                flex-direction: column;
            }

            .newsletter-form input {
                border-radius: 5px;
                margin-bottom: 0.5rem;
            }

            .newsletter-form button {
                border-radius: 5px;
                padding: 0.8rem;
            }
            
            /* Mobile trailer modal */
            .modal-header h3 {
                font-size: 1.2rem;
            }
            
            .movie-details {
                padding: 1rem;
            }
            
            .movie-details h4 {
                font-size: 1.1rem;
            }
            
            .movie-details p {
                font-size: 0.9rem;
            }
        }
    </style>
<body>
    <header>
        <div class="navbar">
            <div class="logo">
                <i class="fas fa-film"></i>
                <span>CineStar</span>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Beranda</a></li>
                    <li><a href="register.php"><i class="fas fa-user-plus"></i> Daftar</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="hero" style="min-height: 80vh; padding-top: 100px;">
        <div class="hero-content" style="max-width: 500px;">
            <h1>Login ke Akun Anda</h1>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn">Login <i class="fas fa-sign-in-alt"></i></button>
            </form>
            
            <p style="margin-top: 1.5rem;">Belum punya akun? <a href="register.php" style="color: var(--secondary);">Daftar disini</a></p>
        </div>
    </section>

    <!-- Footer sama seperti di index.php -->
    <footer>
        <div class="footer-container">
            <div class="footer-col">
                <h3>Tentang CineStar</h3>
                <p>CineStar adalah platform pemesanan tiket bioskop online terkemuka yang memberikan pengalaman menonton terbaik dengan kenyamanan dan kemudahan bagi pelanggan.</p>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h3>Link Cepat</h3>
                <ul class="footer-links">
                    <li><a href="../index.php"><i class="fas fa-chevron-right"></i> Beranda</a></li>
                    <li><a href="../movies.php"><i class="fas fa-chevron-right"></i> Film Terbaru</a></li>
                    <li><a href="../booking.php"><i class="fas fa-chevron-right"></i> Pesan Tiket</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Promo</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Lokasi Bioskop</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Kontak Kami</h3>
                <ul class="footer-links">
                    <li><a href="#"><i class="fas fa-map-marker-alt"></i> Jl. Bioskop No. 123, Jakarta</a></li>
                    <li><a href="tel:+622112345678"><i class="fas fa-phone-alt"></i> (021) 1234-5678</a></li>
                    <li><a href="mailto:info@cinestar.com"><i class="fas fa-envelope"></i> info@cinestar.com</a></li>
                    <li><a href="#"><i class="fas fa-clock"></i> Buka Setiap Hari 08:00 - 23:00</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Newsletter</h3>
                <p>Dapatkan informasi promo dan film terbaru langsung ke email Anda.</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Alamat Email Anda" required>
                    <button type="submit"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> CineStar. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>