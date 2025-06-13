<?php
session_start();
include '../config/database.php';

// Periksa apakah admin sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$doctor_id = $_GET['id'] ?? null;
$doctor_data = null;
$success = '';
$error = '';

if (!$doctor_id) {
    header('Location: doctors.php'); // Kembali ke daftar dokter jika ID tidak ada
    exit();
}

// Ambil data dokter yang akan diedit
$stmt_fetch = $conn->prepare("SELECT id, nama, email, role FROM users WHERE id = ? AND role = 'doctor'");
if ($stmt_fetch) {
    $stmt_fetch->bind_param("i", $doctor_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    if ($result_fetch->num_rows > 0) {
        $doctor_data = $result_fetch->fetch_assoc();
    } else {
        $error = "Dokter tidak ditemukan.";
    }
    $stmt_fetch->close();
} else {
    $error = "Gagal menyiapkan statement: " . $conn->error;
}

// Proses update data dokter
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $doctor_data) {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; // Opsional: untuk mengganti password
    $confirm_password = $_POST['confirm_password'] ?? '';
    // Jika Anda menambahkan 'spesialisasi' di masa mendatang, ambil di sini:
    // $spesialisasi = trim($_POST['spesialisasi'] ?? '');

    // Validasi input
    if (empty($nama) || empty($email)) {
        $error = "Nama dan Email wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } else {
        // Cek apakah email sudah terdaftar oleh user lain (jika email diubah)
        $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt_check_email->bind_param("si", $email, $doctor_id);
        $stmt_check_email->execute();
        $result_check_email = $stmt_check_email->get_result();
        if ($result_check_email->num_rows > 0) {
            $error = "Email sudah terdaftar untuk pengguna lain.";
        } else {
            $update_sql = "UPDATE users SET nama = ?, email = ?";
            $params = "ss";
            $values = [&$nama, &$email];

            if (!empty($password)) {
                if ($password !== $confirm_password) {
                    $error = "Konfirmasi password tidak cocok.";
                } elseif (strlen($password) < 6) {
                    $error = "Password minimal 6 karakter.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_sql .= ", password = ?";
                    $params .= "s";
                    $values[] = &$hashed_password;
                }
            }
            
            // Jika Anda menambahkan 'spesialisasi' di masa mendatang, tambahkan ini:
            // $update_sql .= ", spesialisasi = ?";
            // $params .= "s";
            // $values[] = &$spesialisasi;

            $update_sql .= " WHERE id = ? AND role = 'doctor'";
            $params .= "i";
            $values[] = &$doctor_id;

            if (empty($error)) { // Lanjutkan jika tidak ada error password
                $stmt_update = $conn->prepare($update_sql);
                if ($stmt_update) {
                    // Bind parameter secara dinamis
                    call_user_func_array([$stmt_update, 'bind_param'], array_merge([$params], $values));

                    if ($stmt_update->execute()) {
                        $success = "Data dokter berhasil diperbarui!";
                        // Update data dokter yang ditampilkan di form
                        $doctor_data['nama'] = $nama;
                        $doctor_data['email'] = $email;
                        // $doctor_data['spesialisasi'] = $spesialisasi; // Jika ada
                    } else {
                        $error = "Gagal memperbarui data dokter: " . $stmt_update->error;
                    }
                    $stmt_update->close();
                } else {
                    $error = "Gagal menyiapkan statement update: " . $conn->error;
                }
            }
        }
        $stmt_check_email->close();
    }
}

// Jika dokter tidak ditemukan pada awalnya atau setelah update gagal, redirect kembali
if (!$doctor_data && empty($error)) {
    header('Location: doctors.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Dokter - Admin Klinik Sehatku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --light-bg: #f8f9fa;
            --dark-bg: #2c3e50;
            --text-color: #333;
            --heading-color: #2c3e50;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            background-color: var(--light-bg);
            line-height: 1.7;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            color: var(--heading-color);
        }

        /* Navbar Admin */
        .admin-navbar {
            background-color: var(--dark-bg);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .admin-navbar .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.5rem;
        }
        .admin-navbar .nav-link {
            color: rgba(255,255,255,0.7) !important;
            font-weight: 500;
            margin-left: 15px;
            transition: color 0.3s ease;
        }
        .admin-navbar .nav-link:hover {
            color: white !important;
        }

        /* Container utama */
        .main-content-container {
            padding-top: 50px; /* Sesuaikan dengan tinggi navbar */
            padding-bottom: 50px;
        }

        /* Card/Panel untuk Form */
        .form-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            background-color: white;
            padding: 25px;
            max-width: 600px; /* Lebar form */
            margin-left: auto;
            margin-right: auto;
        }
        .form-card .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--heading-color);
            text-align: center;
        }

        .btn-submit {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        .btn-submit:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            transform: translateY(-2px);
        }
        .btn-back {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        .btn-back:hover {
            background-color: #5a6268;
            border-color: #5a6268;
            transform: translateY(-2px);
        }

        /* Footer */
        .footer {
            background-color: var(--dark-bg);
            color: white;
            padding: 30px 0;
            text-align: center;
            font-size: 0.85rem;
            margin-top: 50px;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg admin-navbar fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">Admin Klinik Sehatku</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAdmin" aria-controls="navbarNavAdmin" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavAdmin">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="patients.php">Manajemen Pasien</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">Manajemen Janji Temu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="doctors.php">Manajemen Dokter</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php">Logout <i class="fas fa-sign-out-alt ms-1"></i></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div style="height: 80px;"></div> 

    <div class="container main-content-container">
        <h1 class="mb-5 text-center">Edit Dokter</h1>

        <div class="form-card">
            <div class="card-header">Detail Dokter</div>
            <div class="card-body">
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

                <?php if ($doctor_data) : ?>
                <form action="edit_doctor.php?id=<?= htmlspecialchars($doctor_data['id']) ?>" method="POST">
                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama Lengkap Dokter</label>
                        <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($doctor_data['nama']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Dokter</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($doctor_data['email']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password Baru (kosongkan jika tidak ingin mengubah)</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small class="form-text text-muted">Minimal 6 karakter.</small>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="doctors.php" class="btn btn-back"><i class="fas fa-arrow-left me-2"></i> Kembali</a>
                        <button type="submit" class="btn btn-submit"><i class="fas fa-save me-2"></i> Simpan Perubahan</button>
                    </div>
                </form>
                <?php else : ?>
                    <div class="alert alert-warning text-center" role="alert">
                        Data dokter tidak dapat dimuat.
                    </div>
                    <div class="d-flex justify-content-center">
                        <a href="doctors.php" class="btn btn-back"><i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Dokter</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Admin Klinik Sehatku. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>