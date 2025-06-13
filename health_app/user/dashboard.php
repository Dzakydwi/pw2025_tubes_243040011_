<?php
session_start();
include '../config/database.php'; // Sesuaikan path jika berbeda

// Periksa apakah pengguna sudah login dan role-nya 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Pengguna'; // Ambil nama pengguna dari session, default 'Pengguna'

// Ambil jumlah janji temu yang akan datang untuk pengguna ini
$total_upcoming_appointments = 0;
$stmt_upcoming = $conn->prepare("SELECT COUNT(*) AS total FROM appointments WHERE user_id = ? AND tanggal >= CURDATE()");
if ($stmt_upcoming) {
    $stmt_upcoming->bind_param("i", $user_id);
    $stmt_upcoming->execute();
    $result_upcoming = $stmt_upcoming->get_result();
    $data_upcoming = $result_upcoming->fetch_assoc();
    $total_upcoming_appointments = $data_upcoming['total'];
    $stmt_upcoming->close();
} else {
    // Handle error atau set pesan default
    $error = "Gagal mengambil jumlah janji temu mendatang: " . $conn->error;
}

// Ambil detail janji temu terdekat untuk pengguna ini
$next_appointment = null;
$stmt_next_app = $conn->prepare("SELECT dokter, tanggal, waktu FROM appointments WHERE user_id = ? AND tanggal >= CURDATE() ORDER BY tanggal ASC, waktu ASC LIMIT 1");
if ($stmt_next_app) {
    $stmt_next_app->bind_param("i", $user_id);
    $stmt_next_app->execute();
    $result_next_app = $stmt_next_app->get_result();
    $next_appointment = $result_next_app->fetch_assoc();
    $stmt_next_app->close();
} else {
    $error = "Gagal mengambil janji temu terdekat: " . $conn->error;
}

// Ambil jumlah total janji temu (termasuk yang sudah lewat)
$total_all_appointments = 0;
$stmt_all = $conn->prepare("SELECT COUNT(*) AS total FROM appointments WHERE user_id = ?");
if ($stmt_all) {
    $stmt_all->bind_param("i", $user_id);
    $stmt_all->execute();
    $result_all = $stmt_all->get_result();
    $data_all = $result_all->fetch_assoc();
    $total_all_appointments = $data_all['total'];
    $stmt_all->close();
}
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
            /* background-color: var(--light-bg); */
            background: url('../assets/img/bg\ home.png') no-repeat center center fixed;
            background-size: cover;
            line-height: 1.7;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            color: var(--light-bg);
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
            padding-top: 50px; /* Sesuaikan dengan tinggi navbar */
            padding-bottom: 50px;
        }

        /* Card Statistik */
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .stat-card.bg-info { background-color: #17a2b8 !important; }
        .stat-card.bg-success { background-color: #28a745 !important; }
        .stat-card.bg-warning { background-color: #ffc107 !important; }
        .stat-card .card-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .stat-card h5 {
            color: white;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .stat-card .fs-1 {
            font-size: 2.5rem !important;
            font-weight: 700;
        }
        .stat-card a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .stat-card a:hover {
            color: white;
        }

        /* Info Card Janji Temu */
        .info-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            background-color: white;
            padding: 25px;
        }
        .info-card .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--heading-color);
        }

        .list-group-item {
            border: none;
            padding: 10px 0;
            background-color: transparent;
        }
        .list-group-item:last-child {
            border-bottom: none;
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
                        <a class="nav-link active" aria-current="page" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_appointment.php">Buat Janji Temu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_appointments.php">Janji Temu Saya</a>
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
        <h1 class="mb-4">Halo, <?= htmlspecialchars($user_name) ?>!</h1>
        <p class="lead mb-5 text-white">Selamat datang di Dashboard Pengguna Klinik Sehatku.</p>

        <?php if (isset($error)) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6 col-lg-4">
                <div class="stat-card bg-info">
                    <div class="card-icon"><i class="fas fa-calendar-alt"></i></div>
                    <h5>Janji Temu Mendatang</h5>
                    <h5 class="fs-1 fw-bold"><?= $total_upcoming_appointments ?></h5>
                    <p class="card-text">Janji temu Anda yang akan datang.</p>
                    <a href="my_appointments.php?filter=upcoming">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="stat-card bg-success">
                    <div class="card-icon"><i class="fas fa-plus-circle"></i></div>
                    <h5>Buat Janji Temu</h5>
                    <h5 class="fs-1 fw-bold">+</h5>
                    <p class="card-text">Buat janji temu baru dengan dokter.</p>
                    <a href="create_appointment.php">Buat Sekarang <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="stat-card bg-secondary">
                    <div class="card-icon"><i class="fas fa-history"></i></div>
                    <h5>Riwayat Janji Temu</h5>
                    <h5 class="fs-1 fw-bold"><?= $total_all_appointments ?></h5>
                    <p class="card-text">Total janji temu Anda.</p>
                    <a href="my_appointments.php">Lihat Riwayat <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="info-card">
                    <div class="card-header">
                        <i class="fas fa-calendar-check me-2"></i> Janji Temu Terdekat Anda
                    </div>
                    <div class="card-body">
                        <?php if ($next_appointment) : ?>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    Dokter: <?= htmlspecialchars($next_appointment['dokter']) ?>
                                </li>
                                <li class="list-group-item">
                                    Tanggal: <?= htmlspecialchars(date('d F Y', strtotime($next_appointment['tanggal']))) ?>
                                </li>
                                <li class="list-group-item">
                                    Waktu: <?= htmlspecialchars(date('H:i', strtotime($next_appointment['waktu']))) ?>
                                </li>
                            </ul>
                            <p class="mt-3 text-muted">Pastikan Anda datang tepat waktu.</p>
                        <?php else : ?>
                            <div class="alert alert-info text-center" role="alert">
                                Anda tidak memiliki janji temu yang akan datang.
                            </div>
                            <div class="text-center">
                                <a href="create_appointment.php" class="btn btn-primary mt-2">Buat Janji Temu Pertama Anda</a>
                            </div>
                        <?php endif; ?>
                    </div>
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