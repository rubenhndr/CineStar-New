<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require '../config.php';

// Ambil data film dari parameter URL
$film_id = isset($_GET['film']) ? (int)$_GET['film'] : 0;

if ($film_id <= 0) {
    header("Location: index.php");
    exit();
}

// Ambil data film
$film_query = "SELECT * FROM films WHERE id = ?";
$film_stmt = $conn->prepare($film_query);
$film_stmt->execute([$film_id]);
$film = $film_stmt->fetch();

if (!$film) {
    header("Location: index.php");
    exit();
}

// Ambil jadwal tayang
$schedules_query = "SELECT s.*, st.nama as studio_name 
                    FROM schedules s 
                    JOIN studios st ON s.studio_id = st.id 
                    WHERE s.film_id = ? AND s.show_time > NOW() 
                    ORDER BY s.show_time";
$schedules_stmt = $conn->prepare($schedules_query);
$schedules_stmt->execute([$film_id]);
$schedules = $schedules_stmt->fetchAll();

// Proses form pemesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = (int)$_POST['schedule_id'];
    $jumlah_tiket = (int)$_POST['jumlah_tiket'];
    $seat_numbers = $_POST['seat_numbers'];
    $nama_pemesan = $_POST['nama_pemesan'];
    $email_pemesan = $_POST['email_pemesan'];
    $no_hp_pemesan = $_POST['no_hp_pemesan'];
    
    // Validasi
    if ($jumlah_tiket <= 0 || empty($seat_numbers) || empty($nama_pemesan)) {
        $_SESSION['error'] = "Harap isi semua field dengan benar";
        header("Location: booking.php?film=" . $film_id);
        exit();
    }
    
    // Hitung total harga (contoh: 50.000 per tiket)
    $harga_tiket = 50000;
    $total_harga = $jumlah_tiket * $harga_tiket;
    
    try {
        $conn->beginTransaction();
        
        // Simpan data pemesan
        $pemesan_query = "INSERT INTO pemesan (nama, email, no_hp) VALUES (?, ?, ?)";
        $pemesan_stmt = $conn->prepare($pemesan_query);
        $pemesan_stmt->execute([$nama_pemesan, $email_pemesan, $no_hp_pemesan]);
        $pemesan_id = $conn->lastInsertId();
        
        // Simpan booking
        $booking_query = "INSERT INTO bookings (film_id, schedule_id, pemesan_id, jumlah_tiket, total_harga, status) 
                          VALUES (?, ?, ?, ?, ?, 'pending')";
        $booking_stmt = $conn->prepare($booking_query);
        $booking_stmt->execute([$film_id, $schedule_id, $pemesan_id, $jumlah_tiket, $total_harga]);
        $booking_id = $conn->lastInsertId();
        
        // Simpan kursi yang dipesan
        $seats = explode(',', $seat_numbers);
        foreach ($seats as $seat) {
            $seat_query = "INSERT INTO booked_seats (booking_id, schedule_id, seat_number) VALUES (?, ?, ?)";
            $seat_stmt = $conn->prepare($seat_query);
            $seat_stmt->execute([$booking_id, $schedule_id, trim($seat)]);
        }
        
        $conn->commit();
        
        // Redirect ke halaman pembayaran
        $_SESSION['booking_id'] = $booking_id;
        header("Location: payment.php");
        exit();
        
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        header("Location: booking.php?film=" . $film_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Tiket - <?php echo htmlspecialchars($film['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Tambahkan CSS untuk halaman booking */
        .booking-container {
            max-width: 1200px;
            margin: 100px auto 50px;
            padding: 2rem;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
        }
        
        .booking-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .booking-poster {
            width: 150px;
            height: 220px;
            border-radius: 10px;
            object-fit: cover;
            margin-right: 2rem;
        }
        
        .booking-title h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .booking-title p {
            color: #666;
        }
        
        .booking-steps {
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
        
        .step.active {
            color: #FF2E63;
            font-weight: 600;
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
        
        .booking-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-section h2 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #FF2E63;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 46, 99, 0.1);
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
        
        .seat-map {
            border: 1px solid #ddd;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .screen {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 0.5rem;
            margin-bottom: 2rem;
            border-radius: 5px;
        }
        
        .seats-grid {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 10px;
            margin-bottom: 1rem;
        }
        
        .seat {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f0f0f0;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .seat.selected {
            background-color: #FF2E63;
            color: white;
        }
        
        .seat.booked {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        .seat-legend {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
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
            border-radius: 3px;
        }
        
        .error-message {
            color: #FF2E63;
            background-color: rgba(255, 46, 99, 0.1);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: none;
        }
        
        .error-message.show {
            display: block;
        }
        
        @media (max-width: 768px) {
            .booking-form {
                grid-template-columns: 1fr;
            }
            
            .booking-header {
                flex-direction: column;
                text-align: center;
            }
            
            .booking-poster {
                margin-right: 0;
                margin-bottom: 1.5rem;
            }
            
            .seats-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    
    <div class="booking-container">
        <div class="booking-header">
            <img src="<?php echo htmlspecialchars($film['poster_url']); ?>" alt="<?php echo htmlspecialchars($film['title']); ?>" class="booking-poster">
            <div class="booking-title">
                <h1><?php echo htmlspecialchars($film['title']); ?></h1>
                <p><?php echo htmlspecialchars($film['genre']); ?> • <?php echo htmlspecialchars($film['duration']); ?> menit</p>
            </div>
        </div>
        
        <div class="booking-steps">
            <div class="step active">
                <div class="step-number">1</div>
                <div>Pilih Kursi</div>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div>Pembayaran</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div>Selesai</div>
            </div>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>
        
        <form action="booking.php?film=<?php echo $film_id; ?>" method="POST" id="bookingForm">
            <div class="booking-form">
                <div>
                    <div class="form-section">
                        <h2>Pilih Jadwal Tayang</h2>
                        <div class="form-group">
                            <label for="schedule_id">Jadwal</label>
                            <select name="schedule_id" id="schedule_id" class="form-control" required>
                                <option value="">-- Pilih Jadwal --</option>
                                <?php foreach ($schedules as $schedule): ?>
                                <option value="<?php echo $schedule['id']; ?>">
                                    <?php echo date('d M Y H:i', strtotime($schedule['show_time'])); ?> - Studio <?php echo htmlspecialchars($schedule['studio_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2>Pilih Kursi</h2>
                        <div class="seat-map">
                            <div class="screen">Layar</div>
                            <div class="seats-grid" id="seatsContainer">
                                <!-- Kursi akan di-load via JavaScript -->
                                <p>Silakan pilih jadwal terlebih dahulu</p>
                            </div>
                            <div class="seat-legend">
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #f0f0f0;"></div>
                                    <span>Tersedia</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #FF2E63;"></div>
                                    <span>Dipilih</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #ccc;"></div>
                                    <span>Tidak Tersedia</span>
                                </div>
                            </div>
                            <input type="hidden" name="seat_numbers" id="seatNumbers" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="jumlah_tiket">Jumlah Tiket</label>
                            <input type="number" name="jumlah_tiket" id="jumlah_tiket" class="form-control" min="1" max="10" required>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="form-section">
                        <h2>Data Pemesan</h2>
                        <div class="form-group">
                            <label for="nama_pemesan">Nama Lengkap</label>
                            <input type="text" name="nama_pemesan" id="nama_pemesan" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="email_pemesan">Email</label>
                            <input type="email" name="email_pemesan" id="email_pemesan" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="no_hp_pemesan">Nomor HP</label>
                            <input type="tel" name="no_hp_pemesan" id="no_hp_pemesan" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2>Ringkasan Pesanan</h2>
                        <div class="form-group">
                            <label>Film</label>
                            <div class="form-control" style="background-color: #f9f9f9;"><?php echo htmlspecialchars($film['title']); ?></div>
                        </div>
                        <div class="form-group">
                            <label>Jadwal</label>
                            <div class="form-control" style="background-color: #f9f9f9;" id="scheduleSummary">-</div>
                        </div>
                        <div class="form-group">
                            <label>Kursi</label>
                            <div class="form-control" style="background-color: #f9f9f9;" id="seatsSummary">-</div>
                        </div>
                        <div class="form-group">
                            <label>Total Harga</label>
                            <div class="form-control" style="background-color: #f9f9f9; font-weight: 600;" id="totalPrice">Rp 0</div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-block">
                        Lanjutkan ke Pembayaran <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <?php include '../footer.php'; ?>
    
    <script>
        // Harga per tiket
        const TICKET_PRICE = 50000;
        
        // Tangkap elemen yang diperlukan
        const scheduleSelect = document.getElementById('schedule_id');
        const seatsContainer = document.getElementById('seatsContainer');
        const seatNumbersInput = document.getElementById('seatNumbers');
        const jumlahTiketInput = document.getElementById('jumlah_tiket');
        const scheduleSummary = document.getElementById('scheduleSummary');
        const seatsSummary = document.getElementById('seatsSummary');
        const totalPrice = document.getElementById('totalPrice');
        const bookingForm = document.getElementById('bookingForm');
        
        // Data kursi yang sudah dipesan
        let bookedSeats = [];
        // Kursi yang dipilih oleh user
        let selectedSeats = [];
        
        // Event listener untuk perubahan jadwal
        scheduleSelect.addEventListener('change', function() {
            const scheduleId = this.value;
            
            if (!scheduleId) {
                seatsContainer.innerHTML = '<p>Silakan pilih jadwal terlebih dahulu</p>';
                scheduleSummary.textContent = '-';
                return;
            }
            
            // Update ringkasan jadwal
            const selectedOption = this.options[this.selectedIndex];
            scheduleSummary.textContent = selectedOption.textContent;
            
            // Load kursi yang tersedia
            loadSeats(scheduleId);
        });
        
        // Fungsi untuk memuat kursi berdasarkan jadwal
        function loadSeats(scheduleId) {
            fetch(`get_seats.php?schedule_id=${scheduleId}`)
                .then(response => response.json())
                .then(data => {
                    bookedSeats = data.bookedSeats;
                    renderSeats();
                })
                .catch(error => {
                    console.error('Error:', error);
                    seatsContainer.innerHTML = '<p>Gagal memuat data kursi. Silakan coba lagi.</p>';
                });
        }
        
        // Fungsi untuk merender kursi
        function renderSeats() {
            seatsContainer.innerHTML = '';
            
            // Buat baris kursi (A1-A10, B1-B10, dst)
            for (let row = 0; row < 5; row++) {
                const rowLetter = String.fromCharCode(65 + row); // A, B, C, D, E
                
                for (let num = 1; num <= 10; num++) {
                    const seatNumber = `${rowLetter}${num}`;
                    const seatElement = document.createElement('div');
                    seatElement.className = 'seat';
                    seatElement.textContent = seatNumber;
                    seatElement.dataset.seat = seatNumber;
                    
                    // Cek apakah kursi sudah dipesan
                    if (bookedSeats.includes(seatNumber)) {
                        seatElement.classList.add('booked');
                    } else {
                        seatElement.addEventListener('click', toggleSeatSelection);
                    }
                    
                    // Cek apakah kursi sudah dipilih
                    if (selectedSeats.includes(seatNumber)) {
                        seatElement.classList.add('selected');
                    }
                    
                    seatsContainer.appendChild(seatElement);
                }
            }
        }
        
        // Fungsi untuk memilih/membatalkan kursi
        function toggleSeatSelection(e) {
            const seat = e.target.dataset.seat;
            const seatIndex = selectedSeats.indexOf(seat);
            
            if (seatIndex === -1) {
                // Tambahkan kursi jika belum dipilih
                if (selectedSeats.length < parseInt(jumlahTiketInput.value)) {
                    selectedSeats.push(seat);
                    e.target.classList.add('selected');
                }
            } else {
                // Hapus kursi jika sudah dipilih
                selectedSeats.splice(seatIndex, 1);
                e.target.classList.remove('selected');
            }
            
            // Update input hidden dan ringkasan
            updateSeatSelection();
        }
        
        // Event listener untuk perubahan jumlah tiket
        jumlahTiketInput.addEventListener('change', function() {
            const maxSeats = parseInt(this.value);
            
            // Jika jumlah kursi yang dipilih melebihi jumlah tiket, hapus yang terakhir
            while (selectedSeats.length > maxSeats) {
                const seatToRemove = selectedSeats.pop();
                const seatElement = document.querySelector(`.seat[data-seat="${seatToRemove}"]`);
                if (seatElement) {
                    seatElement.classList.remove('selected');
                }
            }
            
            updateSeatSelection();
        });
        
        // Fungsi untuk memperbarui input dan ringkasan kursi
        function updateSeatSelection() {
            seatNumbersInput.value = selectedSeats.join(', ');
            seatsSummary.textContent = selectedSeats.length > 0 ? selectedSeats.join(', ') : '-';
            
            // Hitung total harga
            const total = selectedSeats.length * TICKET_PRICE;
            totalPrice.textContent = `Rp ${total.toLocaleString('id-ID')}`;
        }
        
        // Validasi form sebelum submit
        bookingForm.addEventListener('submit', function(e) {
            if (selectedSeats.length !== parseInt(jumlahTiketInput.value)) {
                e.preventDefault();
                alert(`Anda harus memilih tepat ${jumlahTiketInput.value} kursi.`);
            }
        });
    </script>
</body>
</html>