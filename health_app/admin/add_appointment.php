<?php
include '../config/database.php';
include 'includes/header.php';

$error = '';
$success = '';
$users = [];

// Ambil daftar user untuk dropdown
$result_users = $conn->query("SELECT id, nama FROM users WHERE role = 'user' ORDER BY nama ASC");
while ($row = $result_users->fetch_assoc()) {
    $users[] = $row;
}
$result_users->free();

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
        $stmt = $conn->prepare("INSERT INTO appointments (user_id, dokter, tanggal, waktu, keterangan) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $dokter, $tanggal, $waktu, $keterangan);

        if ($stmt->execute()) {
            $success = "Janji temu berhasil ditambahkan!";
            $_POST = array(); // Clear form fields
        } else {
            $error = "Terjadi kesalahan: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<div class="container">
    <div class="card shadow-lg p-4">
        <h1 class="card-title text-center mb-4">Tambah Janji Temu Baru (Admin)</h1>

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

        <form method="POST" action="">
            <div class="mb-3">
                <label for="user_id" class="form-label">Pilih Pasien <span class="text-danger">*</span></label>
                <select class="form-select" id="user_id" name="user_id" required>
                    <option value="">-- Pilih Pasien --</option>
                    <?php foreach ($users as $user) : ?>
                        <option value="<?= $user['id'] ?>" <?= (($_POST['user_id'] ?? '') == $user['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($users)) : ?>
                    <div class="form-text text-danger">Belum ada pasien terdaftar. Silakan tambahkan pasien terlebih dahulu di <a href="patients.php">halaman Pasien</a>.</div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="dokter" class="form-label">Nama Dokter <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="dokter" name="dokter" required value="<?= htmlspecialchars($_POST['dokter'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="tanggal" class="form-label">Tanggal Janji Temu <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="tanggal" name="tanggal" required value="<?= htmlspecialchars($_POST['tanggal'] ?? date('Y-m-d')) ?>" min="<?= date('Y-m-d') ?>">
            </div>
            <div class="mb-3">
                <label for="waktu" class="form-label">Waktu Janji Temu <span class="text-danger">*</span></label>
                <input type="time" class="form-control" id="waktu" name="waktu" required value="<?= htmlspecialchars($_POST['waktu'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="keterangan" class="form-label">Keterangan (Opsional)</label>
                <textarea class="form-control" id="keterangan" name="keterangan" rows="3"><?= htmlspecialchars($_POST['keterangan'] ?? '') ?></textarea>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary" <?= empty($users) ? 'disabled' : '' ?>>
                    <i class="fas fa-calendar-plus me-2"></i> Tambah Janji Temu
                </button>
                <a href="appointments.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Janji Temu
                </a>
            </div>
        </form>
    </div>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>