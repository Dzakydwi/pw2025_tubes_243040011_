<?php
session_start();
include '../config/database.php'; // Pastikan path ini benar

$error = '';
$success_message = ''; // Untuk menampilkan pesan sukses dari registrasi

// Cek jika ada pesan sukses dari registrasi
if (isset($_GET['registration_success']) && $_GET['registration_success'] == 'true') {
    $success_message = "Registrasi berhasil! Silakan login dengan akun Anda.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Gunakan prepared statements untuk keamanan
    $stmt = $conn->prepare("SELECT id, nama, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Verifikasi password menggunakan password_verify()
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nama'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] == 'admin') {
                header('Location: ../admin/index.php');
            } else {
                header('Location: ../user/dashboard.php');
            }
            exit();
        } else {
            $error = "Email atau password salah.";
        }
    } else {
        $error = "Email atau password salah.";
    }
    $stmt->close();
    $conn->close(); // Tutup koneksi setelah selesai
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login ke HealthDoc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css"> </head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card shadow-lg p-4" style="width: 100%; max-width: 400px;">
            <h3 class="card-title text-center mb-4">Login</h3>
            <?php if ($success_message) : ?>
                <div class="alert alert-success" role="alert">
                    <?= $success_message ?>
                </div>
            <?php endif; ?>
            <?php if ($error) : ?>
                <div class="alert alert-danger" role="alert">
                    <?= $error ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required autocomplete="email">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Login</button>
                    <p class="text-center mt-3">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>