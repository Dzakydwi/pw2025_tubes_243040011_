<?php
session_start();
include '../config/database.php';

// Periksa apakah admin sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$appointments = [];
// PERBAIKAN TERBARU: Menghapus 'a.status' dari SELECT statement karena kolom ini juga tidak ada.
// Sekarang query hanya mengambil kolom yang diyakini ada: id, tanggal, waktu, dokter, dan nama pasien.
$stmt_appointments = $conn->prepare("
    SELECT a.id, a.tanggal, a.waktu, a.dokter, u.nama as patient_name
    FROM appointments a
    JOIN users u ON a.user_id = u.id
    ORDER BY a.tanggal DESC, a.waktu DESC
");

if ($stmt_appointments) {
    $stmt_appointments->execute();
    $result_appointments = $stmt_appointments->get_result();
    while ($row = $result_appointments->fetch_assoc()) {
        $appointments[] = $row;
    }
    $stmt_appointments->close();
}

// Logika untuk CRUD atau update status (yang mungkin perlu ditambahkan jika status ada) akan ditambahkan di sini atau di file terpisah.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Janji Temu - HealthDoc</title>
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

        /* Navbar Admin (reused from dashboard) */
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

        /* Card/Panel untuk Tabel */
        .data-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            background-color: white;
            padding: 25px;
        }
        .data-card .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--heading-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Table Styling */
        .table-custom {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .table-custom thead {
            background-color: var(--primary-color);
            color: white;
        }
        .table-custom th {
            padding: 15px;
            border: none;
            font-weight: 600;
        }
        .table-custom td {
            padding: 12px 15px;
            vertical-align: middle;
            border-color: #f1f1f1;
        }
        .table-custom tbody tr:hover {
            background-color: #f5f5f5;
        }

        /* Action Buttons */
        .btn-action {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-right: 5px;
            transition: all 0.3s ease;
        }
        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .btn-edit {
            background-color: #ffc107; /* warning */
            color: white;
            border-color: #ffc107;
        }
        .btn-edit:hover {
            background-color: #e0a800;
            border-color: #e0a800;
        }
        .btn-delete {
            background-color: #dc3545; /* danger */
            color: white;
            border-color: #dc3545;
        }
        .btn-delete:hover {
            background-color: #c82333;
            border-color: #c82333;
        }
        .btn-info-status { /* Untuk tombol update status */
            background-color: #17a2b8; /* info */
            color: white;
            border-color: #17a2b8;
        }
        .btn-info-status:hover {
            background-color: #117a8b;
            border-color: #117a8b;
        }
        .btn-add-new {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            border-radius: 30px;
            padding: 10px 25px;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        .btn-add-new:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            transform: translateY(-2px);
        }

        /* Badge status (kelas ini tetap ada jika sewaktu-waktu Anda menambahkan kolom status) */
        .badge-status {
            font-size: 0.85em;
            padding: 0.4em 0.8em;
            border-radius: 0.5rem;
            font-weight: 600;
        }
        .badge-pending { background-color: #ffc107; color: #fff; } /* warning */
        .badge-confirmed { background-color: #28a745; color: #fff; } /* success */
        .badge-completed { background-color: #6f42c1; color: #fff; } /* purple */
        .badge-cancelled { background-color: #dc3545; color: #fff; } /* danger */


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
            <a class="navbar-brand" href="index.php">HealthDoc</a>
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
                        <a class="nav-link active" aria-current="page" href="appointments.php">Manajemen Janji Temu</a>
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

    <div class="container main-content-container">
        <h1 class="mb-5 text-center">Manajemen Janji Temu</h1>

        <div class="data-card">
            <div class="card-header">
                Daftar Janji Temu
                <a href="add_appointment.php" class="btn btn-add-new btn-sm"><i class="fas fa-calendar-plus me-2"></i> Tambah Janji Temu</a>
            </div>
            <div class="card-body">
                <?php if (empty($appointments)) : ?>
                    <div class="alert alert-info text-center" role="alert">
                        Belum ada data janji temu.
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-custom">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Pasien</th>
                                    <th>Dokter</th>
                                    <th>Tanggal</th>
                                    <th>Waktu</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appt) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($appt['id']) ?></td>
                                    <td><?= htmlspecialchars($appt['patient_name']) ?></td>
                                    <td><?= htmlspecialchars($appt['dokter']) ?></td>
                                    <td><?= date('d M Y', strtotime($appt['tanggal'])) ?></td>
                                    <td><?= date('H:i', strtotime($appt['waktu'])) ?></td>
                                    <td class="text-center">
                                        <a href="edit_appointment.php?id=<?= $appt['id'] ?>" class="btn btn-edit btn-action" title="Edit Janji Temu"><i class="fas fa-edit"></i></a>
                                        <a href="delete_appointment.php?id=<?= $appt['id'] ?>" class="btn btn-delete btn-action" title="Hapus Janji Temu" onclick="return confirm('Apakah Anda yakin ingin menghapus janji temu ini?');"><i class="fas fa-trash-alt"></i></a>
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
            <p>&copy; 2025 HealthDoc. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>