<?php
include '../config/database.php';
include 'includes/header.php';

// Periksa apakah admin sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$appointments = [];
$filter = $_GET['filter'] ?? 'all'; // Default filter for admin page

$sql = "
    SELECT a.id, a.tanggal, a.waktu, a.dokter, u.nama as patient_name
    FROM appointments a
    JOIN users u ON a.user_id = u.id
";

if ($filter === 'upcoming') {
    $sql .= " WHERE a.tanggal >= CURDATE() ORDER BY a.tanggal ASC, a.waktu ASC";
} elseif ($filter === 'past') {
    $sql .= " WHERE a.tanggal < CURDATE() ORDER BY a.tanggal DESC, a.waktu DESC";
} else {
    // 'all' or invalid filter
    $sql .= " ORDER BY a.tanggal DESC, a.waktu DESC";
}

$stmt_appointments = $conn->prepare($sql);

if ($stmt_appointments) {
    $stmt_appointments->execute();
    $result_appointments = $stmt_appointments->get_result();
    while ($row = $result_appointments->fetch_assoc()) {
        $appointments[] = $row;
    }
    $stmt_appointments->close();
}

$page_title = "Manajemen Janji Temu - Admin HealthDoc";
// include 'includes/header.php';
?>

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

<div class="container my-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Manajemen Janji Temu</h4>
            <div class="d-flex align-items-center">
                <a href="add_appointment.php" class="btn btn-success me-3"><i class="fas fa-calendar-plus me-2"></i> Tambah Janji Temu</a>
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        Filter: <?= ucfirst($filter) ?>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                        <li><a class="dropdown-item <?= ($filter === 'all') ? 'active' : '' ?>" href="appointments.php?filter=all">Semua Janji Temu</a></li>
                        <li><a class="dropdown-item <?= ($filter === 'upcoming') ? 'active' : '' ?>" href="appointments.php?filter=upcoming">Janji Temu Mendatang</a></li>
                        <li><a class="dropdown-item <?= ($filter === 'past') ? 'active' : '' ?>" href="appointments.php?filter=past">Janji Temu Terlewat</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($appointments)) : ?>
                <div class="alert alert-info text-center" role="alert">
                    Tidak ada janji temu yang ditemukan untuk filter ini.
                </div>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Pasien</th>
                                <th scope="col">Dokter</th>
                                <th scope="col">Tanggal</th>
                                <th scope="col">Waktu</th>
                                <th scope="col" class="text-center">Aksi</th>
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
                                        <?php
                                            // Create a combined DateTime object for comparison
                                            $appointment_datetime_str = $appt['tanggal'] . ' ' . $appt['waktu'];
                                            $appointment_datetime = DateTime::createFromFormat('Y-m-d H:i:s', $appointment_datetime_str);
                                            $current_datetime = new DateTime(); // Current server time
                                            
                                            // Compare full datetime to determine if it's upcoming
                                            $is_upcoming = ($appointment_datetime && $appointment_datetime >= $current_datetime);
                                        ?>
                                        <?php if ($is_upcoming) : ?>
                                            <a href="edit_appointment.php?id=<?= htmlspecialchars($appt['id']) ?>" class="btn btn-edit btn-action me-2" title="Edit Janji Temu"><i class="fas fa-edit"></i></a>
                                            <a href="delete_appointment.php?id=<?= htmlspecialchars($appt['id']) ?>" class="btn btn-delete btn-action" title="Hapus Janji Temu" onclick="return confirm('Apakah Anda yakin ingin menghapus janji temu ini?');"><i class="fas fa-trash-alt"></i></a>
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

<?php
include 'includes/footer.php';
$conn->close();
?>