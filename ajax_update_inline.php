<?php
session_start();
require_once __DIR__ . '/koneksiweb.php';
require_once __DIR__ . '/csrf_helper.php';

header('Content-Type: application/json');

// Mengizinkan Admin Prodi ATAU Admin Lab untuk menggunakan endpoint inline edit (jika sesuai wewenang)
if (empty($_SESSION['Admin Prodi']['id_user']) && empty($_SESSION['Admin Lab']['id_user'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifikasi manual untuk ajax
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'msg' => 'CSRF token validation failed.']);
        exit();
    }

    $table = isset($_POST['table']) ? $_POST['table'] : '';
    $field = isset($_POST['field']) ? $_POST['field'] : '';
    $value = isset($_POST['value']) ? trim($_POST['value']) : '';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    // Whitelist validasi tabel dan kolom untuk mencegah SQLi di identifier
    $allowed_updates = [
        'mahasiswa' => ['nama_mahasiswa', 'fakultas', 'jurusan']
    ];

    if ($id > 0 && array_key_exists($table, $allowed_updates) && in_array($field, $allowed_updates[$table])) {
        
        $query = "UPDATE $table SET $field = ? WHERE id_user = ?";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "si", $value, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Database update failed']);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Invalid parameters']);
    }
} else {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid method']);
}
?>
