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

// Query untuk mendapatkan semua film
$query = "SELECT * FROM films ORDER BY release_date DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$films = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query untuk mendapatkan film yang sedang tayang (untuk filter)
$query_playing = "SELECT * FROM films WHERE is_playing = 1";
$stmt_playing = $conn->prepare($query_playing);
$stmt_playing->execute();
$playing_films = $stmt_playing->fetchAll(PDO::FETCH_ASSOC);

// Query untuk mendapatkan film yang akan datang (untuk filter)
$query_upcoming = "SELECT * FROM films WHERE is_playing = 0 AND release_date > CURDATE()";
$stmt_upcoming = $conn->prepare($query_upcoming);
$stmt_upcoming->execute();
$upcoming_films = $stmt_upcoming->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Film - CineStar</title>
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
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
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
            position: relative;
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
            padding-top: 120px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 5%;
        }
        
        /* Page Header */
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }
        
        .page-header h1 {
            font-size: 2.8rem;
            color: var(--dark);
            margin-bottom: 1rem;
        }
        
        .page-header p {
            color: var(--gray);
            max-width: 700px;
            margin: 0 auto;
        }
        
        .page-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }
        
        /* Filter Section */
        .filter-section {
            display: flex;
            justify-content: center;
            margin-bottom: 3rem;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 0.7rem 1.8rem;
            border-radius: 50px;
            background-color: white;
            color: var(--dark);
            border: 1px solid #ddd;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            color: white;
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(255, 46, 99, 0.3);
        }
        
        /* Movies Grid */
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2.5rem;
            margin-bottom: 5rem;
        }
        
        .movie-card {
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            position: relative;
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
        
        .movie-status {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            z-index: 2;
        }
        
        .status-playing {
            background-color: rgba(8, 217, 214, 0.9);
            color: white;
        }
        
        .status-upcoming {
            background-color: rgba(255, 184, 0, 0.9);
            color: white;
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
            padding: 0.8rem 1.8rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 46, 99, 0.3);
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 46, 99, 0.4);
        }
        
        .btn i {
            margin-left: 8px;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            box-shadow: none;
        }
        
        .btn-outline:hover {
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            color: white;
        }
        
        /* No Results */
        .no-results {
            text-align: center;
            grid-column: 1 / -1;
            padding: 5rem 0;
        }
        
        .no-results i {
            font-size: 5rem;
            color: var(--gray);
            margin-bottom: 2rem;
            opacity: 0.5;
        }
        
        .no-results h3 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: var(--dark);
        }
        
        .no-results p {
            color: var(--gray);
            max-width: 500px;
            margin: 0 auto 2rem;
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
        
        .footer-col h3 {
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 10px;
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
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
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
            color: var(--primary);
            padding-left: 5px;
        }
        
        .footer-links a i {
            margin-right: 8px;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 3rem;
            margin-top: 3rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            opacity: 0.7;
            font-size: 0.9rem;
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
            
            .page-header h1 {
                font-size: 2.2rem;
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
                    <li><a href="movies.php" class="active"><i class="fas fa-ticket-alt"></i> Film</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <main class="main">
        <div class="container">
            <div class="page-header">
                <h1>Daftar Film</h1>
                <p>Temukan semua film terbaru yang sedang tayang dan akan datang di bioskop kami</p>
            </div>
            
            <div class="filter-section">
                <button class="filter-btn active" data-filter="all">Semua Film</button>
                <button class="filter-btn" data-filter="playing">Sedang Tayang</button>
                <button class="filter-btn" data-filter="upcoming">Akan Datang</button>
            </div>
            
            <div class="movies-grid" id="movies-container">
                <?php foreach ($films as $film): ?>
                <div class="movie-card" 
                     data-status="<?php echo (strtotime($film['release_date']) <= time() && $film['is_playing'] == 1) ? 'playing' : 'upcoming' ?>"
                     data-genre="<?php echo htmlspecialchars(strtolower($film['genre'])); ?>">
                    <div class="movie-poster-container">
                        <img src="<?php echo htmlspecialchars($film['poster_url']); ?>" alt="<?php echo htmlspecialchars($film['title']); ?>" class="movie-poster">
                        
                        <?php if (strtotime($film['release_date']) <= time() && $film['is_playing'] == 1): ?>
                            <span class="movie-status status-playing">SEDANG TAYANG</span>
                        <?php elseif (strtotime($film['release_date']) > time()): ?>
                            <span class="movie-status status-upcoming">AKAN DATANG</span>
                        <?php endif; ?>
                        
                        <div class="movie-rating">
                            <i class="fas fa-star"></i>
                            <?php echo number_format($film['rating'] ?? 4.5, 1); ?>
                        </div>
                    </div>
                    <div class="movie-info">
                        <h3 class="movie-title"><?php echo htmlspecialchars($film['title']); ?></h3>
                        <div class="movie-meta">
                            <span><i class="far fa-clock"></i> <?php echo htmlspecialchars($film['duration'] ?? '120'); ?> min</span>
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($film['release_date'] ?? '2023-01-01')); ?></span>
                        </div>
                        <span class="movie-genre"><?php echo htmlspecialchars($film['genre']); ?></span>
                        
                        <?php if (strtotime($film['release_date']) <= time() && $film['is_playing'] == 1): ?>
                            <a href="booking.php?film_id=<?php echo $film['id']; ?>" class="btn">Pesan Tiket <i class="fas fa-ticket-alt"></i></a>
                        <?php else: ?>
                            <button class="btn btn-outline">Segera Hadir</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($films)): ?>
                <div class="no-results">
                    <i class="fas fa-film"></i>
                    <h3>Tidak Ada Film yang Tersedia</h3>
                    <p>Maaf, saat ini tidak ada film yang sedang tayang atau akan datang. Silakan cek kembali nanti.</p>
                    <a href="index.php" class="btn">Kembali ke Beranda</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <footer>
        <div class="footer-container">
            <div class="footer-col">
                <h3>Tentang CineStar</h3>
                <p>CineStar adalah platform pemesanan tiket bioskop online terkemuka yang memberikan pengalaman menonton terbaik.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h3>Link Cepat</h3>
                <ul class="footer-links">
                    <li><a href="index.php"><i class="fas fa-chevron-right"></i> Beranda</a></li>
                    <li><a href="movies.php"><i class="fas fa-chevron-right"></i> Film Terbaru</a></li>
                    <li><a href="promo.php"><i class="fas fa-chevron-right"></i> Promo</a></li>
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
        
        // Filter Functionality
        const filterBtns = document.querySelectorAll('.filter-btn');
        const movieCards = document.querySelectorAll('.movie-card');
        
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Remove active class from all buttons
                filterBtns.forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                btn.classList.add('active');
                
                const filter = btn.dataset.filter;
                
                movieCards.forEach(card => {
                    if (filter === 'all') {
                        card.style.display = 'block';
                    } else {
                        if (card.dataset.status === filter) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    }
                });
                
                // Check if no results after filtering
                const visibleCards = document.querySelectorAll('.movie-card[style="display: block"]');
                const noResults = document.querySelector('.no-results');
                
                if (visibleCards.length === 0 && !noResults) {
                    const moviesContainer = document.getElementById('movies-container');
                    const noResultsDiv = document.createElement('div');
                    noResultsDiv.className = 'no-results';
                    noResultsDiv.innerHTML = `
                        <i class="fas fa-film"></i>
                        <h3>Tidak Ada Film yang Ditemukan</h3>
                        <p>Maaf, tidak ada film yang sesuai dengan filter yang dipilih.</p>
                        <button class="btn filter-btn active" data-filter="all">Tampilkan Semua Film</button>
                    `;
                    moviesContainer.appendChild(noResultsDiv);
                    
                    // Add event listener to the new button
                    noResultsDiv.querySelector('.filter-btn').addEventListener('click', () => {
                        filterBtns.forEach(btn => btn.classList.remove('active'));
                        document.querySelector('.filter-btn[data-filter="all"]').classList.add('active');
                        movieCards.forEach(card => card.style.display = 'block');
                        noResultsDiv.remove();
                    });
                } else if (visibleCards.length > 0 && noResults) {
                    noResults.remove();
                }
            });
        });
    </script>
</body>
</html>