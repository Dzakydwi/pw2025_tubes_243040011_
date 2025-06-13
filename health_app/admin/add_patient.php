<?php
include '../config/database.php';
include 'includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user'; // Default to user, admin can add admin too

    if (empty($nama) || empty($email) || empty($password)) {
        $error = "Nama, Email, dan Password wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } else {
        // Cek apakah email sudah terdaftar
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            $error = "Email sudah terdaftar.";
        } else {
            // Simpan password plain-text untuk demo ini (TIDAK UNTUK PRODUKSI)
            $hashed_password = $password;

            $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nama, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $success = "Pasien/Pengguna berhasil ditambahkan!";
                $_POST = array(); // Clear form fields
            } else {
                $error = "Registrasi gagal: " . $conn->error;
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}
?>

<div class="container">
    <div class="card shadow-lg p-4">
        <h1 class="card-title text-center mb-4">Tambah Pasien/Pengguna Baru</h1>

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
                <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama" name="nama" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div class="form-text">Minimal 6 karakter.</div>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Role Pengguna</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="user" <?= (($_POST['role'] ?? 'user') == 'user') ? 'selected' : '' ?>>User (Pasien)</option>
                    <option value="admin" <?= (($_POST['role'] ?? '') == 'admin') ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i> Tambah Pengguna
                </button>
                <a href="patients.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Pasien
                </a>
            </div>
        </form>
    </div>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>