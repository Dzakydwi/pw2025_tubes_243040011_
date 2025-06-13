<?php
session_start();
include '../config/database.php';

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
    $sql .= " AND (tanggal > CURDATE() OR (tanggal = CURDATE() AND waktu >= CURTIME())) ORDER BY tanggal ASC, waktu ASC";
} elseif ($filter === 'past') {
    $sql .= " AND (tanggal < CURDATE() OR (tanggal = CURDATE() AND waktu < CURTIME()))  ORDER BY tanggal DESC, waktu DESC";
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

// Set judul halaman
$page_title = "Janji Temu Saya";
// --- INCLUDE HEADER ---
include 'includes/header.php';
?>

<div class="container">
    <h1 class="my-4 text-center">Janji Temu Saya</h1>

    <?php if (isset($_SESSION['message'])) : ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>

    <div class="card shadow-lg p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="card-title mb-0">Daftar Janji Temu</h4>
            <a href="create_appointment.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus-circle me-2"></i> Buat Janji Temu Baru
            </a>
        </div>

        <div class="mb-3">
            <label for="filter" class="form-label">Filter Janji Temu:</label>
            <select class="form-select w-auto" id="filter" onchange="window.location.href='my_appointments.php?filter=' + this.value;">
                <option value="all" <?= ($filter === 'all') ? 'selected' : '' ?>>Semua Janji Temu</option>
                <option value="upcoming" <?= ($filter === 'upcoming') ? 'selected' : '' ?>>Janji Temu Mendatang</option>
                <option value="past" <?= ($filter === 'past') ? 'selected' : '' ?>>Janji Temu Terlewat</option>
            </select>
        </div>

        <div class="table-responsive">
            <?php if (empty($appointments)) : ?>
                <div class="alert alert-info text-center" role="alert">
                    Belum ada janji temu untuk kategori ini.
                </div>
            <?php else : ?>
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Dokter</th>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Waktu</th>
                            <th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($appointments as $app) : ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($app['dokter']) ?></td>
                                <td>
                                    <?php
                                    $tanggal_obj = DateTime::createFromFormat('Y-m-d', $app['tanggal']);
                                    echo $tanggal_obj ? htmlspecialchars($tanggal_obj->format('d F Y')) : 'N/A';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $waktu_obj = DateTime::createFromFormat('H:i:s', $app['waktu']);
                                    echo $waktu_obj ? htmlspecialchars($waktu_obj->format('H:i')) : 'N/A';
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    // Create a combined DateTime object for comparison
                                    $appointment_datetime_str = $app['tanggal'] . ' ' . $app['waktu'];
                                    $appointment_datetime = DateTime::createFromFormat('Y-m-d H:i:s', $appointment_datetime_str);
                                    $current_datetime = new DateTime(); // Current server time

                                    $is_upcoming = ($appointment_datetime && $appointment_datetime >= $current_datetime);
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
            <?php endif; ?>
        </div>
    </div>

    <?php
    // --- INCLUDE FOOTER ---
    include 'includes/footer.php';
    ?>