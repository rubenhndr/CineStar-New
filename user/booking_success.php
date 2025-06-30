<?php
session_start();

if (!isset($_SESSION['payment_success'])) {
    header("Location: index.php");
    exit();
}


require '../config.php';

// Ambil data booking terakhir
$user_id = $_SESSION['user_id'];
$booking_query = "SELECT b.*, f.title as film_title, f.poster_url, s.show_time, st.nama as studio_name 
                  FROM bookings b
                  JOIN films f ON b.film_id = f.id
                  JOIN schedules s ON b.schedule_id = s.id
                  JOIN studios st ON s.studio_id = st.id
                  WHERE b.pemesan_id = ? AND b.status = 'paid'
                  ORDER BY b.created_at DESC LIMIT 1";
$booking_stmt = $conn->prepare($booking_query);
$booking_stmt->execute([$user_id]);
$booking = $booking_stmt->fetch();

if (!$booking) {
    header("Location: index.php");
    exit();
}

// Ambil kursi yang dipesan
$seats_query = "SELECT seat_number FROM booked_seats WHERE booking_id = ?";
$seats_stmt = $conn->prepare($seats_query);
$seats_stmt->execute([$booking['id']]);
$seats = $seats_stmt->fetchAll(PDO::FETCH_COLUMN);

// Hapus session booking_id
unset($_SESSION['payment_success']);
unset($_SESSION['paid_booking_id']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil - <?php echo htmlspecialchars($booking['film_title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Tambahkan CSS untuk halaman sukses */
        .success-container {
            max-width: 800px;
            margin: 100px auto 50px;
            padding: 2rem;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            background-color: #08D9D6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: white;
            font-size: 3rem;
        }
        
        .success-title {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .success-subtitle {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2rem;
        }
        
        .ticket-card {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 2rem;
            margin: 2rem 0;
            text-align: left;
        }
        
        .ticket-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .ticket-poster {
            width: 100px;
            height: 150px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 2rem;
        }
        
        .ticket-info h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .ticket-info p {
            color: #666;
        }
        
        .ticket-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .ticket-item {
            margin-bottom: 1rem;
        }
        
        .ticket-label {
            font-weight: 500;
            color: #666;
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }
        
        .ticket-value {
            font-size: 1.1rem;
        }
        
        .ticket-qr {
            text-align: center;
            margin: 2rem 0;
            padding: 1rem;
            background-color: white;
            border-radius: 10px;
            display: inline-block;
        }
        
        .ticket-qr img {
            width: 200px;
            height: 200px;
            margin-bottom: 1rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, #FF2E63, #E82154);
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
        
        .btn-outline {
            background: transparent;
            border: 2px solid #FF2E63;
            color: #FF2E63;
            box-shadow: none;
            margin-right: 1rem;
        }
        
        .btn-outline:hover {
            background: #FF2E63;
            color: white;
        }
        
        @media (max-width: 768px) {
            .ticket-header {
                flex-direction: column;
                text-align: center;
            }
            
            .ticket-poster {
                margin-right: 0;
                margin-bottom: 1.5rem;
            }
            
            .ticket-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        
        <h1 class="success-title">Pembayaran Berhasil!</h1>
        <p class="success-subtitle">Terima kasih telah memesan tiket di CineStar. Berikut detail pesanan Anda:</p>
        
        <div class="ticket-card">
            <div class="ticket-header">
                <img src="<?php echo htmlspecialchars($booking['poster_url']); ?>" alt="<?php echo htmlspecialchars($booking['film_title']); ?>" class="ticket-poster">
                <div class="ticket-info">
                    <h3><?php echo htmlspecialchars($booking['film_title']); ?></h3>
                    <p><?php echo date('d M Y H:i', strtotime($booking['show_time'])); ?> • Studio <?php echo htmlspecialchars($booking['studio_name']); ?></p>
                </div>
            </div>
            
            <div class="ticket-details">
                <div>
                    <div class="ticket-item">
                        <div class="ticket-label">Kode Booking</div>
                        <div class="ticket-value">CST<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></div>
                    </div>
                    
                    <div class="ticket-item">
                        <div class="ticket-label">Nama Pemesan</div>
                        <div class="ticket-value"><?php echo htmlspecialchars($booking['nama']); ?></div>
                    </div>
                    
                    <div class="ticket-item">
                        <div class="ticket-label">Email</div>
                        <div class="ticket-value"><?php echo htmlspecialchars($booking['email']); ?></div>
                    </div>
                </div>
                
                <div>
                    <div class="ticket-item">
                        <div class="ticket-label">Kursi</div>
                        <div class="ticket-value"><?php echo htmlspecialchars(implode(', ', $seats)); ?></div>
                    </div>
                    
                    <div class="ticket-item">
                        <div class="ticket-label">Jumlah Tiket</div>
                        <div class="ticket-value"><?php echo $booking['jumlah_tiket']; ?></div>
                    </div>
                    
                    <div class="ticket-item">
                        <div class="ticket-label">Total Harga</div>
                        <div class="ticket-value">Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="ticket-qr">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=CST<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?>" alt="QR Code">
                <p>Scan QR Code ini saat masuk bioskop</p>
            </div>
        </div>
        
        <div style="margin-top: 2rem;">
            <a href="history.php" class="btn btn-outline">
                <i class="fas fa-history"></i> Lihat Riwayat
            </a>
            <a href="index.php" class="btn">
                <i class="fas fa-home"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
    
    <?php include '../footer.php'; ?>
</body>
</html>