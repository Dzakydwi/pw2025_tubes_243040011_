<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$patient_id = $_GET['id'] ?? null;

if (!$patient_id) {
    $_SESSION['message'] = "ID Pasien tidak ditemukan.";
    $_SESSION['message_type'] = "danger";
    header('Location: patients.php');
    exit();
}

// Ambil nama file foto profil sebelum dihapus
$stmt_get_pic = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt_get_pic->bind_param("i", $patient_id);
$stmt_get_pic->execute();
$result_get_pic = $stmt_get_pic->get_result();
$profile_picture_to_delete = null;
if ($result_get_pic->num_rows > 0) {
    $row = $result_get_pic->fetch_assoc();
    $profile_picture_to_delete = $row['profile_picture'];
}
$stmt_get_pic->close();


// Hapus pasien (dan janji temu terkait karena ON DELETE CASCADE)
$stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'"); // Pastikan hanya menghapus user
$stmt->bind_param("i", $patient_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // Hapus file foto profil jika ada dan bukan default
        if ($profile_picture_to_delete && $profile_picture_to_delete !== 'default_profile.png') {
            $filePath = '../assets/img/' . $profile_picture_to_delete;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        $_SESSION['message'] = "Pasien dan semua janji temu terkait berhasil dihapus!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Pasien tidak ditemukan atau bukan role 'user'.";
        $_SESSION['message_type'] = "warning";
    }
} else {
    $_SESSION['message'] = "Gagal menghapus pasien: " . $stmt->error;
    $_SESSION['message_type'] = "danger";
}

$stmt->close();
$conn->close();

header('Location: patients.php');
exit();
?>