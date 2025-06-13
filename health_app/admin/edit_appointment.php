<?php
session_start(); // Tambahkan session_start()
include '../config/database.php';
include 'includes/header.php';

// Periksa apakah admin sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$appointment_id = $_GET['id'] ?? null;
$error = '';
$success = '';
$appointment = null;
$users = [];
$doctors = []; // Tambahkan variabel doctors

if (!$appointment_id) {
    header('Location: appointments.php');
    exit();
}

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
    $dokter = $_POST['dokter'] ?? ''; // Ini akan menjadi ID dokter dari dropdown
    $tanggal = $_POST['tanggal'] ?? '';
    $waktu = $_POST['waktu'] ?? '';
    $keterangan = $_POST['keterangan'] ?? '';

    if (empty($user_id) || empty($dokter) || empty($tanggal) || empty($waktu)) {
        $error = "Semua field wajib diisi.";
    } elseif (strtotime($tanggal) < strtotime(date('Y-m-d'))) {
        $error = "Tanggal janji temu tidak bisa di masa lalu.";
    } else {
        // Ambil nama dokter berdasarkan ID yang dipilih
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
            $stmt_update = $conn->prepare("UPDATE appointments SET user_id = ?, dokter = ?, tanggal = ?, waktu = ?, keterangan = ? WHERE id = ?");
            // Gunakan $dokter_nama di sini
            $stmt_update->bind_param("issssi", $user_id, $dokter_nama, $tanggal, $waktu, $keterangan, $appointment_id);

            if ($stmt_update->execute()) {
                $success = "Janji temu berhasil diperbarui!";
                // Perbarui data $appointment agar formulir menampilkan data terbaru
                $appointment['user_id'] = $user_id;
                $appointment['dokter'] = $dokter_nama;
                $appointment['tanggal'] = $tanggal;
                $appointment['waktu'] = $waktu;
                $appointment['keterangan'] = $keterangan;
            } else {
                $error = "Gagal memperbarui janji temu: " . $stmt_update->error;
            }
            $stmt_update->close();
        }
    }
}
?>

<div class="container mt-4">
    <div class="card shadow-lg p-4">
        <h2 class="card-title text-center mb-4"><i class="fas fa-edit me-2"></i> Edit Janji Temu</h2>

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
                    <label for="user_id" class="form-label">Pasien <span class="text-danger">*</span></label>
                    <select class="form-select" id="user_id" name="user_id" required>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?= htmlspecialchars($user['id']) ?>" <?= ($appointment['user_id'] == $user['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="dokter" class="form-label">Dokter <span class="text-danger">*</span></label>
                    <select class="form-select" id="dokter" name="dokter" required>
                        <?php foreach ($doctors as $doctor) : ?>
                            <option value="<?= htmlspecialchars($doctor['id']) ?>" <?= (isset($appointment['dokter']) && $appointment['dokter'] == $doctor['nama']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($doctor['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
include 'includes/footer.php';
$conn->close();
?>