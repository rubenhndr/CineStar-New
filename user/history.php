<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require '../config.php';

// Ambil riwayat pemesanan user
$query = "SELECT b.id, b.created_at, b.jumlah_tiket, b.total_harga, b.status,
          f.title as film_title, f.poster_url, f.duration,
          s.show_time, 
          st.nama as studio_name
          FROM bookings b
          JOIN films f ON b.film_id = f.id
          JOIN schedules s ON b.schedule_id = s.id
          JOIN studios st ON s.studio_id = st.id
          WHERE b.pemesan_id = :user_id
          ORDER BY b.created_at DESC";
          
$stmt = $conn->prepare($query);
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$bookings = $stmt->fetchAll();

// Untuk setiap booking, ambil kursi yang dipesan
foreach ($bookings as &$booking) {
    $seats_query = "SELECT seat_number FROM booked_seats WHERE booking_id = ?";
    $seats_stmt = $conn->prepare($seats_query);
    $seats_stmt->execute([$booking['id']]);
    $booking['seats'] = $seats_stmt->fetchAll(PDO::FETCH_COLUMN);
}
unset($booking);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pemesanan - CineStar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Tambahkan CSS untuk halaman riwayat */
        .history-container {
            max-width: 1200px;
            margin: 100px auto 50px;
            padding: 2rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .section-header h1 {
            font-size: 2rem;
            color: #333;
        }
        
        .history-list {
            display: grid;
            gap: 1.5rem;
        }
        
        .history-item {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .history-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .history-header {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .history-poster {
            width: 80px;
            height: 120px;
            border-radius: 5px;
            object-fit: cover;
            margin-right: 1.5rem;
        }
        
        .history-info h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }
        
        .history-info p {
            color: #666;
        }
        
        .history-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }
        
        .detail-item {
            margin-bottom: 0.5rem;
        }
        
        .detail-label {
            font-weight: 500;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        
        .detail-value {
            font-size: 1.1rem;
        }
        
        .status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status.pending {
            background-color: #FFF3CD;
            color: #856404;
        }
        
        .status.paid {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        .status.cancelled {
            background-color: #F8D7DA;
            color: #721C24;
        }
        
        .no-history {
            text-align: center;
            padding: 3rem;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .no-history i {
            font-size: 3rem;
            color: #FF2E63;
            margin-bottom: 1rem;
        }
        
        .no-history h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .no-history p {
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .history-header {
                flex-direction: column;
                text-align: center;
            }
            
            .history-poster {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .history-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    
    <div class="history-container">
        <div class="section-header">
            <h1>Riwayat Pemesanan</h1>
        </div>
        
        <?php if (empty($bookings)): ?>
        <div class="no-history">
            <i class="far fa-calendar-times"></i>
            <h3>Belum Ada Riwayat Pemesanan</h3>
            <p>Anda belum melakukan pemesanan tiket melalui CineStar.</p>
            <a href="index.php" class="btn">
                <i class="fas fa-ticket-alt"></i> Pesan Tiket Sekarang
            </a>
        </div>
        <?php else: ?>
        <div class="history-list">
            <?php foreach ($bookings as $booking): ?>
            <div class="history-item">
                <div class="history-header">
                    <img src="<?php echo htmlspecialchars($booking['poster_url']); ?>" alt="<?php echo htmlspecialchars($booking['film_title']); ?>" class="history-poster">
                    <div class="history-info">
                        <h3><?php echo htmlspecialchars($booking['film_title']); ?></h3>
                        <p><?php echo date('d M Y H:i', strtotime($booking['show_time'])); ?> • Studio <?php echo htmlspecialchars($booking['studio_name']); ?></p>
                    </div>
                </div>
                
                <div class="history-details">
                    <div>
                        <div class="detail-item">
                            <div class="detail-label">Kode Booking</div>
                            <div class="detail-value">CST<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Tanggal Pemesanan</div>
                            <div class="detail-value"><?php echo date('d M Y H:i', strtotime($booking['created_at'])); ?></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="detail-item">
                            <div class="detail-label">Kursi</div>
                            <div class="detail-value"><?php echo htmlspecialchars(implode(', ', $booking['seats'])); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Jumlah Tiket</div>
                            <div class="detail-value"><?php echo $booking['jumlah_tiket']; ?></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="detail-item">
                            <div class="detail-label">Total Harga</div>
                            <div class="detail-value">Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Status</div>
                            <div class="detail-value">
                                <span class="status <?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php include '../footer.php'; ?>
</body>
</html>