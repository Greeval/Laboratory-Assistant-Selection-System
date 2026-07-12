<?php
/**
 * Konfigurasi Koneksi Database (mysqli)
 * Mode error: throw exception agar bug query langsung kelihatan saat development.
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host     = getenv("DB_HOST") ?: "localhost";
$username = getenv("DB_USER") ?: "root";
$password = getenv("DB_PASS") ?: "";
$database = getenv("DB_NAME") ?: "web_seleksi";

try {
    $koneksi = mysqli_connect($host, $username, $password, $database);
    mysqli_set_charset($koneksi, "utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Koneksi gagal. Silakan coba beberapa saat lagi.");
}

// ---------------------------------------------------------
// FITUR AUTO-LOGOUT KARENA INAKTIFITAS (1 JAM)
// ---------------------------------------------------------
// Cek apakah ada user yang sedang login dari role apapun
$is_logged_in = isset($_SESSION['Mahasiswa']) || isset($_SESSION['Admin Lab']) || isset($_SESSION['Admin Prodi']);

if ($is_logged_in) {
    $timeout_duration = 3600; // 1 jam (dalam detik)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        // Jika sudah melewati batas waktu, logout otomatis
        session_unset();
        session_destroy();
        header("Location: login.php?status=timeout");
        exit();
    }
    // Update waktu aktivitas terakhir
    $_SESSION['last_activity'] = time();
}
?>