<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Redirect if not logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Database configuration
$host = 'localhost';
$dbname = 'bioskop_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle film addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_film'])) {
    try {
        $stmt = $conn->prepare("INSERT INTO films (title, genre, duration, release_date, poster_url, trailer_url, synopsis, is_playing) 
                              VALUES (:title, :genre, :duration, :release_date, :poster_url, :trailer_url, :synopsis, :is_playing)");
        
        $stmt->execute([
            ':title' => htmlspecialchars($_POST['title']),
            ':genre' => htmlspecialchars($_POST['genre']),
            ':duration' => (int)$_POST['duration'],
            ':release_date' => $_POST['release_date'],
            ':poster_url' => filter_var($_POST['poster_url'], FILTER_VALIDATE_URL),
            ':trailer_url' => filter_var($_POST['trailer_url'], FILTER_VALIDATE_URL),
            ':synopsis' => htmlspecialchars($_POST['synopsis']),
            ':is_playing' => isset($_POST['is_playing']) ? 1 : 0
        ]);
        
        $_SESSION['success_message'] = "Film berhasil ditambahkan!";
        header('Location: admin_dashboard.php');
        exit;
    } catch(PDOException $e) {
        $_SESSION['error_message'] = "Gagal menambahkan film: " . $e->getMessage();
    }
}

// Handle schedule addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_schedule'])) {
    try {
        $stmt = $conn->prepare("INSERT INTO schedules (film_id, studio_id, show_time) 
                              VALUES (:film_id, :studio_id, :show_time)");
        
        $stmt->execute([
            ':film_id' => (int)$_POST['film_id'],
            ':studio_id' => (int)$_POST['studio_id'],
            ':show_time' => $_POST['show_date'] . ' ' . $_POST['show_time']
        ]);
        
        $_SESSION['success_message'] = "Jadwal berhasil ditambahkan!";
        header('Location: admin_dashboard.php');
        exit;
    } catch(PDOException $e) {
        $_SESSION['error_message'] = "Gagal menambahkan jadwal: " . $e->getMessage();
    }
}

// Handle film deletion
if (isset($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM films WHERE id = :id");
        $stmt->execute([':id' => (int)$_GET['delete']]);
        
        $_SESSION['success_message'] = "Film berhasil dihapus!";
        header('Location: admin_dashboard.php');
        exit;
    } catch(PDOException $e) {
        $_SESSION['error_message'] = "Gagal menghapus film: " . $e->getMessage();
    }
}

// Handle schedule deletion
if (isset($_GET['delete_schedule'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM schedules WHERE id = :id");
        $stmt->execute([':id' => (int)$_GET['delete_schedule']]);
        
        $_SESSION['success_message'] = "Jadwal berhasil dihapus!";
        header('Location: admin_dashboard.php');
        exit;
    } catch(PDOException $e) {
        $_SESSION['error_message'] = "Gagal menghapus jadwal: " . $e->getMessage();
    }
}

// Fetch all films ordered by release date (newest first)
try {
    $stmt = $conn->query("SELECT *, 
                         CASE 
                             WHEN is_playing = 1 THEN 'Sedang Tayang' 
                             ELSE 'Akan Datang' 
                         END as status_text 
                         FROM films 
                         ORDER BY release_date DESC, created_at DESC");
    $films = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Error fetching films: " . $e->getMessage());
}

// Fetch all schedules with film and studio info
try {
    $stmt = $conn->query("SELECT s.*, f.title as film_title, st.nama as studio_name 
                         FROM schedules s
                         JOIN films f ON s.film_id = f.id
                         JOIN studios st ON s.studio_id = st.id
                         ORDER BY s.show_time DESC");
    $schedules = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Error fetching schedules: " . $e->getMessage());
}

// Fetch all studios
try {
    $stmt = $conn->query("SELECT * FROM studios ORDER BY nama");
    $studios = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Error fetching studios: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bioskop</title>
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
            --success-bg: #d4edda;
            --success-text: #155724;
            --error-bg: #f8d7da;
            --error-text: #721c24;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f8fafc;
            color: var(--dark);
            line-height: 1.6;
        }
        
        /* Header Styles */
        .admin-header {
            background-color: var(--dark-bg);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .logo i {
            font-size: 1.5rem;
        }
        
        .logout-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s;
        }
        
        .logout-btn:hover {
            background-color: var(--primary-dark);
        }
        
        /* Container Styles */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        
        /* Notification messages */
        .notification {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .notification::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 5px;
            height: 100%;
        }
        
        .success {
            background-color: var(--success-bg);
            color: var(--success-text);
        }
        
        .success::before {
            background-color: var(--success-text);
        }
        
        .error {
            background-color: var(--error-bg);
            color: var(--error-text);
        }
        
        .error::before {
            background-color: var(--error-text);
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: inherit;
            margin-left: 1rem;
        }
        
        /* Title and Button Styles */
        .admin-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .admin-title span {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: var(--secondary);
        }
        
        .btn-secondary:hover {
            background-color: #07c7c4;
        }
        
        /* Form Styles */
        .add-form {
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .add-form h2 {
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--dark);
        }
        
        .form-row {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            flex: 1;
            margin-bottom: 1rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }
        
        input[type="text"],
        input[type="number"],
        input[type="date"],
        input[type="time"],
        input[type="url"],
        select,
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--secondary);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 1rem;
        }
        
        .checkbox-group input {
            margin-right: 0.5rem;
        }
        
        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .data-table th {
            background-color: #f8fafc;
            font-weight: 600;
            color: var(--dark);
        }
        
        .data-table tr:hover {
            background-color: #f8fafc;
        }
        
        .film-poster {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .playing {
            background-color: #d4edda;
            color: #155724;
        }
        
        .upcoming {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .action-btns {
            display: flex;
            gap: 0.5rem;
        }
        
        .edit-btn, .delete-btn, .view-trailer-btn {
            padding: 0.5rem 0.8rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .edit-btn {
            background-color: #e2f0fd;
            color: #1a73e8;
        }
        
        .edit-btn:hover {
            background-color: #d0e3fb;
        }
        
        .delete-btn {
            background-color: #fde8e8;
            color: #d32f2f;
        }
        
        .delete-btn:hover {
            background-color: #fbd5d5;
        }
        
        .view-trailer-btn {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .view-trailer-btn:hover {
            background-color: #c8e6c9;
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
        
        /* No Results Styles */
        .no-results {
            text-align: center;
            padding: 3rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        }
        
        .no-results i {
            color: #cbd5e0;
            margin-bottom: 1rem;
        }
        
        .no-results h3 {
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .no-results p {
            color: var(--gray);
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .tab {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab.active {
            border-bottom-color: var(--primary);
            color: var(--primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .admin-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .action-btns {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .data-table th,
            .data-table td {
                padding: 0.75rem;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
            
            .tabs {
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="logo">
            <i class="fas fa-film"></i>
            <span>Admin Bioskop</span>
        </div>
        <form action="logout.php" method="post">
            <button type="submit" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </form>
    </header>
    
    <div class="container">
        <!-- Display success/error messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="notification success">
                <span><?php echo htmlspecialchars($_SESSION['success_message']); ?></span>
                <button class="close-btn">&times;</button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="notification error">
                <span><?php echo htmlspecialchars($_SESSION['error_message']); ?></span>
                <button class="close-btn">&times;</button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" data-tab="films">Film</div>
            <div class="tab" data-tab="schedules">Jadwal Tayang</div>
        </div>
        
        <!-- Films Tab -->
        <div id="films" class="tab-content active">
            <h1 class="admin-title">
                <span><i class="fas fa-film"></i> Daftar Film</span>
                <button id="toggleFilmFormBtn" class="btn">
                    <i class="fas fa-plus"></i> Tambah Film
                </button>
            </h1>
            
            <div class="add-form" id="addFilmForm" style="display: none;">
                <h2><i class="fas fa-plus-circle"></i> Tambah Film Baru</h2>
                <form action="admin_dashboard.php" method="POST" onsubmit="return validateFilmForm()">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">Judul Film *</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="genre">Genre *</label>
                            <input type="text" id="genre" name="genre" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="duration">Durasi (menit) *</label>
                            <input type="number" id="duration" name="duration" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="release_date">Tanggal Rilis *</label>
                            <input type="date" id="release_date" name="release_date" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="poster_url">URL Poster *</label>
                        <input type="url" id="poster_url" name="poster_url" placeholder="https://example.com/poster.jpg" required>
                        <small id="poster-preview-text" style="display:none;">Preview:</small>
                        <img id="poster-preview" src="" alt="Poster Preview" style="max-width:200px; display:none; margin-top:10px;">
                    </div>
                    
                    <div class="form-group">
                        <label for="trailer_url">URL Trailer (YouTube) *</label>
                        <input type="url" id="trailer_url" name="trailer_url" placeholder="https://www.youtube.com/watch?v=..." required>
                        <small id="trailer-preview-text" style="display:none;">Preview:</small>
                        <div id="trailer-preview" style="display:none; margin-top:10px;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="synopsis">Sinopsis *</label>
                        <textarea id="synopsis" name="synopsis" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_playing" name="is_playing">
                        <label for="is_playing">Sedang Tayang</label>
                    </div>
                    
                    <button type="submit" name="add_film" class="btn">
                        <i class="fas fa-save"></i> Simpan Film
                    </button>
                </form>
            </div>
            
            <?php if (empty($films)): ?>
                <div class="no-results">
                    <i class="fas fa-film fa-3x"></i>
                    <h3>Tidak Ada Film</h3>
                    <p>Belum ada film yang ditambahkan.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Poster</th>
                                <th>Judul</th>
                                <th>Genre</th>
                                <th>Durasi</th>
                                <th>Tanggal Rilis</th>
                                <th>Status</th>
                                <th>Trailer</th>
                                <th>Ditambahkan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($films as $film): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($film['poster_url']); ?>" 
                                         alt="Poster <?php echo htmlspecialchars($film['title']); ?>" 
                                         class="film-poster"
                                         onerror="this.src='https://via.placeholder.com/60x80?text=No+Poster'">
                                </td>
                                <td><?php echo htmlspecialchars($film['title']); ?></td>
                                <td><?php echo htmlspecialchars($film['genre']); ?></td>
                                <td><?php echo htmlspecialchars($film['duration']); ?> min</td>
                                <td><?php echo date('d M Y', strtotime($film['release_date'])); ?></td>
                                <td>
                                    <?php if ($film['is_playing'] == 1): ?>
                                        <span class="badge playing">Sedang Tayang</span>
                                    <?php else: ?>
                                        <span class="badge upcoming">Akan Datang</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($film['trailer_url'])): ?>
                                        <button class="view-trailer-btn" data-trailer-url="<?php echo htmlspecialchars($film['trailer_url']); ?>">
                                            <i class="fas fa-play"></i> Lihat
                                        </button>
                                    <?php else: ?>
                                        <span class="badge">Tidak Ada</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d M Y', strtotime($film['created_at'])); ?></td>
                                <td class="action-btns">
                                    <a href="edit_film.php?id=<?php echo $film['id']; ?>" class="edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="admin_dashboard.php?delete=<?php echo $film['id']; ?>" class="delete-btn" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus film ini?')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Schedules Tab -->
        <div id="schedules" class="tab-content">
            <h1 class="admin-title">
                <span><i class="fas fa-calendar-alt"></i> Jadwal Tayang</span>
                <button id="toggleScheduleFormBtn" class="btn btn-secondary">
                    <i class="fas fa-plus"></i> Tambah Jadwal
                </button>
            </h1>
            
            <div class="add-form" id="addScheduleForm" style="display: none;">
                <h2><i class="fas fa-plus-circle"></i> Tambah Jadwal Baru</h2>
                <form action="admin_dashboard.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="film_id">Film *</label>
                            <select id="film_id" name="film_id" required>
                                <option value="">-- Pilih Film --</option>
                                <?php foreach ($films as $film): ?>
                                    <option value="<?php echo $film['id']; ?>">
                                        <?php echo htmlspecialchars($film['title']); ?> (<?php echo date('Y', strtotime($film['release_date'])); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="studio_id">Studio *</label>
                            <select id="studio_id" name="studio_id" required>
                                <option value="">-- Pilih Studio --</option>
                                <?php foreach ($studios as $studio): ?>
                                    <option value="<?php echo $studio['id']; ?>">
                                        <?php echo htmlspecialchars($studio['nama']); ?> (Kapasitas: <?php echo $studio['kapasitas']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="show_date">Tanggal *</label>
                            <input type="date" id="show_date" name="show_date" required>
                        </div>
                        <div class="form-group">
                            <label for="show_time">Waktu *</label>
                            <input type="time" id="show_time" name="show_time" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_schedule" class="btn btn-secondary">
                        <i class="fas fa-save"></i> Simpan Jadwal
                    </button>
                </form>
            </div>
            
            <?php if (empty($schedules)): ?>
                <div class="no-results">
                    <i class="fas fa-calendar-times fa-3x"></i>
                    <h3>Tidak Ada Jadwal</h3>
                    <p>Belum ada jadwal tayang yang ditambahkan.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Film</th>
                                <th>Studio</th>
                                <th>Waktu Tayang</th>
                                <th>Ditambahkan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($schedule['film_title']); ?></td>
                                <td><?php echo htmlspecialchars($schedule['studio_name']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($schedule['show_time'])); ?></td>
                                <td><?php echo date('d M Y', strtotime($schedule['created_at'])); ?></td>
                                <td class="action-btns">
                                    <a href="edit_schedule.php?id=<?php echo $schedule['id']; ?>" class="edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="admin_dashboard.php?delete_schedule=<?php echo $schedule['id']; ?>" class="delete-btn" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Trailer Modal -->
    <div id="trailerModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2 id="modal-title">Trailer Film</h2>
            <div class="video-container" id="video-container"></div>
        </div>
    </div>

    <script>
        // Tab functionality
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs and contents
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                this.classList.add('active');
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });

        // Toggle film form visibility
        document.getElementById('toggleFilmFormBtn').addEventListener('click', function() {
            const form = document.getElementById('addFilmForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            this.innerHTML = form.style.display === 'none' 
                ? '<i class="fas fa-plus"></i> Tambah Film' 
                : '<i class="fas fa-minus"></i> Sembunyikan Form';
        });

        // Toggle schedule form visibility
        document.getElementById('toggleScheduleFormBtn').addEventListener('click', function() {
            const form = document.getElementById('addScheduleForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            this.innerHTML = form.style.display === 'none' 
                ? '<i class="fas fa-plus"></i> Tambah Jadwal' 
                : '<i class="fas fa-minus"></i> Sembunyikan Form';
        });

        // Poster URL preview
        document.getElementById('poster_url').addEventListener('input', function() {
            const preview = document.getElementById('poster-preview');
            const previewText = document.getElementById('poster-preview-text');
            
            if (this.value) {
                preview.src = this.value;
                preview.style.display = 'block';
                previewText.style.display = 'block';
            } else {
                preview.style.display = 'none';
                previewText.style.display = 'none';
            }
        });

        // Trailer URL preview
        document.getElementById('trailer_url').addEventListener('input', function() {
            const preview = document.getElementById('trailer-preview');
            const previewText = document.getElementById('trailer-preview-text');
            
            if (this.value) {
                // Check if it's a YouTube URL
                const youtubeId = getYouTubeId(this.value);
                if (youtubeId) {
                    preview.innerHTML = `<iframe width="200" height="113" src="https://www.youtube.com/embed/${youtubeId}" frameborder="0" allowfullscreen></iframe>`;
                    preview.style.display = 'block';
                    previewText.style.display = 'block';
                } else {
                    preview.innerHTML = '<p>URL YouTube tidak valid</p>';
                    preview.style.display = 'block';
                    previewText.style.display = 'block';
                }
            } else {
                preview.style.display = 'none';
                previewText.style.display = 'none';
            }
        });

        // Close notification
        document.querySelectorAll('.close-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.target.closest('.notification').style.display = 'none';
            });
        });

        // Form validation
        function validateFilmForm() {
            const posterUrl = document.getElementById('poster_url').value;
            const trailerUrl = document.getElementById('trailer_url').value;
            
            if (!isValidUrl(posterUrl)) {
                alert('URL poster tidak valid');
                return false;
            }
            
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
        
        function isValidUrl(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        }
        
        function getYouTubeId(url) {
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
            const match = url.match(regExp);
            return (match && match[2].length === 11) ? match[2] : null;
        }

        // Trailer Modal functionality
        const modal = document.getElementById('trailerModal');
        const modalTitle = document.getElementById('modal-title');
        const videoContainer = document.getElementById('video-container');
        const closeModal = document.querySelector('.close-modal');
        
        document.querySelectorAll('.view-trailer-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const trailerUrl = this.getAttribute('data-trailer-url');
                const youtubeId = getYouTubeId(trailerUrl);
                const filmTitle = this.closest('tr').querySelector('td:nth-child(2)').textContent;
                
                if (youtubeId) {
                    modalTitle.textContent = `Trailer: ${filmTitle}`;
                    videoContainer.innerHTML = `<iframe src="https://www.youtube.com/embed/${youtubeId}?autoplay=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
                    modal.style.display = 'block';
                }
            });
        });
        
        closeModal.addEventListener('click', function() {
            modal.style.display = 'none';
            videoContainer.innerHTML = '';
        });
        
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
                videoContainer.innerHTML = '';
            }
        });
    </script>
</body>
</html>