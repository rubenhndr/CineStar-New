<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require '../config.php';

// Ambil data transaksi dari parameter URL
$transaction_id = $_GET['transaction'] ?? null;
if (!$transaction_id) {
    header("Location: ../index.php");
    exit();
}

// Query data transaksi
$transaction_query = "SELECT t.*, f.title, f.poster_url 
                     FROM transactions t 
                     JOIN films f ON t.film_id = f.id 
                     WHERE t.id = ? AND t.user_id = ?";
$transaction_stmt = $conn->prepare($transaction_query);
$transaction_stmt->execute([$transaction_id, $_SESSION['user_id']]);
$transaction = $transaction_stmt->fetch();

if (!$transaction) {
    header("Location: ../index.php");
    exit();
}

// Query data user
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

// Generate QR code (menggunakan API dari goqr.me)
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($transaction['qr_code']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pemesanan - CineStar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Gunakan CSS yang sama seperti dashboard */
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
        
        /* Header - sama seperti dashboard */
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
        main {
            padding-top: 100px;
            min-height: 100vh;
        }
        
        .confirmation-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 5%;
            text-align: center;
        }
        
        .confirmation-card {
            background-color: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .confirmation-icon {
            font-size: 5rem;
            color: var(--secondary);
            margin-bottom: 1rem;
        }
        
        .confirmation-title {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--dark);
        }
        
        .confirmation-message {
            margin-bottom: 2rem;
            color: var(--gray);
        }
        
        .ticket-details {
            text-align: left;
            margin-bottom: 2rem;
            padding: 1rem;
            background-color: var(--light);
            border-radius: 10px;
        }
        
        .ticket-detail {
            display: flex;
            margin-bottom: 0.5rem;
        }
        
        .ticket-detail strong {
            width: 150px;
            display: inline-block;
        }
        
        .qr-code {
            margin: 2rem 0;
        }
        
        .qr-code img {
            max-width: 200px;
            border: 1px solid var(--light);
            padding: 1rem;
            border-radius: 10px;
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
            margin-top: 1rem;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 46, 99, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            box-shadow: none;
            margin-right: 1rem;
        }
        
        .btn-outline:hover {
            background: var(--primary);
            color: white;
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
                    <li><a href="../index.php"><i class="fas fa-home"></i> Beranda</a></li>
                    <li><a href="history.php"><i class="fas fa-history"></i> Riwayat</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="confirmation-container">
            <div class="confirmation-card">
                <div class="confirmation-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="confirmation-title">Pemesanan Berhasil!</h1>
                <p class="confirmation-message">Terima kasih telah memesan tiket di CineStar. Berikut detail pemesanan Anda:</p>
                
                <div class="ticket-details">
                    <div class="ticket-detail">
                        <strong>Film:</strong>
                        <span><?php echo htmlspecialchars($transaction['title']); ?></span>
                    </div>
                    <div class="ticket-detail">
                        <strong>Nomor Kursi:</strong>
                        <span><?php echo htmlspecialchars($transaction['seat_number']); ?></span>
                    </div>
                    <div class="ticket-detail">
                        <strong>Tanggal Transaksi:</strong>
                        <span><?php echo date('d F Y H:i', strtotime($transaction['transaction_date'])); ?></span>
                    </div>
                    <div class="ticket-detail">
                        <strong>Kode Tiket:</strong>
                        <span><?php echo htmlspecialchars($transaction['qr_code']); ?></span>
                    </div>
                </div>
                
                <div class="qr-code">
                    <img src="<?php echo $qr_code_url; ?>" alt="QR Code Tiket">
                    <p>Scan QR code ini saat masuk bioskop</p>
                </div>
                
                <div class="action-buttons">
                    <a href="../index.php" class="btn btn-outline"><i class="fas fa-home"></i> Kembali ke Beranda</a>
                    <a href="history.php" class="btn"><i class="fas fa-history"></i> Lihat Riwayat</a>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const nav = document.querySelector('nav');
        
        mobileMenuBtn.addEventListener('click', () => {
            nav.classList.toggle('active');
        });
        
        // Responsive navigation
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                nav.classList.remove('active');
            }
        });
    </script>
</body>
</html>