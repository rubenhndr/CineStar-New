<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['booking_id'])) {
    header("Location: index.php");
    exit();
}

require '../config.php';

// Ambil data booking
$booking_id = $_SESSION['booking_id'];
$booking_query = "SELECT b.*, f.title as film_title, s.show_time, st.nama as studio_name 
                  FROM bookings b
                  JOIN films f ON b.film_id = f.id
                  JOIN schedules s ON b.schedule_id = s.id
                  JOIN studios st ON s.studio_id = st.id
                  WHERE b.id = ?";
$booking_stmt = $conn->prepare($booking_query);
$booking_stmt->execute([$booking_id]);
$booking = $booking_stmt->fetch();

if (!$booking) {
    header("Location: index.php");
    exit();
}

// Ambil kursi yang dipesan
$seats_query = "SELECT seat_number FROM booked_seats WHERE booking_id = ?";
$seats_stmt = $conn->prepare($seats_query);
$seats_stmt->execute([$booking_id]);
$seats = $seats_stmt->fetchAll(PDO::FETCH_COLUMN);

// Proses pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();
        
        // 1. Update status booking
        $update_query = "UPDATE bookings SET status = 'paid', payment_method = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->execute([$_POST['payment_method'], $booking_id]);
        
        // 2. Simpan data pembayaran (tambahkan tabel payments jika perlu)
        
        // $payment_query = "INSERT INTO payments (...) VALUES (...)";
        // $payment_stmt->execute([...]);
        
        $conn->commit();
        
        // Simpan data di session sebelum redirect
        $_SESSION['payment_success'] = true;
        $_SESSION['paid_booking_id'] = $booking_id;
        
        header("Location: booking_success.php");
        exit();
        
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Pembayaran gagal: " . $e->getMessage();
        header("Location: payment.php");
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - <?php echo htmlspecialchars($booking['film_title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Tambahkan CSS untuk halaman pembayaran */
        .payment-container {
            max-width: 1200px;
            margin: 100px auto 50px;
            padding: 2rem;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
        }
        
        .payment-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .payment-poster {
            width: 150px;
            height: 220px;
            border-radius: 10px;
            object-fit: cover;
            margin-right: 2rem;
        }
        
        .payment-title h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .payment-title p {
            color: #666;
        }
        
        .payment-steps {
            display: flex;
            margin-bottom: 2rem;
        }
        
        .step {
            flex: 1;
            text-align: center;
            padding: 1rem;
            position: relative;
            color: #999;
            font-weight: 500;
        }
        
        .step.completed {
            color: #08D9D6;
        }
        
        .step.active {
            color: #FF2E63;
            font-weight: 600;
        }
        
        .step.completed .step-number {
            background-color: #08D9D6;
            color: white;
        }
        
        .step.active .step-number {
            background-color: #FF2E63;
            color: white;
        }
        
        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #eee;
            margin-bottom: 0.5rem;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 50%;
            right: 0;
            width: 100%;
            height: 2px;
            background-color: #eee;
            z-index: -1;
        }
        
        .payment-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .order-summary {
            background-color: #f9f9f9;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .order-item {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .order-label {
            font-weight: 500;
            color: #666;
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }
        
        .order-value {
            font-size: 1.1rem;
        }
        
        .order-total {
            font-weight: 600;
            font-size: 1.3rem;
            color: #FF2E63;
        }
        
        .payment-methods {
            margin-bottom: 2rem;
        }
        
        .payment-method {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-method:hover {
            border-color: #FF2E63;
        }
        
        .payment-method.selected {
            border-color: #FF2E63;
            background-color: rgba(255, 46, 99, 0.05);
        }
        
        .payment-method input {
            margin-right: 1rem;
        }
        
        .payment-method-icon {
            width: 40px;
            height: 40px;
            margin-right: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f0f0f0;
            border-radius: 5px;
            color: #555;
        }
        
        .payment-method-info h4 {
            margin-bottom: 0.3rem;
        }
        
        .payment-method-info p {
            font-size: 0.8rem;
            color: #666;
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
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        @media (max-width: 768px) {
            .payment-content {
                grid-template-columns: 1fr;
            }
            
            .payment-header {
                flex-direction: column;
                text-align: center;
            }
            
            .payment-poster {
                margin-right: 0;
                margin-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    
    <div class="payment-container">
        <div class="payment-header">
            <img src="<?php echo htmlspecialchars($booking['poster_url']); ?>" alt="<?php echo htmlspecialchars($booking['film_title']); ?>" class="payment-poster">
            <div class="payment-title">
                <h1><?php echo htmlspecialchars($booking['film_title']); ?></h1>
                <p><?php echo date('d M Y H:i', strtotime($booking['show_time'])); ?> • Studio <?php echo htmlspecialchars($booking['studio_name']); ?></p>
            </div>
        </div>
        
        <div class="payment-steps">
            <div class="step completed">
                <div class="step-number"><i class="fas fa-check"></i></div>
                <div>Pilih Kursi</div>
            </div>
            <div class="step active">
                <div class="step-number">2</div>
                <div>Pembayaran</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div>Selesai</div>
            </div>
        </div>
        
        <form action="payment.php" method="POST">
            <div class="payment-content">
                <div>
                    <div class="order-summary">
                        <h2 style="margin-bottom: 1.5rem; color: #333;">Ringkasan Pesanan</h2>
                        
                        <div class="order-item">
                            <div class="order-label">Film</div>
                            <div class="order-value"><?php echo htmlspecialchars($booking['film_title']); ?></div>
                        </div>
                        
                        <div class="order-item">
                            <div class="order-label">Jadwal</div>
                            <div class="order-value">
                                <?php echo date('d M Y H:i', strtotime($booking['show_time'])); ?> • Studio <?php echo htmlspecialchars($booking['studio_name']); ?>
                            </div>
                        </div>
                        
                        <div class="order-item">
                            <div class="order-label">Kursi</div>
                            <div class="order-value"><?php echo htmlspecialchars(implode(', ', $seats)); ?></div>
                        </div>
                        
                        <div class="order-item">
                            <div class="order-label">Jumlah Tiket</div>
                            <div class="order-value"><?php echo $booking['jumlah_tiket']; ?></div>
                        </div>
                        
                        <div class="order-item">
                            <div class="order-label">Total Harga</div>
                            <div class="order-value order-total">Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="payment-methods">
                        <h2 style="margin-bottom: 1.5rem; color: #333;">Metode Pembayaran</h2>
                        
                        <div class="payment-method selected">
                            <input type="radio" name="payment_method" value="virtual_account" checked>
                            <div class="payment-method-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="payment-method-info">
                                <h4>Virtual Account</h4>
                                <p>Transfer melalui ATM/Internet Banking/Mobile Banking</p>
                            </div>
                        </div>
                        
                        <div class="payment-method">
                            <input type="radio" name="payment_method" value="e_wallet">
                            <div class="payment-method-icon">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="payment-method-info">
                                <h4>E-Wallet</h4>
                                <p>Dana, OVO, Gopay, LinkAja</p>
                            </div>
                        </div>
                        
                        <div class="payment-method">
                            <input type="radio" name="payment_method" value="credit_card">
                            <div class="payment-method-icon">
                                <i class="far fa-credit-card"></i>
                            </div>
                            <div class="payment-method-info">
                                <h4>Kartu Kredit</h4>
                                <p>Visa, Mastercard, JCB</p>
                            </div>
                        </div>
                        
                        <div class="payment-method">
                            <input type="radio" name="payment_method" value="qris">
                            <div class="payment-method-icon">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <div class="payment-method-info">
                                <h4>QRIS</h4>
                                <p>Scan QR Code untuk pembayaran</p>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-block">
                        Bayar Sekarang <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <?php include '../footer.php'; ?>
    
    <script>
        // Pilih metode pembayaran
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                document.querySelectorAll('.payment-method').forEach(m => {
                    m.classList.remove('selected');
                });
                this.classList.add('selected');
                this.querySelector('input').checked = true;
            });
        });
    </script>
</body>
</html>