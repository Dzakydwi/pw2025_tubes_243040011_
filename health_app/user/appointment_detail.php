<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$appointment_id = $_GET['id'] ?? null;
$appointment = null;

if (!$appointment_id) {
    header('Location: my_appointments.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM appointments WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $appointment = $result->fetch_assoc();
} else {
    header('Location: my_appointments.php');
    exit();
}
$stmt->close();
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="card shadow-lg p-4">
        <h1 class="card-title text-center mb-4">Detail Janji Temu</h1>

        <?php if ($appointment) : ?>
            <div class="row mb-3">
                <div class="col-md-4 text-md-end"><strong>Dokter:</strong></div>
                <div class="col-md-8"><?= htmlspecialchars($appointment['dokter']) ?></div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4 text-md-end"><strong>Tanggal:</strong></div>
                <div class="col-md-8"><?= htmlspecialchars(date('d F Y', strtotime($appointment['tanggal']))) ?></div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4 text-md-end"><strong>Waktu:</strong></div>
                <div class="col-md-8"><?= htmlspecialchars(date('H:i', strtotime($appointment['waktu']))) ?></div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4 text-md-end"><strong>Keterangan:</strong></div>
                <div class="col-md-8"><?= htmlspecialchars($appointment['keterangan'] ?: '-') ?></div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4 text-md-end"><strong>Dibuat Pada:</strong></div>
                <div class="col-md-8"><?= htmlspecialchars(date('d F Y H:i', strtotime($appointment['created_at']))) ?></div>
            </div>

            <div class="mt-4 text-center">
                <a href="edit_appointment.php?id=<?= $appointment['id'] ?>" class="btn btn-warning me-2">
                    <i class="fas fa-edit me-2"></i> Edit Janji Temu
                </a>
                <a href="my_appointments.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar
                </a>
            </div>
        <?php else : ?>
            <div class="alert alert-warning text-center" role="alert">
                Janji temu tidak ditemukan.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<?php $conn->close(); ?>