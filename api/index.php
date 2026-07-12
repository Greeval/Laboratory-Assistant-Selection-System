<?php
// Set working directory to project root
chdir(__DIR__ . '/../');

// Ambil path dari URL
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Arahkan root URL ke index.php
if ($uri === '/' || $uri === '') {
    $uri = '/index.php';
}

$file = __DIR__ . '/..' . $uri;

// Jika file PHP ditemukan di root, eksekusi file tersebut
if (file_exists($file) && is_file($file)) {
    require $file;
} else {
    // Jika tidak ditemukan
    http_response_code(404);
    echo "404 Not Found";
}
