<?php
// =========================================================
// Konfigurasi koneksi database
// =========================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'kaligawe');
define('DB_USER', 'root');
define('DB_PASS', '');

$project_root = str_replace('\\', '/', dirname(__DIR__));
$document_root = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$base_url = ($document_root !== '' && str_starts_with($project_root, $document_root))
    ? substr($project_root, strlen($document_root))
    : '';
define('BASE_URL', $base_url);

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}