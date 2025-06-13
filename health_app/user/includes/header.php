<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}   

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Klinik Sehatku'; ?></title> <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #007bff; /* Biru */
            --secondary-color: #6c757d; /* Abu-abu */
            --light-bg: #f8f9fa; /* Latar belakang terang */
            --dark-bg: #2c3e50; /* Latar belakang gelap */
            --text-color: #333; /* Warna teks umum */
            --heading-color: #2c3e50; /* Warna heading */
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            background-color: var(--light-bg); /* Default fallback color */
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
        .user-navbar .nav-link:hover,
        .user-navbar .nav-link.active { /* Tambahkan .active untuk penanda halaman saat ini */
            color: white !important;
        }

        /* Container utama */
        .main-content-container {
            padding-top: 50px; /* Sesuaikan dengan tinggi navbar */
            padding-bottom: 50px;
        }

        /* Dashboard-specific styles */
        .dashboard-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            background-color: white;
            padding: 25px;
        }
        .card-header {
            background-color: white; /* Make card header background white */
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--heading-color);
        }
        .card.bg-primary {
            background-color: var(--primary-color) !important; /* Pastikan warna primer Bootstrap */
            color: white;
        }
        .card.bg-success {
            background-color: var(--success-color) !important; /* Pastikan warna sukses Bootstrap */
            color: white;
        }

        /* Styles for forms and tables */
        .profile-card, .form-card, .table-card { /* Tambahkan .form-card, .table-card untuk keseragaman */
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            background-color: white;
            padding: 25px;
        }
        .profile-card .card-header, .form-card .card-header, .table-card .card-header {
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
            background-color: #eee;
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

        /* Table styles (for appointments, etc.) */
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
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg user-navbar fixed-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">HealthDoc</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavUser" aria-controls="navbarNavUser" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavUser">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'create_appointment.php') ? 'active' : ''; ?>" href="create_appointment.php">Buat Janji Temu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'my_appointments.php' || basename($_SERVER['PHP_SELF']) == 'appointments.php') ? 'active' : ''; ?>" href="my_appointments.php">Janji Temu Saya</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>" href="profile.php">Profil Saya</a>
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