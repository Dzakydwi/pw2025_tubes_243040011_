<?php
// user/includes/footer.php
?>
    </div> <footer class="footer">
        <div class="container">
            <p>&copy; 2025 HealthDoc. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Pastikan koneksi database ditutup jika dibuka di halaman utama
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>