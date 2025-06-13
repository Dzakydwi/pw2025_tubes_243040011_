<?php
session_start(); // Tambahkan session_start() di awal file
include '../config/database.php';
include 'includes/header.php';

// Periksa apakah admin sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$error = '';
$success = '';
$users = [];
$doctors = []; // Tambahkan variabel doctors

// Ambil daftar user (pasien) untuk dropdown
$result_users = $conn->query("SELECT id, nama FROM users WHERE role = 'user' ORDER BY nama ASC");
if ($result_users) {
    while ($row = $result_users->fetch_assoc()) {
        $users[] = $row;
    }
    $result_users->free();
} else {
    $error = "Gagal mengambil daftar pasien: " . $conn->error;
}


// Ambil daftar dokter untuk dropdown
$result_doctors = $conn->query("SELECT id, nama FROM users WHERE role = 'doctor' ORDER BY nama ASC");
if ($result_doctors) {
    while ($row = $result_doctors->fetch_assoc()) {
        $doctors[] = $row;
    }
    $result_doctors->free();
} else {
    $error = "Gagal mengambil daftar dokter: " . $conn->error;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    $dokter = $_POST['dokter'] ?? ''; // Ini harusnya id dokter, bukan nama
    $tanggal = $_POST['tanggal'] ?? '';
    $waktu = $_POST['waktu'] ?? '';
    $keterangan = $_POST['keterangan'] ?? '';

    if (empty($user_id) || empty($dokter) || empty($tanggal) || empty($waktu)) {
        $error = "Semua field wajib diisi.";
    } elseif (strtotime($tanggal) < strtotime(date('Y-m-d'))) {
        $error = "Tanggal janji temu tidak bisa di masa lalu.";
    } else {
        // Ambil nama dokter berdasarkan ID yang dipilih (jika menggunakan ID dokter di dropdown)
        $stmt_get_doctor_name = $conn->prepare("SELECT nama FROM users WHERE id = ? AND role = 'doctor'");
        $stmt_get_doctor_name->bind_param("i", $dokter);
        $stmt_get_doctor_name->execute();
        $result_doctor_name = $stmt_get_doctor_name->get_result();
        if ($result_doctor_name->num_rows > 0) {
            $doctor_name_row = $result_doctor_name->fetch_assoc();
            $dokter_nama = $doctor_name_row['nama']; // Gunakan nama dokter yang diambil dari DB
        } else {
            $error = "Dokter tidak valid.";
        }
        $stmt_get_doctor_name->close();

        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO appointments (user_id, dokter, tanggal, waktu, keterangan) VALUES (?, ?, ?, ?, ?)");
            // Gunakan $dokter_nama di sini
            $stmt->bind_param("issss", $user_id, $dokter_nama, $tanggal, $waktu, $keterangan);

            if ($stmt->execute()) {
                $success = "Janji temu berhasil ditambahkan!";
                // Kosongkan field setelah sukses
                $user_id = '';
                $dokter = ''; // Reset dokter terpilih
                $tanggal = '';
                $waktu = '';
                $keterangan = '';
            } else {
                $error = "Gagal menambahkan janji temu: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<div class="container mt-4">
    <div class="card shadow-lg p-4">
        <h2 class="card-title text-center mb-4"><i class="fas fa-calendar-plus me-2"></i> Tambah Janji Temu Baru</h2>

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
                    <option value="">Pilih Pasien</option>
                    <?php foreach ($users as $user) : ?>
                        <option value="<?= htmlspecialchars($user['id']) ?>" <?= (isset($_POST['user_id']) && $_POST['user_id'] == $user['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($users)) : ?>
                    <p class="text-danger mt-2">Belum ada data pasien. Tambahkan pasien terlebih dahulu.</p>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="dokter" class="form-label">Pilih Dokter <span class="text-danger">*</span></label>
                <select class="form-select" id="dokter" name="dokter" required>
                    <option value="">Pilih Dokter</option>
                    <?php foreach ($doctors as $doctor) : ?>
                        <option value="<?= htmlspecialchars($doctor['id']) ?>" <?= (isset($_POST['dokter']) && $_POST['dokter'] == $doctor['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($doctor['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($doctors)) : ?>
                    <p class="text-danger mt-2">Belum ada data dokter. Tambahkan dokter terlebih dahulu.</p>
                <?php endif; ?>
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
                <button type="submit" class="btn btn-primary" <?= empty($users) || empty($doctors) ? 'disabled' : '' ?>>
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
include 'includes/footer.php';
$conn->close();
?>