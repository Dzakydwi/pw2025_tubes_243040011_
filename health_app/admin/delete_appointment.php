<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id) {
    $_SESSION['message'] = "ID Janji Temu tidak ditemukan.";
    $_SESSION['message_type'] = "danger";
    header('Location: appointments.php');
    exit();
}

// Hapus janji temu
$stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
$stmt->bind_param("i", $appointment_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "Janji temu berhasil dihapus!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Janji temu tidak ditemukan.";
        $_SESSION['message_type'] = "warning";
    }
} else {
    $_SESSION['message'] = "Gagal menghapus janji temu: " . $stmt->error;
    $_SESSION['message_type'] = "danger";
}

$stmt->close();
$conn->close();

header('Location: appointments.php');
exit();
?>