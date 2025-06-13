<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id) {
    // Jika tidak ada ID, redirect kembali
    header('Location: my_appointments.php');
    exit();
}

// Hapus janji temu jika ID valid dan milik user yang sedang login
$stmt = $conn->prepare("DELETE FROM appointments WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $appointment_id, $user_id);

if ($stmt->execute()) {
    // Redirect dengan pesan sukses (opsional)
    $_SESSION['message'] = "Janji temu berhasil dihapus!";
    $_SESSION['message_type'] = "success";
} else {
    // Redirect dengan pesan error (opsional)
    $_SESSION['message'] = "Gagal menghapus janji temu: " . $stmt->error;
    $_SESSION['message_type'] = "danger";
}

$stmt->close();
$conn->close();

header('Location: my_appointments.php');
exit();
?>