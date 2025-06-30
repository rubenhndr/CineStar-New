<?php
require '../config.php';

header('Content-Type: application/json');

$schedule_id = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : 0;

if ($schedule_id <= 0) {
    echo json_encode(['error' => 'Invalid schedule ID']);
    exit();
}

// Ambil kursi yang sudah dipesan untuk jadwal ini
$query = "SELECT seat_number FROM booked_seats WHERE schedule_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$schedule_id]);
$bookedSeats = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode([
    'bookedSeats' => $bookedSeats
]);
?>