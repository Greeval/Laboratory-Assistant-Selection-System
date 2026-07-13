<?php
$host = getenv('DB_HOST') ?: 'mysql-ead0258-greeval-22c.c.aivencloud.com';
$port = getenv('DB_PORT') ?: 18867;
$user = getenv('DB_USER') ?: 'avnadmin';
$pass = getenv('DB_PASS');
$db   = getenv('DB_NAME') ?: 'defaultdb';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    echo "<h1>Database Importer</h1>";
    echo "Connecting to Aiven MySQL...<br>";
    $koneksi = mysqli_connect($host, $user, $pass, $db, $port);
    echo "Connected successfully!<br>";

    echo "Reading database.sql...<br>";
    // Vercel path is relative to api folder
    $sql = file_get_contents(__DIR__ . '/../database.sql');
    
    if (!$sql) {
        die("Could not read database.sql<br>");
    }

    echo "Executing SQL script...<br>";
    if (mysqli_multi_query($koneksi, $sql)) {
        do {
            /* store first result set */
            if ($result = mysqli_store_result($koneksi)) {
                mysqli_free_result($result);
            }
        } while (mysqli_more_results($koneksi) && mysqli_next_result($koneksi));
        echo "<b>Database imported successfully! You can now login.</b><br>";
    } else {
        echo "Error executing script: " . mysqli_error($koneksi) . "<br>";
    }
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "<br>";
}
