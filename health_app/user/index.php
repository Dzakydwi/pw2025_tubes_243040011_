<?php
session_start();
include '../config/database.php';

// Pastikan pengguna sudah login dan memiliki peran 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name']; // Mengambil nama pengguna dari sesi

// --- Ambil Data untuk Dashboard Pengguna ---

// Ambil jumlah janji temu yang akan datang untuk pengguna ini
$total_appointments = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_appointments FROM appointments WHERE user_id = ? AND tanggal >= CURDATE()");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $total_appointments = $data['total_appointments'];
    $stmt->close();
}


// Ambil janji temu terbaru/terdekat (misalnya 3 janji temu terdekat)
$upcoming_appointments_list = [];
// *** PERBAIKAN: Menghapus kolom 'status' dari SELECT statement ***
$stmt_latest = $conn->prepare("SELECT dokter, tanggal, waktu FROM appointments WHERE user_id = ? AND tanggal >= CURDATE() ORDER BY tanggal ASC, waktu ASC LIMIT 3");
if ($stmt_latest) {
    $stmt_latest->bind_param("i", $user_id);
    $stmt_latest->execute();
    $result_latest = $stmt_latest->get_result();
    while ($row = $result_latest->fetch_assoc()) {
        $upcoming_appointments_list[] = $row;
    }
    $stmt_latest->close();
}

// --- Akhir Pengambilan Data ---

// Asumsikan ada file header.php dan footer.php di folder 'includes'
// Anda bisa menambahkan link CSS dan JS di header.php atau langsung di sini.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengguna - Klinik Sehatku</title>
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

        /* Navbar khusus untuk User Dashboard */
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

        /* Container utama dashboard */
        .dashboard-container {
            padding-top: 50px; /* Sesuaikan dengan tinggi navbar */
            padding-bottom: 50px;
        }

        /* Card styles */
        .dashboard-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
            margin-bottom: 30px;
            background-color: white;
            padding: 25px;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .dashboard-card .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--heading-color);
        }
        .dashboard-card .icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-right: 15px;
        }

        /* Welcome section */
        .welcome-section {
            background: linear-gradient(45deg, #e9f7ff, #cfe8ff);
            border-radius: 15px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
        }
        .welcome-section h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .welcome-section p {
            font-size: 1.1rem;
            color: var(--text-color);
        }

        /* List items for appointments/tips */
        .list-group-item {
            border-left: 5px solid var(--primary-color); /* Garis biru di samping */
            margin-bottom: 8px;
            border-radius: 8px;
            font-size: 0.95rem;
            padding: 12px 20px;
            background-color: #fcfdff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .list-group-item:last-child {
            margin-bottom: 0;
        }

        /* Button styles */
        .btn-primary-custom {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 30px;
            padding: 10px 25px;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        .btn-primary-custom:hover {
            background-color: #0056b3;
            border-color: #0056b3;
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
            <a class="navbar-brand" href="#">Klinik Sehatku</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavUser" aria-controls="navbarNavUser" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavUser">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">Janji Temu Saya</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php">Logout <i class="fas fa-sign-out-alt ms-1"></i></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div style="height: 80px;"></div> 

    <div class="container dashboard-container">
        <div class="welcome-section">
            <h1>Selamat Datang, <?= htmlspecialchars($user_name) ?>!</h1>
            <p>Jaga kesehatan Anda tetap prima bersama Klinik Sehatku. Mari kelola janji temu Anda dengan mudah.</p>
            <a href="create_appointment.php" class="btn btn-primary-custom"><i class="fas fa-plus-circle me-2"></i> Buat Janji Temu Baru</a>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="dashboard-card">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-calendar-alt icon me-3"></i>
                        <h5 class="mb-0">Janji Temu Mendatang (<?= $total_appointments ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($upcoming_appointments_list)) : ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($upcoming_appointments_list as $appointment) : ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="fw-bold">Dr. <?= htmlspecialchars($appointment['dokter']) ?></span>
                                            <br>
                                            <small><i class="far fa-calendar-alt me-1"></i> <?= date('d M Y', strtotime($appointment['tanggal'])) ?> | <i class="far fa-clock me-1"></i> <?= date('H:i', strtotime($appointment['waktu'])) ?> WIB</small>
                                        </div>
                                        </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if ($total_appointments > 3) : ?>
                                <div class="text-center mt-3">
                                    <a href="appointments.php" class="btn btn-outline-primary btn-sm">Lihat Semua Janji Temu <i class="fas fa-arrow-right ms-1"></i></a>
                                </div>
                            <?php endif; ?>
                        <?php else : ?>
                            <p class="text-center text-muted">Anda tidak memiliki janji temu yang akan datang.</p>
                            <div class="text-center">
                                <a href="create_appointment.php" class="btn btn-outline-success btn-sm"><i class="fas fa-plus me-1"></i> Buat Janji Temu Pertama Anda</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="dashboard-card">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-lightbulb icon me-3"></i>
                        <h5 class="mb-0">Tips Kesehatan untuk Anda</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Menjaga kesehatan adalah investasi terbaik untuk masa depan. Pastikan Anda mendapatkan cukup istirahat, makan makanan bergizi, dan berolahraga secara teratur.
                        </p>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i> Konsumsi air putih yang cukup (8 gelas/hari).</li>
                            <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i> Tidur 7-9 jam setiap malam.</li>
                            <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i> Rutin berolahraga minimal 30 menit sehari.</li>
                            <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i> Jaga kebersihan diri dan lingkungan.</li>
                        </ul>
                        <div class="text-center mt-3">
                             <a href="#" class="btn btn-outline-info btn-sm">Pelajari Lebih Lanjut <i class="fas fa-external-link-alt ms-1"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="dashboard-card text-center py-4">
                    <h4 class="mb-3">Butuh bantuan atau informasi lebih lanjut?</h4>
                    <p class="mb-4">Tim kami siap membantu Anda. Jangan ragu untuk menghubungi kami.</p>
                    <a href="#" class="btn btn-primary-custom me-3"><i class="fas fa-phone-alt me-2"></i> Hubungi Kami</a>
                    <a href="profile.php" class="btn btn-outline-secondary"><i class="fas fa-user-circle me-2"></i> Lihat Profil Saya</a>
                </div>
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