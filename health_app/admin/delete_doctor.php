<?php
session_start();
include '../config/database.php';

// Periksa apakah admin sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$doctor_id = $_GET['id'] ?? null;

if (!$doctor_id) {
    $_SESSION['error_message'] = "ID dokter tidak ditemukan.";
    header('Location: doctors.php');
    exit();
}

// Siapkan dan jalankan query DELETE
$stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'doctor'");
if ($stmt_delete) {
    $stmt_delete->bind_param("i", $doctor_id);
    if ($stmt_delete->execute()) {
        $_SESSION['success_message'] = "Dokter berhasil dihapus!";
    } else {
        $_SESSION['error_message'] = "Gagal menghapus dokter: " . $stmt_delete->error;
    }
    $stmt_delete->close();
} else {
    $_SESSION['error_message'] = "Gagal menyiapkan statement: " . $conn->error;
}

$conn->close();
header('Location: doctors.php');
exit();
?>