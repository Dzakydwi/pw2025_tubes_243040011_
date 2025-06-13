<?php
session_start();
include '../config/database.php'; // Sesuaikan path jika berbeda

// Periksa apakah pengguna sudah login dan role-nya 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Pengguna'; // Default jika nama tidak ada di sesi

$success_message = '';
$error_message = '';
$upload_success = '';
$upload_error = '';

// Ambil data profil pengguna saat ini
$user_data = [];
$stmt_profile = $conn->prepare("SELECT nama, email, profile_picture FROM users WHERE id = ?");
if ($stmt_profile) {
    $stmt_profile->bind_param("i", $user_id);
    $stmt_profile->execute();
    $result_profile = $stmt_profile->get_result();
    if ($result_profile->num_rows > 0) {
        $user_data = $result_profile->fetch_assoc();
        $_SESSION['user_name'] = $user_data['nama']; // Perbarui sesi dengan nama terbaru
    } else {
        $error_message = "Data pengguna tidak ditemukan.";
    }
    $stmt_profile->close();
} else {
    $error_message = "Gagal mengambil data profil: " . $conn->error;
}

// Tangani unggah foto profil
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    // Pastikan user_id dan $conn tersedia untuk upload_handler.php
    // Include the handler file
    include 'upload_handler.php'; // Pastikan path ini benar relatif dari profile.php
}

// Tangani pembaruan data profil (selain foto)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $new_nama = trim($_POST['nama'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi input
    if (empty($new_nama) || empty($new_email)) {
        $error_message = "Nama dan Email wajib diisi.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid.";
    } else {
        // Cek apakah email sudah terdaftar oleh user lain
        $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        if ($stmt_check_email) {
            $stmt_check_email->bind_param("si", $new_email, $user_id);
            $stmt_check_email->execute();
            $result_check_email = $stmt_check_email->get_result();
            if ($result_check_email->num_rows > 0) {
                $error_message = "Email sudah terdaftar oleh pengguna lain.";
            }
            $stmt_check_email->close();
        } else {
            $error_message = "Gagal menyiapkan pengecekan email: " . $conn->error;
        }

        if (empty($error_message)) {
            $update_sql = "UPDATE users SET nama = ?, email = ?";
            $update_params = "ss";
            $params_array = [$new_nama, $new_email];

            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                    $error_message = "Password minimal 6 karakter.";
                } elseif ($new_password !== $confirm_password) {
                    $error_message = "Konfirmasi password tidak cocok.";
                } else {
                    // Hash password sebelum menyimpan
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_sql .= ", password = ?";
                    $update_params .= "s";
                    $params_array[] = $hashed_password;
                }
            }

            if (empty($error_message)) {
                $update_sql .= " WHERE id = ?";
                $update_params .= "i";
                $params_array[] = $user_id;

                $stmt_update = $conn->prepare($update_sql);
                if ($stmt_update) {
                    $stmt_update->bind_param($update_params, ...$params_array);
                    if ($stmt_update->execute()) {
                        $success_message = "Profil berhasil diperbarui!";
                        // Perbarui data di $user_data dan sesi setelah berhasil update
                        $user_data['nama'] = $new_nama;
                        $user_data['email'] = $new_email;
                        $_SESSION['user_name'] = $new_nama; // Perbarui nama di sesi
                    } else {
                        $error_message = "Gagal memperbarui profil: " . $stmt_update->error;
                    }
                    $stmt_update->close();
                } else {
                    $error_message = "Gagal menyiapkan statement update: " . $conn->error;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Klinik Sehatku</title>
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
            /* Pastikan path gambar latar belakang benar jika ingin diterapkan di sini */
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

        /* Card/Panel untuk Form */
        .profile-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            background-color: white;
            padding: 25px;
            max-width: 700px; /* Lebar form */
            margin-left: auto;
            margin-right: auto;
            text-align: center; /* Untuk foto profil */
        }
        .profile-card .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--heading-color);
            text-align: center;
        }
        .profile-picture-container {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 20px auto;
            border: 3px solid var(--primary-color);
            background-color: #eee; /* Fallback for no image */
        }
        .profile-picture-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
        .btn-secondary-custom {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        .btn-secondary-custom:hover {
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
                        <a class="nav-link" href="my_appointments.php">Janji Temu Saya</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="profile.php">Profil Saya</a>
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
        <h1 class="mb-5 text-center">Profil Saya</h1>

        <div class="profile-card">
            <div class="card-header">Kelola Informasi Profil Anda</div>
            <div class="card-body">
                <?php if ($success_message) : ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $success_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error_message) : ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if ($upload_success) : ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $upload_success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if ($upload_error) : ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $upload_error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($user_data)) : ?>
                    <form action="profile.php" method="POST" enctype="multipart/form-data" class="mb-4">
                        <div class="profile-picture-container">
                            <?php
                                // Path ke gambar profil. Gunakan default jika tidak ada atau null
                                $profile_pic_path = '../assets/img/' . htmlspecialchars($user_data['profile_picture'] ?? 'default_profile.png');
                                // Periksa apakah file gambar ada, jika tidak gunakan placeholder
                                if (!file_exists($profile_pic_path) || empty($user_data['profile_picture'])) {
                                    $profile_pic_path = '../assets/img/default_profile.png';
                                }
                            ?>
                            <img src="<?= $profile_pic_path ?>" alt="Foto Profil">
                        </div>
                        <div class="mb-3 d-flex justify-content-center">
                            <input type="file" class="form-control w-auto me-2" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif">
                            <button type="submit" class="btn btn-primary">Unggah Foto</button>
                        </div>
                        <small class="text-muted d-block mb-3">Maks. 2MB (JPG, JPEG, PNG, GIF)</small>
                    </form>

                    <form action="profile.php" method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="mb-3 text-start">
                            <label for="nama" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($user_data['nama'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3 text-start">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3 text-start">
                            <label for="password" class="form-label">Password Baru (kosongkan jika tidak ingin mengubah)</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        <div class="mb-3 text-start">
                            <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-submit">Simpan Perubahan Profil</button>
                        </div>
                    </form>
                <?php else : ?>
                    <p class="text-center">Tidak dapat memuat data profil Anda.</p>
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