<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Koneksi ke database
require '../config.php';

// Query untuk mendapatkan film yang sedang tayang
$query = "SELECT * FROM films WHERE is_playing = 1";
$stmt = $conn->prepare($query);
$stmt->execute();
$films = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query untuk mendapatkan data user
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard User - CineStar</title>
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
        
        /* Main Content */
        main {
            padding-top: 100px;
            min-height: 100vh;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 5%;
        }

        /* User Profile Section */
        .user-profile {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .profile-card {
            flex: 1;
            min-width: 300px;
            background-color: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--primary);
            margin-right: 1.5rem;
        }
        
        .profile-info h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .profile-info p {
            color: var(--gray);
        }
        
        .profile-details {
            margin-top: 1.5rem;
        }
        
        .detail-item {
            display: flex;
            margin-bottom: 1rem;
        }
        
        .detail-item i {
            width: 30px;
            color: var(--primary);
        }
        
        .detail-content h4 {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 0.2rem;
        }
        
        .detail-content p {
            font-weight: 500;
        }
        
        .edit-profile {
            margin-top: 1.5rem;
        }
        
        /* Movies Section */
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2.5rem;
            margin-top: 2rem;
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
        
        .btn-sm {
            padding: 0.6rem 1.5rem;
            font-size: 0.9rem;
            width: 100%;
        }
        
        .btn-trailer {
            background-color: var(--secondary);
            margin-bottom: 0.5rem;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            box-shadow: none;
        }
        
        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }
        
        /* Section Header */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .section-header h2 {
            font-size: 1.8rem;
            color: var(--dark);
        }
        
        .section-header a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-header a:hover {
            text-decoration: underline;
        }
        
        /* Trailer Modal */
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
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: transparent;
            max-width: 900px;
            margin: 2rem auto;
            border-radius: 15px;
            overflow: hidden;
            animation: slideUp 0.4s ease;
            position: relative;
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
            padding: 0 1.5rem;
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
            margin: 1.5rem;
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
        
        .modal-controls {
            display: flex;
            justify-content: flex-end;
            padding: 0 1.5rem 1.5rem;
            gap: 1rem;
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
        
        /* Footer */
        footer {
            background-color: var(--dark-bg);
            color: white;
            padding: 5rem 5% 2rem;
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
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-avatar {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .modal-content {
                margin: 1rem;
            }
            
            .modal-header h3 {
                font-size: 1.4rem;
            }
            
            .movie-details h4 {
                font-size: 1.2rem;
            }
            
            .footer-container {
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
                    <li><a href="#"><i class="fas fa-home"></i> Beranda</a></li>
                    <li><a href="history.php"><i class="fas fa-history"></i> Riwayat</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="dashboard-container">
            <!-- User Profile Section -->
            <div class="user-profile">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                            <p>Member sejak <?php echo date('d F Y', strtotime($user['created_at'])); ?></p>
                        </div>
                    </div>
                    <div class="profile-details">
                        <div class="detail-item">
                            <i class="fas fa-envelope"></i>
                            <div class="detail-content">
                                <h4>Email</h4>
                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-phone"></i>
                            <div class="detail-content">
                                <h4>Telepon</h4>
                                <p><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></p>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-user-tag"></i>
                            <div class="detail-content">
                                <h4>Status</h4>
                                <p><?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-outline edit-profile">
                        <i class="fas fa-user-edit"></i> Edit Profil
                    </button>
                </div>
                
                <div class="profile-card" style="flex: 2;">
                    <div class="section-header">
                        <h2>Aktivitas Terakhir</h2>
                        <a href="history.php">Lihat Semua <i class="fas fa-chevron-right"></i></a>
                    </div>
                    <p>Riwayat pemesanan dan aktivitas terakhir Anda akan muncul di sini.</p>
                </div>
            </div>

            <!-- Movies Section -->
            <div class="section-header">
                <h2>Film Sedang Tayang</h2>
                <a href="../index.php">Lihat Semua <i class="fas fa-chevron-right"></i></a>
            </div>

            <div class="movies-grid">
                <?php foreach ($films as $film): ?>
                <div class="movie-card">
                    <div class="movie-poster-container">
                        <img src="<?php echo htmlspecialchars($film['poster_url']); ?>" alt="<?php echo htmlspecialchars($film['title']); ?>" class="movie-poster">
                        <div class="movie-rating">
                            <i class="fas fa-star"></i>
                            <?php echo number_format(rand(35, 50)/10, 1); ?>
                        </div>
                    </div>
                    <div class="movie-info">
                        <h3 class="movie-title"><?php echo htmlspecialchars($film['title']); ?></h3>
                        <div class="movie-meta">
                            <span><i class="far fa-clock"></i> <?php echo htmlspecialchars($film['duration']); ?> min</span>
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('Y', strtotime($film['release_date'])); ?></span>
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
                        
                        <a href="booking.php?film=<?php echo $film['id']; ?>" class="btn btn-sm">
                            Pesan Tiket <i class="fas fa-ticket-alt"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

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
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Film Terbaru</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Pesan Tiket</a></li>
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

    <!-- Trailer Modal -->
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

    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const nav = document.querySelector('nav');
        
        mobileMenuBtn.addEventListener('click', () => {
            nav.classList.toggle('active');
        });
        
        // Fungsi untuk mengekstrak ID YouTube dari URL
        function getYouTubeId(url) {
            // Handle berbagai format URL YouTube
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
            const match = url.match(regExp);
            
            if (match && match[2].length === 11) {
                return match[2];
            } else if (url.includes('youtube.com/embed/')) {
                return url.split('youtube.com/embed/')[1].split('?')[0];
            } else if (url.includes('youtu.be/')) {
                return url.split('youtu.be/')[1].split('?')[0];
            }
            return null;
        }
        
        // Enhanced Trailer Modal
        const trailerModal = document.getElementById('trailerModal');
        const watchTrailerBtns = document.querySelectorAll('.watch-trailer-btn');
        const closeModalButtons = document.querySelectorAll('.close-modal');
        let player;
        let isMuted = false;
        
        // Load YouTube API script
        const tag = document.createElement('script');
        tag.src = "https://www.youtube.com/iframe_api";
        const firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        
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
                    
                    // Buat iframe YouTube
                    const trailerContainer = document.getElementById('trailerContainer');
                    trailerContainer.innerHTML = `
                        <div id="youtubePlayer"></div>
                    `;
                    
                    // Initialize YouTube player
                    player = new YT.Player('youtubePlayer', {
                        height: '100%',
                        width: '100%',
                        videoId: youtubeId,
                        playerVars: {
                            'autoplay': 1,
                            'controls': 1,
                            'rel': 0,
                            'modestbranding': 1,
                            'showinfo': 0
                        },
                        events: {
                            'onReady': onPlayerReady,
                            'onStateChange': onPlayerStateChange
                        }
                    });
                    
                    trailerModal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                    
                    // Reset volume button state
                    isMuted = false;
                    document.getElementById('volumeBtn').innerHTML = '<i class="fas fa-volume-up"></i> Volume';
                } else {
                    alert('URL trailer tidak valid. Pastikan URL YouTube yang valid.');
                }
            });
        });
        
        function onPlayerReady(event) {
            // Player is ready
        }
        
        function onPlayerStateChange(event) {
            // Handle player state changes
        }
        
        // Fullscreen functionality
        document.getElementById('fullscreenBtn').addEventListener('click', () => {
            const playerElement = document.querySelector('.trailer-container iframe');
            
            if (!playerElement) return;
            
            if (playerElement.requestFullscreen) {
                playerElement.requestFullscreen();
            } else if (playerElement.webkitRequestFullscreen) {
                playerElement.webkitRequestFullscreen();
            } else if (playerElement.msRequestFullscreen) {
                playerElement.msRequestFullscreen();
            }
        });
        
        // Volume control
        document.getElementById('volumeBtn').addEventListener('click', () => {
            if (!player) return;
            
            isMuted = !isMuted;
            const volumeBtn = document.getElementById('volumeBtn');
            
            if (isMuted) {
                player.mute();
                volumeBtn.innerHTML = '<i class="fas fa-volume-mute"></i> Suara';
            } else {
                player.unMute();
                volumeBtn.innerHTML = '<i class="fas fa-volume-up"></i> Volume';
            }
        });
        
        // Tutup modal
        closeModalButtons.forEach(button => {
            button.addEventListener('click', () => {
                closeTrailerModal();
            });
        });
        
        // Tutup modal saat klik di luar area modal
        window.addEventListener('click', (e) => {
            if (e.target === trailerModal) {
                closeTrailerModal();
            }
        });
        
        // Fungsi untuk menutup modal trailer
        function closeTrailerModal() {
            trailerModal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Hentikan video saat modal ditutup
            if (player && typeof player.stopVideo === 'function') {
                player.stopVideo();
            }
            
            // Hapus player
            const trailerContainer = document.getElementById('trailerContainer');
            trailerContainer.innerHTML = '';
        }

        // Responsive navigation
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                nav.classList.remove('active');
            }
        });
    </script>
</body>
</html>