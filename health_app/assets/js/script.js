// Anda bisa menambahkan JavaScript kustom di sini
// Contoh: script untuk mengatur tanggal minimum di input date
document.addEventListener("DOMContentLoaded", function () {
  const dateInput = document.getElementById("tanggal");
  if (dateInput) {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, "0");
    const day = String(today.getDate()).padStart(2, "0");
    const minDate = `${year}-${month}-${day}`;
    dateInput.setAttribute("min", minDate);
  }

  // Untuk menutup alert secara otomatis setelah beberapa detik (opsional)
  const alerts = document.querySelectorAll(".alert");
  alerts.forEach(function (alert) {
    if (alert.classList.contains("alert-success") || alert.classList.contains("alert-danger")) {
      setTimeout(function () {
        const bootstrapAlert = bootstrap.Alert.getInstance(alert);
        if (bootstrapAlert) {
          bootstrapAlert.close();
        } else {
          alert.remove(); // Fallback if Bootstrap JS not loaded or not an Alert instance
        }
      }, 5000); // Tutup setelah 5 detik
    }
  });
});
