<?php
include '../config/database.php';
include 'includes/header.php';

$patient_id = $_GET['id'] ?? null;
$error = '';
$success = '';
$patient_data = null;

if (!$patient_id) {
    header('Location: patients.php');
    exit();
}

// Ambil data pasien
$stmt_get = $conn->prepare("SELECT id, nama, email, role, profile_picture FROM users WHERE id = ? AND role = 'user'"); // Hanya edit user
$stmt_get->bind_param("i", $patient_id);
$stmt_get->execute();
$result_get = $stmt_get->get_result();
if ($result_get->num_rows > 0) {
    $patient_data = $result_get->fetch_assoc();
} else {
    $_SESSION['message'] = "Pasien tidak ditemukan.";
    $_SESSION['message_type'] = "danger";
    header('Location: patients.php');
    exit();
}
$stmt_get->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_patient'])) {
        $nama = $_POST['nama'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? 'user';

        if (empty($nama) || empty($email)) {
            $error = "Nama dan Email wajib diisi.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format email tidak valid.";
        } else {
            // Cek apakah email sudah digunakan oleh user lain
            $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt_check_email->bind_param("si", $email, $patient_id);
            $stmt_check_email->execute();
            $result_check_email = $stmt_check_email->get_result();
            if ($result_check_email->num_rows > 0) {
                $error = "Email ini sudah digunakan oleh pengguna lain.";
            } else {
                $stmt_update = $conn->prepare("UPDATE users SET nama = ?, email = ?, role = ? WHERE id = ?");
                $stmt_update->bind_param("sssi", $nama, $email, $role, $patient_id);
                if ($stmt_update->execute()) {
                    $success = "Data pasien berhasil diperbarui!";
                    // Update patient_data yang ditampilkan setelah berhasil disimpan
                    $patient_data['nama'] = $nama;
                    $patient_data['email'] = $email;
                    $patient_data['role'] = $role;
                } else {
                    $error = "Gagal memperbarui data pasien: " . $stmt_update->error;
                }
                $stmt_update->close();
            }
            $stmt_check_email->close();
        }
    } elseif (isset($_POST['upload_photo'])) {
        // Shared upload handler (could be a separate function or file)
        include 'upload_handler.php'; // This file should handle the upload and update 'profile_picture' for the patient_id
        if (isset($upload_success)) {
            $success = $upload_success;
            $patient_data['profile_picture'] = $newFileName; // Update displayed image
        } elseif (isset($upload_error)) {
            $error = $upload_error;
        }
    }
}
?>

<div class="container">
    <div class="card shadow-lg p-4">
        <h1 class="card-title text-center mb-4">Edit Data Pasien</h1>

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

        <?php if ($patient_data) : ?>
            <div class="row">
                <div class="col-md-4 text-center mb-4">
                    <?php $profile_pic_path = $patient_data['profile_picture'] ? '../assets/img/' . $patient_data['profile_picture'] : '../assets/img/default_profile.png'; ?>
                    <img src="<?= $profile_pic_path ?>" class="img-fluid rounded-circle mb-3" alt="Foto Profil" style="width: 150px; height: 150px; object-fit: cover; border: 2px solid #007bff;">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="upload_photo" value="1">
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label visually-hidden">Upload Foto Profil</label>
                            <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/jpeg, image/png, image/gif">
                            <div class="form-text">Maks. 2MB. Format: JPG, PNG, GIF.</div>
                        </div>
                        <button type="submit" class="btn btn-info btn-sm">
                            <i class="fas fa-upload me-2"></i> Unggah Foto
                        </button>
                    </form>
                </div>
                <div class="col-md-8">
                    <form method="POST" action="">
                        <input type="hidden" name="update_patient" value="1">
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($patient_data['nama']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($patient_data['email']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role Pengguna</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user" <?= ($patient_data['role'] == 'user') ? 'selected' : '' ?>>User (Pasien)</option>
                                <option value="admin" <?= ($patient_data['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Simpan Perubahan
                            </button>
                            <a href="patients.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Pasien
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php else : ?>
            <div class="alert alert-warning text-center" role="alert">
                Data pasien tidak ditemukan.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>