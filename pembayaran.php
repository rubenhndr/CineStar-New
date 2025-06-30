<?php
session_start();

// Redirect jika tidak ada data booking
if (!isset($_SESSION['booking_data'])) {
    header("Location: index.php");
    exit;
}

$booking = $_SESSION['booking_data'];

// Proses pembayaran jika form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    // Validasi metode pembayaran
    $payment_methods = ['gopay', 'ovo', 'dana', 'bca'];
    if (!isset($_POST['payment_method']) || !in_array($_POST['payment_method'], $payment_methods)) {
        $error = "Silakan pilih metode pembayaran yang valid";
    } else {
        // Simpan metode pembayaran
        $booking['payment_method'] = $_POST['payment_method'];
        $_SESSION['booking_data'] = $booking;
        
        // Redirect ke halaman konfirmasi
        header("Location: konfirmasi.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - CineStar</title>
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
        
        /* Main Content */
        .main {
            padding-top: 90px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 5%;
        }
        
        .payment-container {
            background-color: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 3rem;
        }
        
        .section-title {
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 10px;
        }
        
        .section-title h2 {
            font-size: 1.8rem;
            color: var(--dark);
        }
        
        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
        }
        
        /* Booking Summary */
        .booking-summary {
            background-color: var(--light-bg);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
            font-size: 0.95rem;
        }
        
        .summary-item span:first-child {
            color: var(--gray);
        }
        
        .summary-item span:last-child {
            font-weight: 500;
            color: var(--dark);
        }
        
        .summary-total {
            font-weight: 700;
            font-size: 1.1rem;
            border-top: 1px solid #e2e8f0;
            padding-top: 0.8rem;
            margin-top: 0.8rem;
            color: var(--primary);
        }
        
        /* Payment Methods */
        .payment-methods {
            margin-bottom: 2rem;
        }
        
        .payment-options {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .payment-option {
            position: relative;
        }
        
        .payment-option input {
            position: absolute;
            opacity: 0;
        }
        
        .payment-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .payment-option input:checked + label {
            border-color: var(--primary);
            background-color: rgba(255, 46, 99, 0.05);
        }
        
        .payment-option img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            margin-bottom: 0.5rem;
        }
        
        .payment-option span {
            font-size: 0.9rem;
            font-weight: 500;
            text-align: center;
        }
        
        /* Payment Button */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 0.9rem 2rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(255, 46, 99, 0.2);
            border: none;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 46, 99, 0.3);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        /* Error Message */
        .error-message {
            color: var(--primary);
            background-color: rgba(255, 46, 99, 0.1);
            padding: 0.8rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .error-message i {
            margin-right: 8px;
        }
        
        /* Footer */
        footer {
            background-color: var(--dark-bg);
            color: white;
            padding: 3rem 5%;
            text-align: center;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: left;
        }
        
        .footer-section h3 {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            color: white;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 0.8rem;
        }
        
        .footer-section ul li a {
            color: var(--gray);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-section ul li a:hover {
            color: var(--secondary);
        }
        
        .social-icons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .social-icons a {
            color: white;
            background-color: var(--gray);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .social-icons a:hover {
            background-color: var(--primary);
            transform: translateY(-3px);
        }
        
        .copyright {
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main {
                padding-top: 80px;
            }
            
            .section-title h2 {
                font-size: 1.5rem;
            }
            
            .payment-options {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .navbar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            nav {
                width: 100%;
                margin-top: 1rem;
            }
            
            nav ul {
                justify-content: space-between;
            }
            
            nav ul li {
                margin-left: 0;
            }
        }
        
        @media (max-width: 480px) {
            .payment-options {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
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
                </ul>
            </nav>
        </div>
    </header>
    
    <main class="main">
        <div class="container">
            <div class="payment-container">
                <div class="section-title">
                    <h2>Pembayaran Tiket</h2>
                </div>
                
                <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
                <?php endif; ?>
                
                <div class="booking-summary">
                    <h3>Ringkasan Pemesanan</h3>
                    <div class="summary-item">
                        <span>Kode Booking:</span>
                        <span><?php echo htmlspecialchars($booking['booking_ref']); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Film:</span>
                        <span><?php echo htmlspecialchars($booking['film_title']); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Jadwal:</span>
                        <span><?php echo htmlspecialchars($booking['showtime']); ?> WIB</span>
                    </div>
                    <div class="summary-item">
                        <span>Kursi:</span>
                        <span><?php echo htmlspecialchars(implode(', ', $booking['seats'])); ?></span>
                    </div>
                    <div class="summary-item summary-total">
                        <span>Total Pembayaran:</span>
                        <span>Rp <?php echo number_format($booking['total_price'], 0, ',', '.'); ?></span>
                    </div>
                </div>
                
                <form method="POST" action="pembayaran.php">
                    <div class="payment-methods">
                        <h3>Metode Pembayaran</h3>
                        <div class="payment-options">
                            <div class="payment-option">
                                <input type="radio" id="gopay" name="payment_method" value="gopay">
                                <label for="gopay">
                                    <img src="https://logos-world.net/wp-content/uploads/2023/02/GoPay-Logo.png" alt="GoPay">
                                    <span>GoPay</span>
                                </label>
                            </div>
                            
                            <div class="payment-option">
                                <input type="radio" id="ovo" name="payment_method" value="ovo">
                                <label for="ovo">
                                    <img src="https://1.bp.blogspot.com/-R8tAzIPSkIU/X-wzaYpNpxI/AAAAAAAABWs/gw-W4OzVvqsKpue1k-ij_MS4lGQC8thWACLcBGAsYHQ/s2048/OVO.png" alt="OVO">
                                    <span>OVO</span>
                                </label>
                            </div>
                            
                            <div class="payment-option">
                                <input type="radio" id="dana" name="payment_method" value="dana">
                                <label for="dana">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/7/72/Logo_dana_blue.svg/1200px-Logo_dana_blue.svg.png" alt="DANA">
                                    <span>DANA</span>
                                </label>
                            </div>
                            
                            <div class="payment-option">
                                <input type="radio" id="bca" name="payment_method" value="bca">
                                <label for="bca">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5c/Bank_Central_Asia.svg/1200px-Bank_Central_Asia.svg.png" alt="Bank BCA">
                                    <span>Transfer BCA</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="process_payment" class="btn">
                        <i class="fas fa-credit-card"></i> Proses Pembayaran
                    </button>
                </form>
            </div>
        </div>
    </main>
    
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Tentang CineStar</h3>
                <p>CineStar adalah bioskop modern dengan teknologi terkini untuk pengalaman menonton terbaik.</p>
            </div>
            <div class="footer-section">
                <h3>Menu</h3>
                <ul>
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="movies.php">Film</a></li>
                    <li><a href="booking.php">Pesan Tiket</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Kontak</h3>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> Jl. Cinema No. 123, Jakarta</li>
                    <li><i class="fas fa-phone"></i> (021) 1234-5678</li>
                    <li><i class="fas fa-envelope"></i> info@cinestar.com</li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Follow Kami</h3>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; <?php echo date('Y'); ?> CineStar. All Rights Reserved.</p>
        </div>
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