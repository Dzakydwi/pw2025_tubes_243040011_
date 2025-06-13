<?php
include '../config/database.php';
include 'includes/header.php';

$appointment_id = $_GET['id'] ?? null;
$error = '';
$success = '';
$appointment = null;
$users = [];

if (!$appointment_id) {
    header('Location: appointments.php');
    exit();
}

// Ambil daftar user untuk dropdown
$result_users = $conn->query("SELECT id, nama FROM users WHERE role = 'user' ORDER BY nama ASC");
while ($row = $result_users->fetch_assoc()) {
    $users[] = $row;
}
$result_users->free();

// Ambil data janji temu yang akan diedit
$stmt = $conn->prepare("SELECT * FROM appointments WHERE id = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $appointment = $result->fetch_assoc();
} else {
    $_SESSION['message'] = "Janji temu tidak ditemukan.";
    $_SESSION['message_type'] = "danger";
    header('Location: appointments.php');
    exit();
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    $dokter = $_POST['dokter'] ?? '';
    $tanggal = $_POST['tanggal'] ?? '';
    $waktu = $_POST['waktu'] ?? '';
    $keterangan = $_POST['keterangan'] ?? '';

    if (empty($user_id) || empty($dokter) || empty($tanggal) || empty($waktu)) {
        $error = "Semua field wajib diisi.";
    } elseif (strtotime($tanggal) < strtotime(date('Y-m-d'))) {
        $error = "Tanggal janji temu tidak bisa di masa lalu.";
    } else {
        $stmt_update = $conn->prepare("UPDATE appointments SET user_id = ?, dokter = ?, tanggal = ?, waktu = ?, keterangan = ? WHERE id = ?");
        $stmt_update->bind_param("issssi", $user_id, $dokter, $tanggal, $waktu, $keterangan, $appointment_id);

        if ($stmt_update->execute()) {
            $success = "Janji temu berhasil diperbarui!";
            // Update data appointment yang ditampilkan setelah berhasil disimpan
            $appointment['user_id'] = $user_id;
            $appointment['dokter'] = $dokter;
            $appointment['tanggal'] = $tanggal;
            $appointment['waktu'] = $waktu;
            $appointment['keterangan'] = $keterangan;
        } else {
            $error = "Terjadi kesalahan: " . $stmt_update->error;
        }
        $stmt_update->close();
    }
}
?>

<div class="container">
    <div class="card shadow-lg p-4">
        <h1 class="card-title text-center mb-4">Edit Janji Temu (Admin)</h1>

        <?php if ($success) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($error) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($appointment) : ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="user_id" class="form-label">Pilih Pasien <span class="text-danger">*</span></label>
                    <select class="form-select" id="user_id" name="user_id" required>
                        <option value="">-- Pilih Pasien --</option>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?= $user['id'] ?>" <?= ($appointment['user_id'] == $user['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="dokter" class="form-label">Nama Dokter <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="dokter" name="dokter" required value="<?= htmlspecialchars($appointment['dokter']) ?>">
                </div>
                <div class="mb-3">
                    <label for="tanggal" class="form-label">Tanggal Janji Temu <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="tanggal" name="tanggal" required value="<?= htmlspecialchars($appointment['tanggal']) ?>" min="<?= date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label for="waktu" class="form-label">Waktu Janji Temu <span class="text-danger">*</span></label>
                    <input type="time" class="form-control" id="waktu" name="waktu" required value="<?= htmlspecialchars($appointment['waktu']) ?>">
                </div>
                <div class="mb-3">
                    <label for="keterangan" class="form-label">Keterangan (Opsional)</label>
                    <textarea class="form-control" id="keterangan" name="keterangan" rows="3"><?= htmlspecialchars($appointment['keterangan']) ?></textarea>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Simpan Perubahan
                    </button>
                    <a href="appointments.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Janji Temu
                    </a>
                </div>
            </form>
        <?php else : ?>
            <div class="alert alert-warning text-center" role="alert">
                Janji temu tidak ditemukan.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>