<?php
session_start();
include '../config/database.php'; // Pastikan path ini benar

$success = '';
$error = '';

// Inisialisasi variabel untuk menghindari "Undefined variable" warnings
$nama = '';
$email = '';
// Password tidak perlu diinisialisasi di sini karena tidak ditampilkan dalam value input

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($nama) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Semua field harus diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } else {
        // Cek apakah email sudah terdaftar
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            $error = "Email sudah terdaftar. Silakan gunakan email lain atau login.";
        } else {
            // Hashing password sebelum menyimpan ke database (KEAMANAN PENTING!)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user'; // Default role untuk pendaftaran adalah 'user'
            $profile_picture = 'default_profile.png'; // Default profile picture

            $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role, profile_picture) VALUES (?, ?, ?, ?, ?)");
            // Perhatikan 'sssss' jika Anda menambahkan 'profile_picture' ke bind_param
            $stmt->bind_param("sssss", $nama, $email, $hashed_password, $role, $profile_picture);

            if ($stmt->execute()) {
                $success = "Registrasi berhasil! Silakan login.";
                // Setelah register berhasil, langsung redirect ke halaman login
                header('Location: login.php?registration_success=true');
                exit();
            } else {
                $error = "Registrasi gagal: " . $stmt->error;
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Baru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css"> </head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card shadow-lg p-4" style="width: 100%; max-width: 400px;">
            <h3 class="card-title text-center mb-4">Daftar Akun Baru</h3>
            <?php if ($success) : ?>
                <div class="alert alert-success" role="alert">
                    <?= $success ?>
                </div>
            <?php endif; ?>
            <?php if ($error) : ?>
                <div class="alert alert-danger" role="alert">
                    <?= $error ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="nama" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama" name="nama" required autocomplete="name" value="<?= htmlspecialchars($nama) ?>">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required autocomplete="email" value="<?= htmlspecialchars($email) ?>">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password">
                    <small class="form-text text-muted">Minimal 6 karakter.</small>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success">Daftar</button>
                    <p class="text-center mt-3">Sudah punya akun? <a href="login.php">Login di sini</a></p>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>