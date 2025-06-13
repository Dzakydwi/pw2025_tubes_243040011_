<?php
session_start();
include '../config/database.php'; // Sesuaikan path jika berbeda

// Periksa apakah pengguna sudah login dan role-nya 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Pengguna';

$appointment_id = $_GET['id'] ?? null;
$appointment = null;
$success = '';
$error = '';

// Ambil daftar dokter (users dengan role 'doctor') untuk ditampilkan di dropdown
$doctors = [];
$stmt_doctors = $conn->prepare("SELECT id, nama FROM users WHERE role = 'doctor' ORDER BY nama ASC");
if ($stmt_doctors) {
    $stmt_doctors->execute();
    $result_doctors = $stmt_doctors->get_result();
    while ($row = $result_doctors->fetch_assoc()) {
        $doctors[] = $row;
    }
    $stmt_doctors->close();
} else {
    $error = "Gagal mengambil daftar dokter: " . $conn->error;
}


if ($appointment_id) {
    // Ambil data janji temu berdasarkan ID dan pastikan milik user yang sedang login
    $stmt_fetch = $conn->prepare("SELECT id, dokter, tanggal, waktu FROM appointments WHERE id = ? AND user_id = ?");
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("ii", $appointment_id, $user_id);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        if ($result_fetch->num_rows > 0) {
            $appointment = $result_fetch->fetch_assoc();
        } else {
            $error = "Janji temu tidak ditemukan atau Anda tidak memiliki akses ke janji temu ini.";
            $appointment_id = null; // Set null agar form tidak ditampilkan
        }
        $stmt_fetch->close();
    } else {
        $error = "Gagal menyiapkan statement: " . $conn->error;
    }
} else {
    $error = "ID janji temu tidak disediakan.";
}

// Proses update janji temu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $appointment_id) {
    $new_tanggal = trim($_POST['tanggal'] ?? '');
    $new_waktu = trim($_POST['waktu'] ?? '');
    $new_dokter = trim($_POST['dokter'] ?? '');

    // Validasi input
    if (empty($new_tanggal) || empty($new_waktu) || empty($new_dokter)) {
        $error = "Tanggal, Waktu, dan Dokter wajib diisi.";
    } elseif (!in_array($new_dokter, array_column($doctors, 'nama'))) {
        $error = "Nama dokter tidak valid.";
    } else {
        // Update data di database
        $stmt_update = $conn->prepare("UPDATE appointments SET tanggal = ?, waktu = ?, dokter = ? WHERE id = ? AND user_id = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("sssii", $new_tanggal, $new_waktu, $new_dokter, $appointment_id, $user_id);
            if ($stmt_update->execute()) {
                $success = "Janji temu berhasil diperbarui!";
                // Perbarui objek $appointment setelah update agar form menampilkan data terbaru
                $appointment['tanggal'] = $new_tanggal;
                $appointment['waktu'] = $new_waktu;
                $appointment['dokter'] = $new_dokter;
            } else {
                $error = "Gagal memperbarui janji temu: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $error = "Gagal menyiapkan statement: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Janji Temu - Klinik Sehatku</title>
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

        /* Navbar Pengguna */
        .user-navbar {
            background-color: var(--primary-color);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .user-navbar .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.5rem;
        }
        .user-navbar .nav-link {
            color: rgba(255,255,255,0.8) !important;
            font-weight: 500;
            margin-left: 15px;
            transition: color 0.3s ease;
        }
        .user-navbar .nav-link:hover {
            color: white !important;
        }

        /* Container utama */
        .main-content-container {
            padding-top: 50px; 
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

    <nav class="navbar navbar-expand-lg user-navbar fixed-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Klinik Sehatku</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavUser" aria-controls="navbarNavUser" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavUser">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_appointment.php">Buat Janji Temu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="my_appointments.php">Janji Temu Saya</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profil Saya</a>
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
        <h1 class="mb-5 text-center">Edit Janji Temu</h1>

        <div class="form-card">
            <div class="card-header">Perbarui Detail Janji Temu Anda</div>
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

                <?php if ($appointment) : ?>
                    <form action="edit_appointment.php?id=<?= htmlspecialchars($appointment['id']) ?>" method="POST">
                        <div class="mb-3">
                            <label for="pasien_nama" class="form-label">Nama Anda</label>
                            <input type="text" class="form-control" id="pasien_nama" value="<?= htmlspecialchars($user_name) ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="dokter" class="form-label">Pilih Dokter</label>
                            <select class="form-select" id="dokter" name="dokter" required>
                                <option value="">Pilih Dokter</option>
                                <?php if (empty($doctors)) : ?>
                                    <option value="" disabled>Tidak ada dokter yang tersedia</option>
                                <?php else : ?>
                                    <?php foreach ($doctors as $doctor) : ?>
                                        <option value="<?= htmlspecialchars($doctor['nama'] ?? '') ?>" <?= ( ( (isset($appointment['dokter']) ? $appointment['dokter'] : '') ) == $doctor['nama'] ) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($doctor['nama'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal Janji Temu</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= htmlspecialchars($appointment['tanggal'] ?? '') ?>" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="waktu" class="form-label">Waktu Janji Temu</label>
                            <input type="time" class="form-control" id="waktu" name="waktu" value="<?= htmlspecialchars($appointment['waktu'] ?? '') ?>" required>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="my_appointments.php" class="btn btn-back"><i class="fas fa-arrow-left me-2"></i> Kembali ke Janji Temu</a>
                            <button type="submit" class="btn btn-submit"><i class="fas fa-save me-2"></i> Simpan Perubahan</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Klinik Sehatku. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>