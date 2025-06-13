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

// Set judul halaman
$page_title = "Janji Temu Saya";
// --- INCLUDE HEADER ---
include 'includes/header.php';
?>

    <h1 class="mb-4 text-center">Janji Temu Saya</h1>
    <p class="lead mb-4 text-center">Daftar janji temu Anda, <?= htmlspecialchars($user_name) ?>.</p>

    <?php if (isset($error)) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="table-card"> <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-calendar-check me-2"></i> Daftar Janji Temu</span>
            <div class="filter-buttons">
                <a href="my_appointments.php?filter=all" class="btn btn-sm <?= ($filter === 'all') ? 'btn-primary' : 'btn-outline-primary' ?>">Semua</a>
                <a href="my_appointments.php?filter=upcoming" class="btn btn-sm <?= ($filter === 'upcoming') ? 'btn-primary' : 'btn-outline-primary' ?>">Mendatang</a>
                <a href="my_appointments.php?filter=past" class="btn btn-sm <?= ($filter === 'past') ? 'btn-primary' : 'btn-outline-primary' ?>">Lewat</a>
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

<?php
// --- INCLUDE FOOTER ---
include 'includes/footer.php';
?>