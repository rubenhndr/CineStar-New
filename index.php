<?php
// Koneksi ke database
$host = 'localhost';
$dbname = 'bioskop_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Koneksi gagal: " . $e->getMessage();
}

// Query untuk mendapatkan film yang sedang tayang
$query = "SELECT * FROM films WHERE is_playing = 1 LIMIT 4";
$stmt = $conn->prepare($query);
$stmt->execute();
$films = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Proses form booking jika ada data yang dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking'])) {
    // Simpan data booking ke session
    session_start();
    $_SESSION['booking_data'] = [
        'film_id' => $_POST['film_id'],
        'film_title' => $_POST['film_title'],
        'name' => $_POST['name'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'],
        'showtime' => $_POST['showtime'],
        'seats' => explode(',', $_POST['selected_seats']),
        'total_price' => count(explode(',', $_POST['selected_seats'])) * 35000,
        'booking_ref' => 'CST-' . uniqid()
    ];
    
    // Redirect ke halaman pembayaran
    header("Location: pembayaran.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineStar - Pemesanan Tiket Bioskop Online</title>
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
        <?php if(isset($_SESSION['user_id'])): ?>
            <li><a href="user/index.php"><i class="fas fa-user"></i> Akun</a></li>
            <li><a href="user/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        <?php else: ?>
        <?php endif; ?>
    </ul>
</nav>
    </header>
    
    <section class="hero">
        <div class="hero-content">
            <h1>Nikmati Pengalaman Bioskop Premium</h1>
            <p>Pesan tiket bioskop online dengan mudah, pilih kursi favorit Anda, dan nikmati film terbaru dengan kualitas terbaik tanpa harus antri.</p>
            <a href="#now-playing" class="btn">Pesan Sekarang <i class="fas fa-arrow-right"></i></a>
        </div>
    </section>
    
    <section class="now-playing" id="now-playing">
        <div class="section-title">
            <h2>Sedang Tayang</h2>
        </div>
        <div class="movies-grid">
            <?php foreach ($films as $film): ?>
            <div class="movie-card">
                <div class="movie-poster-container">
                    <img src="<?php echo htmlspecialchars($film['poster_url']); ?>" alt="<?php echo htmlspecialchars($film['title']); ?>" class="movie-poster">
                    <div class="movie-rating">
                        <i class="fas fa-star"></i>
                        <?php echo number_format($film['rating'] ?? 4.5, 1); ?>
                    </div>
                </div>
                <div class="movie-info">
                    <h3 class="movie-title"><?php echo htmlspecialchars($film['title']); ?></h3>
                    <div class="movie-meta">
                        <span><i class="far fa-clock"></i> <?php echo htmlspecialchars($film['duration'] ?? '120'); ?> min</span>
                        <span><i class="fas fa-calendar-alt"></i> <?php echo date('Y', strtotime($film['release_date'] ?? '2023-01-01')); ?></span>
                    </div>
                    <span class="movie-genre"><?php echo htmlspecialchars($film['genre']); ?></span>
                    
                    <?php if (!empty($film['trailer_url'])): ?>
                    <button class="btn btn-sm btn-trailer watch-trailer-btn" 
                            data-trailer-url="<?php echo htmlspecialchars($film['trailer_url']); ?>"
                            data-film-title="<?php echo htmlspecialchars($film['title']); ?>"
                            data-film-synopsis="<?php echo htmlspecialchars($film['synopsis'] ?? 'Nikmati trailer resmi film ini.'); ?>">
                        <i class="fas fa-play"></i> Tonton Trailer
                    </button>
                    <?php endif; ?>
                    
                    <button class="btn btn-sm book-btn" data-film-id="<?php echo $film['id']; ?>" data-film-title="<?php echo htmlspecialchars($film['title']); ?>">
                        Pesan Tiket <i class="fas fa-ticket-alt"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- Enhanced Trailer Modal -->
    <div class="modal" id="trailerModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="trailerModalTitle">Trailer Film</h3>
                <button class="close-modal">&times;</button>
            </div>
            
            <div class="trailer-container" id="trailerContainer">
                <!-- Trailer will be loaded here -->
            </div>
            
            <div class="movie-details">
                <h4 id="trailerFilmTitle"></h4>
                <p id="trailerFilmSynopsis"></p>
            </div>
            
            <div class="modal-controls">
                <button id="fullscreenBtn"><i class="fas fa-expand"></i> Layar Penuh</button>
                <button id="volumeBtn"><i class="fas fa-volume-up"></i> Volume</button>
            </div>
        </div>
    </div>
    
    <!-- Modal Pemesanan Tiket -->
    <div class="modal" id="bookingModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Pesan Tiket</h3>
                <button class="close-modal">&times;</button>
            </div>
            <form id="bookingForm" method="POST" action="index.php">
                <input type="hidden" name="booking" value="1">
                <input type="hidden" id="filmId" name="film_id">
                <input type="hidden" id="filmTitle" name="film_title">
                <input type="hidden" id="selectedSeats" name="selected_seats">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Nama Lengkap</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Nomor Telepon</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="showtime">Jadwal Tayang</label>
                        <select id="showtime" name="showtime" required>
                            <option value="">Pilih Jadwal</option>
                            <option value="10:00">10:00 WIB</option>
                            <option value="13:00">13:00 WIB</option>
                            <option value="16:00">16:00 WIB</option>
                            <option value="19:00">19:00 WIB</option>
                            <option value="22:00">22:00 WIB</option>
                        </select>
                    </div>
                    
                    <div class="seat-selection">
                        <div class="screen">Layar Bioskop</div>
                        
                        <div class="seat-legend">
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: var(--light);"></div>
                                <span>Tersedia</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: var(--primary);"></div>
                                <span>Dipilih</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: var(--gray);"></div>
                                <span>Terisi</span>
                            </div>
                        </div>
                        
                        <div class="seats-grid" id="seatsGrid">
                            <!-- Kursi akan di-generate oleh JavaScript -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn" id="continuePayment">Lanjutkan Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
    
    <section class="features">
        <div class="features-container">
            <div class="section-title">
                <h2>Kenapa Memilih Kami?</h2>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <h3>Pesan Mudah</h3>
                    <p>Proses pemesanan tiket yang cepat dan mudah hanya dalam beberapa langkah saja. Nikmati kemudahan memesan tiket kapan saja dan di mana saja.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chair"></i>
                    </div>
                    <h3>Pilih Kursi</h3>
                    <p>Pilih kursi favorit Anda secara langsung dengan sistem pemilihan kursi online. Lihat denah bioskop dan pilih posisi terbaik sesuai keinginan.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3>Pembayaran Aman</h3>
                    <p>Berbagai metode pembayaran yang aman dan terpercaya untuk kenyamanan Anda. Kami mendukung transfer bank, e-wallet, dan kartu kredit.</p>
                </div>
            </div>
        </div>
    </section>
    
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
                    <li><a href="index.php"><i class="fas fa-chevron-right"></i> Beranda</a></li>
                    <li><a href="movies.php"><i class="fas fa-chevron-right"></i> Film Terbaru</a></li>
                    <li><a href="booking.php"><i class="fas fa-chevron-right"></i> Pesan Tiket</a></li>
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

    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const nav = document.querySelector('nav');
        
        mobileMenuBtn.addEventListener('click', () => {
            nav.classList.toggle('active');
        });
        
        // Fungsi untuk mengekstrak ID YouTube dari URL
        function getYouTubeId(url) {
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
            const match = url.match(regExp);
            return (match && match[2].length === 11) ? match[2] : null;
        }
        
        // Enhanced Trailer Modal
        const trailerModal = document.getElementById('trailerModal');
        const watchTrailerBtns = document.querySelectorAll('.watch-trailer-btn');
        const closeModalButtons = document.querySelectorAll('.close-modal');
        let trailerIframe = null;
        
        // Buka modal trailer saat tombol tonton trailer diklik
        watchTrailerBtns.forEach(button => {
            button.addEventListener('click', () => {
                const trailerUrl = button.getAttribute('data-trailer-url');
                const filmTitle = button.getAttribute('data-film-title');
                const filmSynopsis = button.getAttribute('data-film-synopsis');
                const youtubeId = getYouTubeId(trailerUrl);
                
                if (youtubeId) {
                    document.getElementById('trailerModalTitle').textContent = `Trailer ${filmTitle}`;
                    document.getElementById('trailerFilmTitle').textContent = filmTitle;
                    document.getElementById('trailerFilmSynopsis').textContent = filmSynopsis;
                    
                    // Buat iframe YouTube dengan parameter tambahan
                    const trailerContainer = document.getElementById('trailerContainer');
                    trailerContainer.innerHTML = `
                        <iframe src="https://www.youtube.com/embed/${youtubeId}?autoplay=1&rel=0&modestbranding=1&showinfo=0" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen
                                title="${filmTitle} Trailer"></iframe>
                    `;
                    
                    trailerModal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                    document.body.style.position = 'fixed';
                    
                    // Simpan referensi iframe
                    trailerIframe = trailerContainer.querySelector('iframe');
                }
            });
        });
        
        // Fullscreen functionality
        document.getElementById('fullscreenBtn').addEventListener('click', () => {
            if (!trailerIframe) return;
            
            if (trailerIframe.requestFullscreen) {
                trailerIframe.requestFullscreen();
            } else if (trailerIframe.webkitRequestFullscreen) {
                trailerIframe.webkitRequestFullscreen();
            } else if (trailerIframe.msRequestFullscreen) {
                trailerIframe.msRequestFullscreen();
            }
        });
        
        // Volume control
        const volumeBtn = document.getElementById('volumeBtn');
        let isMuted = false;
        
        volumeBtn.addEventListener('click', () => {
            if (!trailerIframe) return;
            
            // Ini hanya contoh UI, kontrol volume sebenarnya memerlukan YouTube API
            isMuted = !isMuted;
            volumeBtn.innerHTML = isMuted ? 
                '<i class="fas fa-volume-mute"></i> Suara' : 
                '<i class="fas fa-volume-up"></i> Volume';
            
            // Dalam implementasi nyata, gunakan YouTube API untuk mengontrol volume
            // player.setVolume(isMuted ? 0 : 100);
        });
        
        // Tutup modal
        closeModalButtons.forEach(button => {
            button.addEventListener('click', () => {
                trailerModal.style.display = 'none';
                document.getElementById('bookingModal').style.display = 'none';
                document.body.style.overflow = 'auto';
                document.body.style.position = 'static';
                
                // Hentikan video saat modal ditutup
                if (trailerIframe) {
                    const iframeSrc = trailerIframe.src;
                    trailerIframe.src = iframeSrc.replace('autoplay=1', 'autoplay=0');
                    trailerIframe = null;
                }
            });
        });
        
        // Tutup modal saat klik di luar area modal
        window.addEventListener('click', (e) => {
            if (e.target === trailerModal) {
                trailerModal.style.display = 'none';
                document.body.style.overflow = 'auto';
                document.body.style.position = 'static';
                
                // Hentikan video saat modal ditutup
                if (trailerIframe) {
                    const iframeSrc = trailerIframe.src;
                    trailerIframe.src = iframeSrc.replace('autoplay=1', 'autoplay=0');
                    trailerIframe = null;
                }
            }
            
            if (e.target === document.getElementById('bookingModal')) {
                document.getElementById('bookingModal').style.display = 'none';
                document.body.style.overflow = 'auto';
                document.body.style.position = 'static';
            }
        });
        
        // Modal Booking
        const bookButtons = document.querySelectorAll('.book-btn');
        const bookingModal = document.getElementById('bookingModal');
        const bookingForm = document.getElementById('bookingForm');
        const seatsGrid = document.getElementById('seatsGrid');
        let selectedSeats = [];
        
        // Buka modal booking saat tombol pesan diklik
        bookButtons.forEach(button => {
            button.addEventListener('click', () => {
                const filmId = button.getAttribute('data-film-id');
                const filmTitle = button.getAttribute('data-film-title');
                
                document.getElementById('filmId').value = filmId;
                document.getElementById('filmTitle').value = filmTitle;
                document.querySelector('#bookingModal .modal-header h3').textContent = `Pesan Tiket - ${filmTitle}`;
                bookingModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
                document.body.style.position = 'fixed';
                
                // Reset form dan selected seats
                bookingForm.reset();
                selectedSeats = [];
                document.getElementById('selectedSeats').value = '';
                
                // Generate kursi
                generateSeats();
            });
        });
        
        // Generate kursi bioskop yang lebih sederhana
        function generateSeats() {
            seatsGrid.innerHTML = '';
            const rows = ['A', 'B', 'C', 'D', 'E'];
            const cols = 8;
            
            rows.forEach(row => {
                for (let i = 1; i <= cols; i++) {
                    const seat = document.createElement('div');
                    seat.className = 'seat';
                    seat.textContent = row + i;
                    
                    // Randomly mark some seats as occupied (for demo)
                    if (Math.random() < 0.3) {
                        seat.classList.add('occupied');
                    }
                    
                    seat.addEventListener('click', () => {
                        if (!seat.classList.contains('occupied')) {
                            seat.classList.toggle('selected');
                            
                            const seatNumber = seat.textContent;
                            const index = selectedSeats.indexOf(seatNumber);
                            
                            if (index === -1) {
                                selectedSeats.push(seatNumber);
                            } else {
                                selectedSeats.splice(index, 1);
                            }
                            
                            document.getElementById('selectedSeats').value = selectedSeats.join(', ');
                        }
                    });
                    
                    seatsGrid.appendChild(seat);
                }
            });
        }
        
        // Validasi form sebelum submit
        bookingForm.addEventListener('submit', (e) => {
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            const showtime = document.getElementById('showtime').value;
            
            if (!name || !email || !phone || !showtime || selectedSeats.length === 0) {
                e.preventDefault();
                alert('Harap lengkapi semua data dan pilih kursi terlebih dahulu');
                return;
            }
        });
        
        // Responsive navigation
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                nav.classList.remove('active');
            }
        });

        // Newsletter form submission
        const newsletterForm = document.querySelector('.newsletter-form');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const emailInput = newsletterForm.querySelector('input[type="email"]');
                if (emailInput.value) {
                    alert('Terima kasih telah berlangganan newsletter kami!');
                    emailInput.value = '';
                }
            });
        }
        
        // Close modal when pressing Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                trailerModal.style.display = 'none';
                bookingModal.style.display = 'none';
                document.body.style.overflow = 'auto';
                document.body.style.position = 'static';
                
                if (trailerIframe) {
                    const iframeSrc = trailerIframe.src;
                    trailerIframe.src = iframeSrc.replace('autoplay=1', 'autoplay=0');
                    trailerIframe = null;
                }
            }
        });
    </script>
</body>
</html>