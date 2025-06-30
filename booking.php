<?php
session_start();

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

// Ambil data film
$film_id = $_GET['film_id'] ?? 0;
$stmt = $conn->prepare("SELECT id, title, genre, duration, poster_url, trailer_url, synopsis FROM films WHERE id = :id");
$stmt->bindParam(':id', $film_id);
$stmt->execute();
$film = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil jadwal tayang
$jadwal = [];
if ($film_id) {
    $stmt = $conn->prepare("SELECT s.*, st.nama as studio_nama 
                           FROM schedules s 
                           JOIN studios st ON s.studio_id = st.id 
                           WHERE s.film_id = :film_id 
                           ORDER BY s.show_time");
    $stmt->bindParam(':film_id', $film_id);
    $stmt->execute();
    $jadwal = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Ambil kursi yang sudah dipesan
$booked_seats = [];
$schedule_id = $_GET['schedule_id'] ?? 0;
if ($schedule_id) {
    $stmt = $conn->prepare("SELECT seat_number FROM booked_seats WHERE schedule_id = :schedule_id");
    $stmt->bindParam(':schedule_id', $schedule_id);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($bookings as $booking) {
        $booked_seats[] = $booking['seat_number'];
    }
}

// Proses pemesanan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['schedule_id'])) {
    $schedule_id = $_POST['schedule_id'];
    $nama = htmlspecialchars($_POST['nama']);
    $email = htmlspecialchars($_POST['email']);
    $no_hp = htmlspecialchars($_POST['no_hp']);
    $seats = isset($_POST['kursi']) ? explode(',', $_POST['kursi'][0]) : [];
    
    if (empty($seats)) {
        $error = "Silakan pilih kursi terlebih dahulu";
    } else {
        try {
            $conn->beginTransaction();
            
            // Simpan data pemesan
            $stmt = $conn->prepare("INSERT INTO pemesan (nama, email, no_hp) VALUES (:nama, :email, :no_hp)");
            $stmt->bindParam(':nama', $nama);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':no_hp', $no_hp);
            $stmt->execute();
            $pemesan_id = $conn->lastInsertId();
            
            // Simpan booking
            $total_harga = count($seats) * 50000;
            $stmt = $conn->prepare("INSERT INTO bookings (film_id, schedule_id, pemesan_id, jumlah_tiket, total_harga, status) 
                                  VALUES (:film_id, :schedule_id, :pemesan_id, :jumlah_tiket, :total_harga, 'pending')");
            $stmt->bindParam(':film_id', $film_id);
            $stmt->bindParam(':schedule_id', $schedule_id);
            $stmt->bindParam(':pemesan_id', $pemesan_id);
            $stmt->bindValue(':jumlah_tiket', count($seats));
            $stmt->bindParam(':total_harga', $total_harga);
            $stmt->execute();
            $booking_id = $conn->lastInsertId();
            
            // Simpan kursi yang dipesan
            foreach ($seats as $seat) {
                $stmt = $conn->prepare("INSERT INTO booked_seats (booking_id, schedule_id, seat_number) 
                                      VALUES (:booking_id, :schedule_id, :seat_number)");
                $stmt->bindParam(':booking_id', $booking_id);
                $stmt->bindParam(':schedule_id', $schedule_id);
                $stmt->bindParam(':seat_number', $seat);
                $stmt->execute();
            }
            
            // Simpan data pemesanan sementara di session
            $_SESSION['temp_booking'] = [
                'booking_id' => $booking_id,
                'film_id' => $film_id,
                'film_title' => $film['title'],
                'schedule_id' => $schedule_id,
                'show_time' => $jadwal[array_search($schedule_id, array_column($jadwal, 'id'))]['show_time'],
                'seats' => $seats,
                'customer_name' => $nama,
                'customer_email' => $email,
                'customer_phone' => $no_hp,
                'total_price' => $total_harga,
                'studio_nama' => $jadwal[array_search($schedule_id, array_column($jadwal, 'id'))]['studio_nama']
            ];
            
            $conn->commit();
            
            // Redirect ke halaman pembayaran
            header("Location: pembayaran.php");
            exit;
        } catch(PDOException $e) {
            $conn->rollBack();
            $error = "Terjadi kesalahan saat memproses pemesanan: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Tiket - CineStar</title>
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
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
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
            padding-top: 90px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 5%;
        }
        
        /* Hero Section */
        .booking-hero {
            background: linear-gradient(rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.9)), 
                        url('https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80');
            background-size: cover;
            background-position: center;
            padding: 5rem 0;
            text-align: center;
            color: white;
            margin-bottom: 3rem;
            border-radius: 0 0 20px 20px;
        }
        
        .booking-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .booking-hero p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 700px;
            margin: 0 auto;
        }
        
        /* Breadcrumb */
        .breadcrumb {
            padding: 1rem 0;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .breadcrumb span {
            color: var(--gray);
            margin: 0 8px;
            font-size: 0.7rem;
        }
        
        /* Alert */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .alert-danger {
            color: #721C24;
            background-color: #F8D7DA;
            border: 1px solid #F5C6CB;
        }
        
        /* Booking Container */
        .booking-container {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        @media (max-width: 992px) {
            .booking-container {
                grid-template-columns: 1fr;
            }
        }
        
        /* Film Info */
        .film-info {
            background-color: white;
            border-radius: 12px;
            padding: 1.8rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 100px;
            height: fit-content;
        }
        
        .film-poster {
            width: 100%;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .film-title {
            font-size: 1.5rem;
            margin-bottom: 0.8rem;
            color: var(--dark);
            line-height: 1.3;
        }
        
        .film-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.2rem;
            color: var(--gray);
            font-size: 0.85rem;
        }
        
        .film-meta span {
            display: flex;
            align-items: center;
        }
        
        .film-meta i {
            margin-right: 6px;
            color: var(--primary);
            font-size: 0.8rem;
        }
        
        .film-genre {
            display: inline-block;
            background-color: var(--light);
            color: var(--primary);
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-bottom: 1.2rem;
        }
        
        .film-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1.2rem;
        }
        
        .film-synopsis {
            font-size: 0.9rem;
            line-height: 1.6;
            color: var(--gray);
        }
        
        /* Button Styles */
        .btn-sm {
            padding: 0.6rem 1.2rem;
            font-size: 0.9rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 3px 10px rgba(255, 46, 99, 0.2);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 46, 99, 0.3);
        }
        
        .btn-secondary {
            background-color: var(--secondary);
            color: white;
            box-shadow: 0 3px 10px rgba(8, 217, 214, 0.2);
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(8, 217, 214, 0.3);
        }
        
        /* Booking Form */
        .booking-form {
            background-color: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }
        
        .section-title {
            font-size: 1.3rem;
            margin-bottom: 1.2rem;
            color: var(--dark);
            position: relative;
            padding-bottom: 8px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }
        
        .form-group {
            margin-bottom: 1.2rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 500;
            color: var(--dark);
            font-size: 0.9rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background-color: var(--light-bg);
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 46, 99, 0.1);
            background-color: white;
        }
        
        /* Jadwal Selection */
        .select-jadwal {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 0.8rem;
            margin-bottom: 1.5rem;
        }
        
        .jadwal-option {
            padding: 0.9rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            background-color: var(--light-bg);
        }
        
        .jadwal-option:hover {
            border-color: var(--primary);
            background-color: white;
        }
        
        .jadwal-option.active {
            border-color: var(--primary);
            background-color: rgba(255, 46, 99, 0.05);
            box-shadow: 0 0 0 1px var(--primary);
        }
        
        .jadwal-option input {
            display: none;
        }
        
        .jadwal-time {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 3px;
            font-size: 0.95rem;
        }
        
        .jadwal-date {
            font-size: 0.8rem;
            color: var(--gray);
        }
        
        .jadwal-studio {
            font-size: 0.75rem;
            color: var(--primary);
            margin-top: 3px;
            font-weight: 500;
        }
        
        /* Seat Selection */
        .seat-selection {
            margin: 1.8rem 0;
        }
        
        .screen {
            background: linear-gradient(to right, #1e293b, #475569, #1e293b);
            color: white;
            text-align: center;
            padding: 0.8rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.9rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .seats-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            margin-bottom: 1.5rem;
        }
        
        .seat-row {
            display: flex;
            gap: 8px;
        }
        
        .seat {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.7rem;
            transition: all 0.2s ease;
        }
        
        .seat.available {
            background-color: #f0f0f0;
            color: var(--dark);
        }
        
        .seat.available:hover {
            background-color: #e0e0e0;
            transform: scale(1.05);
        }
        
        .seat.selected {
            background-color: var(--primary);
            color: white;
            transform: scale(1.05);
            box-shadow: 0 2px 5px rgba(255, 46, 99, 0.3);
        }
        
        .seat.booked {
            background-color: #cbd5e1;
            color: #64748b;
            cursor: not-allowed;
        }
        
        .seat.space {
            visibility: hidden;
        }
        
        .seat-legend {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1.2rem;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.75rem;
        }
        
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 3px;
        }
        
        /* Booking Summary */
        .booking-summary {
            background-color: var(--light-bg);
            border-radius: 8px;
            padding: 1.2rem;
            margin-top: 1.5rem;
            border: 1px solid #e2e8f0;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
            font-size: 0.9rem;
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
            font-size: 1rem;
            border-top: 1px solid #e2e8f0;
            padding-top: 0.8rem;
            margin-top: 0.8rem;
            color: var(--primary);
        }
        
        /* Button */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 0.8rem 1.8rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(255, 46, 99, 0.2);
            border: none;
            cursor: pointer;
            width: 100%;
            font-size: 0.95rem;
            margin-top: 1rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 46, 99, 0.3);
        }
        
        .btn:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        /* Not Found */
        .not-found {
            text-align: center;
            padding: 4rem 0;
        }
        
        .not-found i {
            font-size: 3rem;
            color: var(--gray);
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }
        
        .not-found h2 {
            font-size: 1.5rem;
            margin-bottom: 0.8rem;
            color: var(--dark);
        }
        
        .not-found p {
            color: var(--gray);
            max-width: 400px;
            margin: 0 auto 1.5rem;
            font-size: 0.9rem;
        }
        
        /* Footer */
        footer {
            background-color: var(--dark-bg);
            color: white;
            padding: 3rem 5% 1.5rem;
            margin-top: 3rem;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }
        
        .footer-col h3 {
            font-size: 1.2rem;
            margin-bottom: 1.2rem;
            position: relative;
            padding-bottom: 8px;
        }
        
        .footer-col h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
        }
        
        .footer-col p {
            margin-bottom: 1.2rem;
            opacity: 0.8;
            font-size: 0.85rem;
            line-height: 1.6;
        }
        
        .social-links {
            display: flex;
            gap: 0.8rem;
        }
        
        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .social-links a:hover {
            background-color: var(--primary);
            transform: translateY(-2px);
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 0.8rem;
        }
        
        .footer-links a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            font-size: 0.85rem;
        }
        
        .footer-links a:hover {
            opacity: 1;
            color: var(--primary);
            padding-left: 5px;
        }
        
        .footer-links a i {
            margin-right: 6px;
            font-size: 0.7rem;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            opacity: 0.7;
            font-size: 0.8rem;
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
            
            .main {
                padding-top: 80px;
            }
            
            .select-jadwal {
                grid-template-columns: 1fr;
            }
            
            .seat {
                width: 28px;
                height: 28px;
            }
            
            .film-info {
                position: static;
            }
            
            .booking-hero h1 {
                font-size: 2rem;
            }
            
            .booking-hero p {
                font-size: 1rem;
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
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    <?php else: ?>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <main class="main">
        <section class="booking-hero">
            <div class="container">
                <h1>Pesan Tiket Bioskop Online</h1>
                <p>Pilih kursi favorit Anda dan nikmati pengalaman menonton yang tak terlupakan</p>
            </div>
        </section>
        
        <div class="container">
            <div class="breadcrumb">
                <a href="index.php">Beranda</a>
                <span>/</span>
                <a href="movies.php">Film</a>
                <span>/</span>
                <a href="#">Pesan Tiket</a>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($film): ?>
            <div class="booking-container">
                <div class="film-info">
                    <img src="<?php echo htmlspecialchars($film['poster_url']); ?>" alt="<?php echo htmlspecialchars($film['title']); ?>" class="film-poster">
                    <h2 class="film-title"><?php echo htmlspecialchars($film['title']); ?></h2>
                    <div class="film-meta">
                        <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($film['duration']); ?> menit</span>
                        <span><i class="fas fa-film"></i> <?php echo htmlspecialchars($film['genre']); ?></span>
                    </div>
                    <div class="film-price">Rp 50,000</div>
                    <p class="film-synopsis"><?php echo htmlspecialchars($film['synopsis']); ?></p>
                    
                    <?php if (!empty($film['trailer_url'])): ?>
                    <button class="btn-sm btn-secondary watch-trailer-btn" 
                            data-trailer-url="<?php echo htmlspecialchars($film['trailer_url']); ?>"
                            data-film-title="<?php echo htmlspecialchars($film['title']); ?>"
                            data-film-synopsis="<?php echo htmlspecialchars($film['synopsis'] ?? 'Nikmati trailer resmi film ini.'); ?>">
                        <i class="fas fa-play"></i> Tonton Trailer
                    </button>
                    <?php endif; ?>
                    
                    
                </div>
                
                <div class="booking-form" id="booking-form">
                    <form action="booking.php?film_id=<?php echo $film_id; ?>" method="POST">
                        <h3 class="section-title">Pilih Jadwal Tayang</h3>
                        
                        <?php if (!empty($jadwal)): ?>
                        <div class="select-jadwal">
                            <?php foreach ($jadwal as $j): ?>
                            <label class="jadwal-option <?php echo isset($_GET['schedule_id']) && $_GET['schedule_id'] == $j['id'] ? 'active' : ''; ?>">
                                <input type="radio" name="schedule_id" value="<?php echo $j['id']; ?>" 
                                    <?php echo isset($_GET['schedule_id']) && $_GET['schedule_id'] == $j['id'] ? 'checked' : ''; ?>
                                    onchange="window.location.href='booking.php?film_id=<?php echo $film_id; ?>&schedule_id=<?php echo $j['id']; ?>'">
                                <div class="jadwal-time"><?php echo date('H:i', strtotime($j['show_time'])); ?></div>
                                <div class="jadwal-date"><?php echo date('d M Y', strtotime($j['show_time'])); ?></div>
                                <div class="jadwal-studio">Studio <?php echo htmlspecialchars($j['studio_nama']); ?></div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                            <p style="color: var(--gray); font-size: 0.9rem;">Tidak ada jadwal tayang tersedia untuk film ini.</p>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['schedule_id'])): ?>
                        <div class="seat-selection">
                            <h3 class="section-title">Pilih Kursi</h3>
                            <div class="screen">L A Y A R</div>
                            
                            <div class="seats-container">
                                <?php 
                                // Generate seat layout (5 rows, 10 seats per row)
                                $rows = range('A', 'E');
                                foreach ($rows as $row) {
                                    echo '<div class="seat-row">';
                                    for ($i = 1; $i <= 10; $i++) {
                                        $seat_number = $row . $i;
                                        $is_booked = in_array($seat_number, $booked_seats);
                                        $class = $is_booked ? 'booked' : 'available';
                                        echo '<div class="seat ' . $class . '" data-seat="' . $seat_number . '">' . $seat_number . '</div>';
                                        if ($i == 5) {
                                            echo '<div class="seat space"></div>';
                                        }
                                    }
                                    echo '</div>';
                                }
                                ?>
                            </div>
                            
                            <div class="seat-legend">
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #f0f0f0;"></div>
                                    <span>Tersedia</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: var(--primary);"></div>
                                    <span>Dipilih</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #cbd5e1;"></div>
                                    <span>Tidak Tersedia</span>
                                </div>
                            </div>
                            
                            <input type="hidden" name="kursi[]" id="selected-seats">
                        </div>
                        
                        <div class="form-group">
                            <h3 class="section-title">Data Pemesan</h3>
                        </div>
                        
                        <div class="form-group">
                            <label for="nama">Nama Lengkap</label>
                            <input type="text" id="nama" name="nama" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="no_hp">Nomor HP</label>
                            <input type="tel" id="no_hp" name="no_hp" class="form-control" required>
                        </div>
                        
                        <div class="booking-summary">
                            <h4>Ringkasan Pemesanan</h4>
                            <div class="summary-item">
                                <span>Film:</span>
                                <span><?php echo htmlspecialchars($film['title']); ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Tanggal:</span>
                                <span><?php echo date('d M Y H:i', strtotime($jadwal[0]['show_time'])); ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Studio:</span>
                                <span><?php echo htmlspecialchars($jadwal[0]['studio_nama'] ?? '-'); ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Kursi:</span>
                                <span id="summary-seats">Belum dipilih</span>
                            </div>
                            <div class="summary-item summary-total">
                                <span>Total:</span>
                                <span id="total-harga">Rp 0</span>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn" id="submit-btn" disabled>
                            <i class="fas fa-ticket-alt"></i> Pesan Tiket Sekarang
                        </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <?php else: ?>
                <div class="not-found">
                    <i class="fas fa-film"></i>
                    <h2>Film tidak ditemukan</h2>
                    <p>Film yang Anda cari tidak tersedia atau tidak ditemukan.</p>
                    <a href="movies.php" class="btn" style="display: inline-flex; width: auto; margin-top: 1rem;">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Film
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
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
    
    <footer>
        <div class="footer-container">
            <div class="footer-col">
                <h3>Tentang CineStar</h3>
                <p>Platform pemesanan tiket bioskop online terkemuka yang memberikan pengalaman menonton terbaik.</p>
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
                    <li><a href="movies.php"><i class="fas fa-chevron-right"></i> Film</a></li>
                    <li><a href="booking.php"><i class="fas fa-chevron-right"></i> Pesan Tiket</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Kontak Kami</h3>
                <ul class="footer-links">
                    <li><a href="#"><i class="fas fa-map-marker-alt"></i> Jl. Bioskop No. 123, Jakarta</a></li>
                    <li><a href="tel:+622112345678"><i class="fas fa-phone-alt"></i> (021) 1234-5678</a></li>
                    <li><a href="mailto:info@cinestar.com"><i class="fas fa-envelope"></i> info@cinestar.com</a></li>
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
        });
        
        // Seat Selection
        const seats = document.querySelectorAll('.seat.available');
        const selectedSeatsInput = document.getElementById('selected-seats');
        const summarySeats = document.getElementById('summary-seats');
        const totalHarga = document.getElementById('total-harga');
        const submitBtn = document.getElementById('submit-btn');
        const hargaPerTiket = 50000;
        
        let selectedSeats = [];
        
        seats.forEach(seat => {
            seat.addEventListener('click', () => {
                const seatNumber = seat.getAttribute('data-seat');
                
                if (seat.classList.contains('selected')) {
                    // Remove seat from selection
                    seat.classList.remove('selected');
                    selectedSeats = selectedSeats.filter(s => s !== seatNumber);
                } else {
                    // Add seat to selection
                    seat.classList.add('selected');
                    selectedSeats.push(seatNumber);
                }
                
                // Update hidden input
                selectedSeatsInput.value = selectedSeats.join(',');
                
                // Update summary
                updateSummary();
            });
        });
        
        function updateSummary() {
            if (selectedSeats.length > 0) {
                summarySeats.textContent = selectedSeats.join(', ');
                const total = selectedSeats.length * hargaPerTiket;
                totalHarga.textContent = 'Rp ' + total.toLocaleString('id-ID');
                submitBtn.disabled = false;
            } else {
                summarySeats.textContent = 'Belum dipilih';
                totalHarga.textContent = 'Rp 0';
                submitBtn.disabled = true;
            }
        }
        
        // Initialize summary
        updateSummary();
        
        // Close modal when pressing Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                trailerModal.style.display = 'none';
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