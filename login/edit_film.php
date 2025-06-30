<?php
session_start();

// Redirect jika belum login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Koneksi ke database
$host = 'localhost';
$dbname = 'bioskop_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// Ambil data film yang akan diedit
$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM films WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$film = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$film) {
    header('Location: admin_dashboard.php');
    exit;
}

// Proses update film
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_film'])) {
    $title = $_POST['title'];
    $genre = $_POST['genre'];
    $duration = $_POST['duration'];
    $release_date = $_POST['release_date'];
    $poster_url = $_POST['poster_url'];
    $trailer_url = $_POST['trailer_url'];
    $synopsis = $_POST['synopsis'];
    $is_playing = isset($_POST['is_playing']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE films SET 
                          title = :title, 
                          genre = :genre, 
                          duration = :duration, 
                          release_date = :release_date, 
                          poster_url = :poster_url, 
                          trailer_url = :trailer_url,
                          synopsis = :synopsis, 
                          is_playing = :is_playing 
                          WHERE id = :id");
    
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':genre', $genre);
    $stmt->bindParam(':duration', $duration);
    $stmt->bindParam(':release_date', $release_date);
    $stmt->bindParam(':poster_url', $poster_url);
    $stmt->bindParam(':trailer_url', $trailer_url);
    $stmt->bindParam(':synopsis', $synopsis);
    $stmt->bindParam(':is_playing', $is_playing);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    header('Location: admin_dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Film - CineStar Admin</title>
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
            background-color: #f5f5f5;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .admin-header {
            background-color: var(--dark-bg);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-header .logo {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: flex;
            align-items: center;
        }
        
        .admin-header .logo i {
            margin-right: 10px;
        }
        
        .logout-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .admin-title {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
            display: flex;
            align-items: center;
        }
        
        .admin-title i {
            margin-right: 10px;
        }
        
        .back-btn {
            display: inline-block;
            margin-bottom: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-btn i {
            margin-right: 5px;
        }
        
        .edit-film-form {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input, 
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        
        .checkbox-group input {
            width: auto;
            margin-right: 10px;
        }
        
        .btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            font-size: 1rem;
        }
        
        .btn:hover {
            background: var(--primary-dark);
        }
        
        .film-poster-preview {
            max-width: 200px;
            height: auto;
            border-radius: 5px;
            margin-bottom: 1rem;
            display: block;
        }
        
        /* Trailer Preview Styles */
        .trailer-preview-container {
            margin-top: 10px;
        }
        
        .trailer-preview-text {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 5px;
            display: none;
        }
        
        .trailer-preview {
            display: none;
            width: 100%;
            max-width: 400px;
            height: 225px;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .trailer-preview iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
        }
        
        .modal-content {
            position: relative;
            margin: 5% auto;
            padding: 20px;
            width: 80%;
            max-width: 800px;
            background-color: white;
            border-radius: 8px;
        }
        
        .close-modal {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }
        
        .close-modal:hover {
            color: #333;
        }
        
        .video-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
            margin-top: 20px;
        }
        
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="logo">
            <i class="fas fa-film"></i>
            <span>CineStar Admin</span>
        </div>
        <form action="logout.php" method="post">
            <button type="submit" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </form>
    </header>
    
    <div class="container">
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
        
        <h1 class="admin-title">
            <i class="fas fa-edit"></i> Edit Film
        </h1>
        
        <div class="edit-film-form">
            <form action="edit_film.php?id=<?php echo $id; ?>" method="POST" onsubmit="return validateFilmForm()">
                <div class="form-group">
                    <label for="poster">Poster Film</label>
                    <img src="<?php echo htmlspecialchars($film['poster_url']); ?>" alt="Poster Film" class="film-poster-preview" id="posterPreview">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Judul Film</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($film['title']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="genre">Genre</label>
                        <input type="text" id="genre" name="genre" value="<?php echo htmlspecialchars($film['genre']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="duration">Durasi (menit)</label>
                        <input type="number" id="duration" name="duration" value="<?php echo htmlspecialchars($film['duration']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="release_date">Tanggal Rilis</label>
                        <input type="date" id="release_date" name="release_date" value="<?php echo htmlspecialchars($film['release_date']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="poster_url">URL Poster</label>
                    <input type="url" id="poster_url" name="poster_url" value="<?php echo htmlspecialchars($film['poster_url']); ?>" required onchange="updatePosterPreview(this.value)">
                </div>
                
                <div class="form-group">
                    <label for="trailer_url">URL Trailer (YouTube)</label>
                    <input type="url" id="trailer_url" name="trailer_url" value="<?php echo htmlspecialchars($film['trailer_url']); ?>" required oninput="updateTrailerPreview(this.value)">
                    <small class="trailer-preview-text" id="trailerPreviewText">Preview Trailer:</small>
                    <div class="trailer-preview" id="trailerPreview"></div>
                    <button type="button" class="btn" style="margin-top: 10px; background: var(--secondary);" onclick="viewTrailerInModal()" id="viewTrailerBtn" style="display: none;">
                        <i class="fas fa-play"></i> Lihat Trailer
                    </button>
                </div>
                
                <div class="form-group">
                    <label for="synopsis">Sinopsis</label>
                    <textarea id="synopsis" name="synopsis" required><?php echo htmlspecialchars($film['synopsis']); ?></textarea>
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="is_playing" name="is_playing" <?php echo $film['is_playing'] == 1 ? 'checked' : ''; ?>>
                    <label for="is_playing">Sedang Tayang</label>
                </div>
                
                <button type="submit" name="update_film" class="btn">
                    <i class="fas fa-save"></i> Update Film
                </button>
            </form>
        </div>
    </div>

    <!-- Trailer Modal -->
    <div id="trailerModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2 id="modal-title">Trailer <?php echo htmlspecialchars($film['title']); ?></h2>
            <div class="video-container" id="video-container"></div>
        </div>
    </div>

    <script>
        // Update poster preview ketika URL poster diubah
        function updatePosterPreview(url) {
            const preview = document.getElementById('posterPreview');
            if (url) {
                preview.src = url;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        }

        // Update trailer preview ketika URL trailer diubah
        function updateTrailerPreview(url) {
            const previewText = document.getElementById('trailerPreviewText');
            const preview = document.getElementById('trailerPreview');
            const viewBtn = document.getElementById('viewTrailerBtn');
            
            if (url) {
                const youtubeId = getYouTubeId(url);
                if (youtubeId) {
                    preview.innerHTML = `<iframe src="https://www.youtube.com/embed/${youtubeId}" frameborder="0" allowfullscreen></iframe>`;
                    preview.style.display = 'block';
                    previewText.style.display = 'block';
                    viewBtn.style.display = 'inline-block';
                } else {
                    preview.innerHTML = '<p>URL YouTube tidak valid</p>';
                    preview.style.display = 'block';
                    previewText.style.display = 'block';
                    viewBtn.style.display = 'none';
                }
            } else {
                preview.style.display = 'none';
                previewText.style.display = 'none';
                viewBtn.style.display = 'none';
            }
        }

        // Validasi form sebelum submit
        function validateFilmForm() {
            const trailerUrl = document.getElementById('trailer_url').value;
            
            if (!isValidUrl(trailerUrl)) {
                alert('URL trailer tidak valid');
                return false;
            }
            
            if (!getYouTubeId(trailerUrl)) {
                alert('URL trailer harus berupa link YouTube yang valid');
                return false;
            }
            
            return true;
        }
        
        // Fungsi untuk mengekstrak ID YouTube dari URL
        function getYouTubeId(url) {
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
            const match = url.match(regExp);
            return (match && match[2].length === 11) ? match[2] : null;
        }
        
        // Fungsi untuk memvalidasi URL
        function isValidUrl(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        }

        // Fungsi untuk menampilkan trailer di modal
        function viewTrailerInModal() {
            const trailerUrl = document.getElementById('trailer_url').value;
            const youtubeId = getYouTubeId(trailerUrl);
            const modal = document.getElementById('trailerModal');
            const videoContainer = document.getElementById('video-container');
            
            if (youtubeId) {
                videoContainer.innerHTML = `<iframe src="https://www.youtube.com/embed/${youtubeId}?autoplay=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
                modal.style.display = 'block';
            }
        }

        // Inisialisasi preview trailer saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            const trailerUrl = document.getElementById('trailer_url').value;
            if (trailerUrl) {
                updateTrailerPreview(trailerUrl);
            }
            
            // Setup modal close button
            const modal = document.getElementById('trailerModal');
            const closeModal = document.querySelector('.close-modal');
            
            closeModal.addEventListener('click', function() {
                modal.style.display = 'none';
                document.getElementById('video-container').innerHTML = '';
            });
            
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                    document.getElementById('video-container').innerHTML = '';
                }
            });
        });
    </script>
</body>
</html>