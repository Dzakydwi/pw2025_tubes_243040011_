<?php
$host = 'localhost';
$username = 'root'; // Sesuaikan dengan username database Anda
$password = 'dzakyjakiDwi15';     // Sesuaikan dengan password database Anda
$dbname = 'health_app_db';

// Buat koneksi
$conn = new mysqli($host, $username, $password, $dbname);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>