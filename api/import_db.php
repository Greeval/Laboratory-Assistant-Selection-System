<?php
$host = getenv('DB_HOST') ?: 'mysql-ead0258-greeval-22c.c.aivencloud.com';
$port = getenv('DB_PORT') ?: 18867;
$user = getenv('DB_USER') ?: 'avnadmin';
$pass = getenv('DB_PASS');
$db   = getenv('DB_NAME') ?: 'defaultdb';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    echo "<h2>Database Importer</h2>";
    echo "Connecting to Aiven MySQL...<br>";
    $koneksi = mysqli_connect($host, $user, $pass, $db, $port);
    echo "✅ Connected successfully!<br><br>";

    // Import web_seleksi.sql (schema + data)
    $sqlFile = __DIR__ . '/web_seleksi.sql';
    echo "Reading web_seleksi.sql...<br>";
    
    if (!file_exists($sqlFile)) {
        die("❌ File web_seleksi.sql not found at: $sqlFile<br>");
    }
    
    $sql = file_get_contents($sqlFile);
    if (!$sql) {
        die("❌ Could not read web_seleksi.sql<br>");
    }

    echo "Executing SQL schema + data...<br>";
    if (mysqli_multi_query($koneksi, $sql)) {
        do {
            if ($result = mysqli_store_result($koneksi)) {
                mysqli_free_result($result);
            }
        } while (mysqli_more_results($koneksi) && mysqli_next_result($koneksi));
        echo "✅ Schema imported!<br><br>";
    } else {
        echo "❌ Error: " . mysqli_error($koneksi) . "<br>";
    }


    echo "<br>Login credentials:<br>";
    echo "- Admin Prodi: <b>adminprodi</b> / password<br>";
    echo "- Admin Lab: <b>adminlab</b> / password<br>";
    echo "- Mahasiswa: <b>123456789</b> / password<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
