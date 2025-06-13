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

$filter = $_GET['filter'] ?? 'all'; // Default filter adalah 'all'

$sql = "SELECT id, dokter, tanggal, waktu FROM appointments WHERE user_id = ?";
$params = [$user_id];
$types = "i";

if ($filter === 'upcoming') {
    $sql .= " AND tanggal >= CURDATE() ORDER BY tanggal ASC, waktu ASC";
} elseif ($filter === 'past') {
    $sql .= " AND tanggal < CURDATE() ORDER BY tanggal DESC, waktu DESC";
} else {
    // 'all' atau filter tidak valid
    $sql .= " ORDER BY tanggal DESC, waktu DESC";
}

$appointments = [];
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    $stmt->close();
} else {
    $error = "Gagal mengambil data janji temu: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Janji Temu Saya - Klinik Sehatku</title>
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
            background-color: var(--light-bg); /* Default fallback color */
            line-height: 1.7;
            /* Tambahkan properti background gambar jika ingin ada di sini juga */
            /* background-image: url('../../assets/img/background_klinik.jpg'); */
            /* background-size: cover; */
            /* background-position: center center; */
            /* background-attachment: fixed; */
            /* background-repeat: no-repeat; */
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
            padding-top: 50px; /* Sesuaikan dengan tinggi navbar */
            padding-bottom: 50px;
        }

        /* Card/Table */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            background-color: white;
            padding: 25px;
        }
        .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--heading-color);
        }

        .table thead {
            background-color: var(--primary-color);
            color: white;
        }
        .table thead th {
            border-bottom: none;
            padding: 12px 15px;
        }
        .table tbody tr:hover {
            background-color: #f2f2f2;
        }
        .table tbody td {
            padding: 10px 15px;
            vertical-align: middle;
        }
        .btn-action {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .btn-edit { background-color: #ffc107; border-color: #ffc107; color: white; }
        .btn-edit:hover { background-color: #e0a800; border-color: #e0a800; }
        .btn-delete { background-color: #dc3545; border-color: #dc3545; color: white; }
        .btn-delete:hover { background-color: #c82333; border-color: #c82333; }

        /* Filter buttons */
        .filter-buttons .btn {
            margin-right: 10px;
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
                        <a class="nav-link" href="appointments.php">Semua Janji Temu</a>
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
        <h1 class="mb-4">Janji Temu Saya </h1>
        <p class="lead mb-4">Daftar janji temu Anda, <?= htmlspecialchars($user_name) ?>.</p>

        <?php if (isset($error)) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-calendar-check me-2"></i> Daftar Janji Temu</span>
                <div class="filter-buttons">
                    <a href="appointments.php?filter=all" class="btn btn-sm <?= ($filter === 'all') ? 'btn-primary' : 'btn-outline-primary' ?>">Semua</a>
                    <a href="appointments.php?filter=upcoming" class="btn btn-sm <?= ($filter === 'upcoming') ? 'btn-primary' : 'btn-outline-primary' ?>">Mendatang</a>
                    <a href="appointments.php?filter=past" class="btn btn-sm <?= ($filter === 'past') ? 'btn-primary' : 'btn-outline-primary' ?>">Lewat</a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($appointments)) : ?>
                    <div class="alert alert-info text-center" role="alert">
                        Anda belum memiliki janji temu yang tercatat.
                        <br>
                        <a href="create_appointment.php" class="btn btn-primary mt-3"><i class="fas fa-plus-circle me-2"></i> Buat Janji Temu Baru</a>
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Dokter</th>
                                    <th>Tanggal</th>
                                    <th>Waktu</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; ?>
                                <?php foreach ($appointments as $app) : ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($app['dokter'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php
                                                $tanggal_obj = !empty($app['tanggal']) ? new DateTime($app['tanggal']) : null;
                                                echo $tanggal_obj ? htmlspecialchars($tanggal_obj->format('d F Y')) : 'N/A';
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                $waktu_obj = !empty($app['waktu']) ? new DateTime($app['waktu']) : null;
                                                echo $waktu_obj ? htmlspecialchars($waktu_obj->format('H:i')) : 'N/A';
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                                $is_upcoming = ($tanggal_obj && $tanggal_obj->format('Y-m-d') >= date('Y-m-d'));
                                            ?>
                                            <?php if ($is_upcoming) : ?>
                                                <a href="edit_appointment.php?id=<?= htmlspecialchars($app['id']) ?>" class="btn btn-edit btn-action me-2"><i class="fas fa-edit"></i> Edit</a>
                                                <a href="delete_appointment.php?id=<?= htmlspecialchars($app['id']) ?>" class="btn btn-delete btn-action" onclick="return confirm('Apakah Anda yakin ingin membatalkan janji temu ini?');"><i class="fas fa-trash-alt"></i> Batalkan</a>
                                            <?php else : ?>
                                                <span class="badge bg-secondary">Selesai/Lewat</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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