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
    echo "Koneksi gagal: " . $e->getMessage();
    exit();
}

// Proses pembayaran jika data ada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['booking_data'])) {
    $booking_data = $_SESSION['booking_data'];
    $payment_method = $_POST['payment_method'] ?? '';
    $booking_id = $_POST['booking_id'] ?? uniqid();
    $status = 'pending'; // bisa diganti menjadi 'success' setelah pembayaran berhasil
    
    // Simpan ke database
    try {
        // Simpan data booking
        $query = "INSERT INTO bookings (booking_id, film_id, customer_name, customer_email, customer_phone, showtime, seats, total_price, payment_method, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            $booking_id,
            $booking_data['film_id'],
            $booking_data['nama'],
            $booking_data['email'],
            $booking_data['phone'],
            $booking_data['showtime'],
            $booking_data['seats'],
            $booking_data['total_harga'],
            $payment_method,
            $status
        ]);
        
        // Dapatkan data film untuk halaman konfirmasi
        $film_query = "SELECT * FROM films WHERE id = ?";
        $film_stmt = $conn->prepare($film_query);
        $film_stmt->execute([$booking_data['film_id']]);
        $film_data = $film_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Tampilkan halaman konfirmasi
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Pembayaran Berhasil | CineStar</title>
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
                }
                
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                    font-family: 'Poppins', sans-serif;
                }
                
                body {
                    background-color: #f8f9fa;
                    color: var(--dark);
                    line-height: 1.6;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    padding: 2rem;
                }
                
                .confirmation-container {
                    background-color: white;
                    border-radius: 15px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                    padding: 3rem;
                    max-width: 600px;
                    width: 100%;
                    text-align: center;
                }
                
                .confirmation-icon {
                    font-size: 5rem;
                    color: #4CAF50;
                    margin-bottom: 1.5rem;
                }
                
                .confirmation-title {
                    font-size: 2rem;
                    margin-bottom: 1rem;
                    color: var(--primary);
                }
                
                .confirmation-message {
                    color: var(--gray);
                    margin-bottom: 2rem;
                }
                
                .booking-details {
                    background-color: #f8f9fa;
                    border-radius: 10px;
                    padding: 1.5rem;
                    margin-bottom: 2rem;
                    text-align: left;
                }
                
                .detail-item {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 0.8rem;
                }
                
                .detail-item:last-child {
                    margin-bottom: 0;
                }
                
                .detail-label {
                    font-weight: 500;
                    color: var(--gray);
                }
                
                .detail-value {
                    font-weight: 600;
                }
                
                .total-price {
                    font-size: 1.2rem;
                    font-weight: 700;
                    color: var(--primary);
                    margin-top: 1rem;
                    padding-top: 1rem;
                    border-top: 1px solid #eee;
                    display: flex;
                    justify-content: space-between;
                }
                
                .btn {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    background: linear-gradient(45deg, var(--primary), var(--primary-dark));
                    color: white;
                    padding: 1rem 2rem;
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
                
                .btn i {
                    margin-left: 8px;
                }
            </style>
        </head>
        <body>
            <div class="confirmation-container">
                <div class="confirmation-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="confirmation-title">Pembayaran Berhasil!</h1>
                <p class="confirmation-message">Terima kasih telah memesan tiket di CineStar. Detail pemesanan telah dikirim ke email Anda.</p>
                
                <div class="booking-details">
                    <div class="detail-item">
                        <span class="detail-label">Kode Booking:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking_id); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Film:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($film_data['title']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Jadwal:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking_data['showtime']); ?> WIB</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Kursi:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking_data['seats']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Metode Pembayaran:</span>
                        <span class="detail-value">
                            <?php 
                            $methods = [
                                'bank_transfer' => 'Transfer Bank',
                                'gopay' => 'Gopay',
                                'ovo' => 'OVO',
                                'credit_card' => 'Kartu Kredit'
                            ];
                            echo $methods[$payment_method] ?? $payment_method;
                            ?>
                        </span>
                    </div>
                    <div class="total-price">
                        <span>Total Pembayaran:</span>
                        <span>Rp<?php echo number_format($booking_data['total_harga'], 0, ',', '.'); ?></span>
                    </div>
                </div>
                
                <a href="index.php" class="btn">
                    Kembali ke Beranda <i class="fas fa-home"></i>
                </a>
            </div>
        </body>
        </html>
        <?php
        // Hapus session setelah pembayaran berhasil
        unset($_SESSION['booking_data']);
        exit();
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    // Redirect jika tidak ada data booking
    header('Location: pembayaran.php');
    exit();
}
?>