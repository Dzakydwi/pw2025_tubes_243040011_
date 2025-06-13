<?php
include '../config/database.php'; // Koneksi database
include 'includes/header.php';

// Periksa apakah admin sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php'); // Redirect ke halaman login jika bukan admin
    exit();
}

// Ambil statistik
$total_patients = 0;
$total_appointments = 0;
$upcoming_appointments = 0;

$result_patients = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'user'");
if ($result_patients) {
    $total_patients = $result_patients->fetch_assoc()['total'];
}

$result_appointments = $conn->query("SELECT COUNT(*) AS total FROM appointments");
if ($result_appointments) {
    $total_appointments = $result_appointments->fetch_assoc()['total'];
}

$result_upcoming = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE tanggal >= CURDATE()");
if ($result_upcoming) {
    $upcoming_appointments = $result_upcoming->fetch_assoc()['total'];
}

// --- LOGIKA UNTUK AKTIVITAS TERAKHIR DIMULAI DI SINI ---

$recent_activities = [];

// Ambil 5 janji temu terbaru (misalnya berdasarkan tanggal pembuatan)
// PERBAIKAN: Menggunakan a.dokter langsung karena tidak ada dokter_id
$stmt_appointments = $conn->prepare("
    SELECT a.tanggal, a.waktu, a.dokter, u.nama as patient_name
    FROM appointments a
    JOIN users u ON a.user_id = u.id
    ORDER BY a.tanggal DESC, a.waktu DESC LIMIT 5
");

if ($stmt_appointments) {
    $stmt_appointments->execute();
    $result_appointments_log = $stmt_appointments->get_result();
    while ($row = $result_appointments_log->fetch_assoc()) {
        $recent_activities[] = [
            'type' => 'appointment',
            'message' => 'Janji temu baru dengan ' . htmlspecialchars($row['dokter']) . ' untuk ' . htmlspecialchars($row['patient_name']) . ' pada ' . date('d M Y H:i', strtotime($row['tanggal'] . ' ' . $row['waktu'])),
            'timestamp' => $row['tanggal'] . ' ' . $row['waktu'] // Gunakan timestamp asli untuk sorting jika diperlukan
        ];
    }
    $stmt_appointments->close();
}

// Ambil 5 pendaftaran pasien baru terbaru
// Asumsi tabel 'users' memiliki kolom 'created_at'
$stmt_new_users = $conn->prepare("SELECT nama, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5");
if ($stmt_new_users) {
    $stmt_new_users->execute();
    $result_new_users = $stmt_new_users->get_result();
    while ($row = $result_new_users->fetch_assoc()) {
        $recent_activities[] = [
            'type' => 'new_user',
            'message' => 'Pasien baru mendaftar: ' . htmlspecialchars($row['nama']),
            'timestamp' => $row['created_at']
        ];
    }
    $stmt_new_users->close();
}

// Sortir aktivitas berdasarkan timestamp terbaru
usort($recent_activities, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Hanya tampilkan 10 aktivitas terbaru (atau sesuai kebutuhan)
$recent_activities = array_slice($recent_activities, 0, 10);

// --- AKHIR LOGIKA AKTIVITAS TERAKHIR ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Klinik Sehatku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #007bff; /* Biru Bootstrap primer */
            --secondary-color: #6c757d; /* Abu-abu Bootstrap sekunder */
            --light-bg: #f8f9fa; /* Latar belakang terang */
            --dark-bg: #2c3e50; /* Latar belakang gelap untuk footer */
            --text-color: #333;
            --heading-color: #2c3e50;
            --info-color: #17a2b8; /* Bootstrap Info */
            --success-color: #28a745; /* Bootstrap Success */
            --warning-color: #ffc107; /* Bootstrap Warning */
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

        /* Navbar khusus untuk Admin Dashboard */
        .admin-navbar {
            background-color: var(--dark-bg); /* Warna gelap untuk navbar admin */
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

        /* Container utama dashboard */
        .dashboard-container {
            padding-top: 50px; /* Sesuaikan dengan tinggi navbar */
            padding-bottom: 50px;
        }

        /* Statistik Cards */
        .statistic-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
            padding: 25px;
            text-align: center;
            height: 100%; /* Pastikan tinggi kartu sama */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white; /* Default text color for stats */
            background-color: var(--primary-color); /* Default background */
        }
        .statistic-card.bg-primary { background-image: linear-gradient(45deg, #007bff, #0056b3); }
        .statistic-card.bg-success { background-image: linear-gradient(45deg, #28a745, #1e7e34); }
        .statistic-card.bg-info { background-image: linear-gradient(45deg, #17a2b8, #117a8b); }

        .statistic-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }
        .statistic-card .card-icon {
            font-size: 3.5rem; /* Ukuran ikon lebih besar */
            margin-bottom: 20px;
            opacity: 0.8;
        }
        .statistic-card .card-title {
            font-size: 3rem; /* Ukuran angka statistik lebih besar */
            font-weight: 700;
            margin-bottom: 10px;
        }
        .statistic-card .card-text {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .statistic-card a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .statistic-card a:hover {
            color: white;
            text-decoration: underline;
        }

        /* Recent Activity Card */
        .activity-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            background-color: white;
            padding: 25px;
        }
        .activity-card .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--heading-color);
            display: flex;
            align-items: center;
        }
        .activity-card .list-group-item {
            border-left: 5px solid var(--secondary-color); /* Garis di samping */
            margin-bottom: 8px;
            border-radius: 8px;
            font-size: 0.95rem;
            padding: 12px 20px;
            background-color: #fcfdff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .activity-card .list-group-item:last-child {
            margin-bottom: 0;
        }
        .activity-card .activity-message {
            color: var(--text-color);
        }
        .activity-card .activity-time {
            font-size: 0.85rem;
            color: var(--secondary-color);
            margin-top: 5px;
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
            <a class="navbar-brand" href="#">Admin HealthDoc</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAdmin" aria-controls="navbarNavAdmin" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavAdmin">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="patients.php">Manajemen Pasien</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">Manajemen Janji Temu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="doctors.php">Manajemen Dokter</a>
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
        <h1 class="mb-5 text-center">Dashboard Admin</h1>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="statistic-card bg-primary">
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                    <div class="card-title"><?= $total_patients ?></div>
                    <div class="card-text">Total Pasien Terdaftar</div>
                    <a href="patients.php">Lihat Detail <i class="fas fa-arrow-circle-right ms-2"></i></a>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="statistic-card bg-success">
                    <div class="card-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="card-title"><?= $total_appointments ?></div>
                    <div class="card-text">Total Semua Janji Temu</div>
                    <a href="appointments.php">Lihat Detail <i class="fas fa-arrow-circle-right ms-2"></i></a>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="statistic-card bg-info">
                    <div class="card-icon"><i class="fas fa-hourglass-half"></i></div>
                    <div class="card-title"><?= $upcoming_appointments ?></div>
                    <div class="card-text">Janji Temu Mendatang</div>
                    <a href="appointments.php?filter=upcoming">Lihat Detail <i class="fas fa-arrow-circle-right ms-2"></i></a>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="activity-card">
                    <div class="card-header">
                        <i class="fas fa-chart-line me-3"></i>
                        <h5 class="mb-0">Aktivitas Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_activities)) : ?>
                            <p class="text-center text-muted">Belum ada aktivitas terbaru.</p>
                        <?php else : ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($recent_activities as $activity) : ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-start">
                                        <div class="activity-message">
                                            <?= $activity['message'] ?>
                                            <div class="activity-time">
                                                <i class="far fa-clock me-1"></i> <?= date('d M Y, H:i', strtotime($activity['timestamp'])) ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="activity-card text-center py-4">
                    <h4 class="mb-3">Akses Cepat ke Manajemen Data</h4>
                    <div class="d-flex justify-content-center flex-wrap">
                        <a href="patients.php" class="btn btn-outline-primary m-2"><i class="fas fa-user-friends me-2"></i> Kelola Pasien</a>
                        <a href="appointments.php" class="btn btn-outline-success m-2"><i class="fas fa-calendar-alt me-2"></i> Kelola Janji Temu</a>
                        <a href="doctors.php" class="btn btn-outline-info m-2"><i class="fas fa-user-md me-2"></i> Kelola Dokter</a>
                        <a href="#" class="btn btn-outline-secondary m-2"><i class="fas fa-cog me-2"></i> Pengaturan Aplikasi</a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Admin HealthDoc. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>