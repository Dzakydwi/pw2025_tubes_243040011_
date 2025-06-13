<?php
// Pastikan file ini di-include dari profile.php atau file serupa
// Asumsikan session sudah dimulai dan koneksi database tersedia

if (!isset($_FILES['profile_picture'])) {
    $upload_error = "Tidak ada file yang diunggah.";
    return;
}

$target_dir = "../assets/img/"; // Folder tempat menyimpan gambar

// *** Perbaikan dimulai di sini ***
// Pastikan direktori tujuan ada. Jika tidak, buat direktori tersebut.
// 0777 adalah mode izin yang memberikan akses penuh (untuk pengembangan).
// 'true' berarti membuat direktori secara rekursif jika ada parent directory yang belum ada.
if (!is_dir($target_dir)) {
    if (!mkdir($target_dir, 0777, true)) {
        $upload_error = "Gagal membuat direktori unggahan: " . $target_dir;
        return; // Hentikan eksekusi jika direktori tidak bisa dibuat
    }
}
// *** Perbaikan berakhir di sini ***

$file = $_FILES['profile_picture'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileError = $file['error'];
$fileType = $file['type'];

$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowed = array('jpg', 'jpeg', 'png', 'gif');

if (in_array($fileExt, $allowed)) {
    if ($fileError === 0) {
        if ($fileSize < 5000000) { // Max 5MB
            $newFileName = uniqid('', true) . "." . $fileExt;
            $fileDestination = $target_dir . $newFileName;

            if (move_uploaded_file($fileTmpName, $fileDestination)) {
                // Update nama file di database
                // Pastikan $conn dan $user_id sudah didefinisikan sebelum file ini di-include
                // Pastikan juga $user_data sudah didefinisikan jika Anda menggunakan logika penghapusan gambar lama
                if (isset($conn) && isset($user_id)) {
                    $stmt_update_pic = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                    $stmt_update_pic->bind_param("si", $newFileName, $user_id);
                    if ($stmt_update_pic->execute()) {
                        $upload_success = "Foto profil berhasil diunggah!";
                        // Hapus gambar lama jika ada dan bukan default_profile.png
                        if (isset($user_data['profile_picture']) && $user_data['profile_picture'] && $user_data['profile_picture'] !== 'default_profile.png') {
                            $oldFilePath = $target_dir . $user_data['profile_picture'];
                            if (file_exists($oldFilePath)) {
                                unlink($oldFilePath);
                            }
                        }
                    } else {
                        $upload_error = "Gagal memperbarui database: " . $stmt_update_pic->error;
                        unlink($fileDestination); // Hapus file yang sudah terunggah jika update DB gagal
                    }
                    $stmt_update_pic->close();
                } else {
                    $upload_error = "Koneksi database atau ID pengguna tidak tersedia.";
                    unlink($fileDestination); // Hapus file jika DB update tidak bisa dilakukan
                }
            } else {
                $upload_error = "Gagal memindahkan file yang diunggah. Periksa izin direktori.";
            }
        } else {
            $upload_error = "Ukuran file terlalu besar (maks. 5MB).";
        }
    } else {
        $upload_error = "Terjadi kesalahan saat mengunggah file: " . $fileError;
    }
} else {
    $upload_error = "Tipe file tidak diizinkan. Hanya JPG, JPEG, PNG, GIF yang diperbolehkan.";
}
