<?php
session_start();
include '../config/database.php'; // Sesuaikan path jika berbeda

// Periksa apakah pengguna sudah login dan role-nya 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Pengguna'; // Default jika nama tidak ada di sesi

$success_message = '';
$error_message = '';
$upload_success = '';
$upload_error = '';

// Ambil data profil pengguna saat ini
$user_data = [];
$stmt_profile = $conn->prepare("SELECT nama, email, profile_picture FROM users WHERE id = ?");
if ($stmt_profile) {
    $stmt_profile->bind_param("i", $user_id);
    $stmt_profile->execute();
    $result_profile = $stmt_profile->get_result();
    if ($result_profile->num_rows > 0) {
        $user_data = $result_profile->fetch_assoc();
        $_SESSION['user_name'] = $user_data['nama']; // Perbarui sesi dengan nama terbaru
    } else {
        $error_message = "Data pengguna tidak ditemukan.";
    }
    $stmt_profile->close();
} else {
    $error_message = "Gagal mengambil data profil: " . $conn->error;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle Profile Picture Upload First (if any)
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        // Ini akan memanggil logic dari upload_handler.php
        include 'upload_handler.php'; // Pastikan path ini benar

        // Setelah upload_handler.php dieksekusi, perbarui user_data jika sukses
        if (!empty($upload_success)) {
            // Refresh user_data to get the new profile picture name
            $stmt_refresh_profile = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
            $stmt_refresh_profile->bind_param("i", $user_id);
            $stmt_refresh_profile->execute();
            $result_refresh_profile = $stmt_refresh_profile->get_result();
            if ($result_refresh_profile->num_rows > 0) {
                $user_data['profile_picture'] = $result_refresh_profile->fetch_assoc()['profile_picture'];
            }
            $stmt_refresh_profile->close();
        }
    }

    // Handle Profile Data Update (Name, Email, Password)
    if (isset($_POST['update_profile'])) {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validasi input
        if (empty($nama) || empty($email)) {
            $error_message = "Nama dan Email wajib diisi.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Format email tidak valid.";
        }

        // Siapkan bagian query untuk update
        $sql = "UPDATE users SET nama = ?, email = ? WHERE id = ?";
        $params = [$nama, $email, $user_id];
        $types = "ssi";

        // Jika password diisi, validasi dan tambahkan ke query
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $error_message = "Password baru minimal 6 karakter.";
            } elseif ($password !== $confirm_password) {
                $error_message = "Konfirmasi password baru tidak cocok.";
            } else {
                // HASH PASSWORD BARU SEBELUM DISIMPAN
                $hashed_new_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET nama = ?, email = ?, password = ? WHERE id = ?";
                $params = [$nama, $email, $hashed_new_password, $user_id];
                $types = "sssi";
            }
        }

        if (empty($error_message)) { // Hanya proses update jika tidak ada error validasi
            $stmt_update = $conn->prepare($sql);
            if ($stmt_update) {
                $stmt_update->bind_param($types, ...$params);
                if ($stmt_update->execute()) {
                    $_SESSION['user_name'] = $nama; // Perbarui nama di sesi
                    $user_data['nama'] = $nama; // Perbarui data yang ditampilkan
                    $user_data['email'] = $email; // Perbarui data yang ditampilkan
                    $success_message = "Profil berhasil diperbarui!";

                    // Jika password diubah, paksa logout untuk login ulang (opsional, tapi disarankan)
                    if (!empty($password)) {
                        session_unset();
                        session_destroy();
                        header('Location: ../auth/login.php?message=password_changed'); // Redirect dengan pesan
                        exit();
                    }

                } else {
                    $error_message = "Gagal memperbarui profil: " . $stmt_update->error;
                }
                $stmt_update->close();
            } else {
                $error_message = "Gagal menyiapkan statement update profil: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - HealthDoc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-picture-container {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 20px auto;
            border: 3px solid var(--primary-color);
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #fff;
        }
        .profile-picture-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .file-upload-label {
            cursor: pointer;
            padding: 8px 15px;
            background-color: var(--secondary-color);
            color: white;
            border-radius: 5px;
            display: inline-block;
            margin-top: 10px;
        }
        .file-upload-label:hover {
            background-color: #5a6268;
        }
        #profile_picture_input {
            display: none;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="card shadow-lg mx-auto" style="max-width: 700px;">
            <div class="card-header bg-primary text-white text-center py-4">
                <h2 class="mb-0"><i class="fas fa-user-circle me-2"></i> Profil Saya</h2>
            </div>
            <div class="card-body p-4">
                <?php if ($success_message) : ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $success_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error_message) : ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if ($upload_success) : ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $upload_success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if ($upload_error) : ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $upload_error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($user_data) : ?>
                    <div class="text-center mb-4">
                        <div class="profile-picture-container">
                            <img src="<?= htmlspecialchars(!empty($user_data['profile_picture']) ? '../assets/img/' . $user_data['profile_picture'] : '../assets/img/default_profile.png') ?>" alt="Foto Profil" class="img-fluid">
                        </div>
                        <form action="" method="POST" enctype="multipart/form-data" class="d-inline-block">
                            <label for="profile_picture_input" class="file-upload-label">
                                <i class="fas fa-camera me-2"></i> Ubah Foto Profil
                            </label>
                            <input type="file" id="profile_picture_input" name="profile_picture" accept="image/*" onchange="this.form.submit()">
                        </form>
                    </div>

                    <form action="" method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="mb-3 text-start">
                            <label for="nama" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($user_data['nama'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3 text-start">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3 text-start">
                            <label for="password" class="form-label">Password Baru (kosongkan jika tidak ingin mengubah)</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        <div class="mb-3 text-start">
                            <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-submit">Simpan Perubahan Profil</button>
                        </div>
                    </form>
                <?php else : ?>
                    <p class="text-center">Tidak dapat memuat data profil Anda.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025. HealthDoc All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>