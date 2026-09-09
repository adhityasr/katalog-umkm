<?php
// =========================================================
// Konfigurasi koneksi database
// =========================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'kaligawe');
define('DB_USER', 'root');
define('DB_PASS', '');

// Auto-detect BASE_URL — support localhost & hosting live https://pasar-kaligawe.desa.id
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Jika sudah di-hosting (bukan localhost), BASE_URL = '' (di root domain)
if (!str_contains($host, 'localhost') && !str_contains($host, '127.0.0.1')) {
    $base_url = ''; // hosting di root https://pasar-kaligawe.desa.id/
} else {
    $project_root = str_replace('\\', '/', dirname(__DIR__));
    $document_root = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $base_url = ($document_root !== '' && str_starts_with($project_root, $document_root))
        ? substr($project_root, strlen($document_root))
        : '';
}
define('BASE_URL', $base_url);
define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $host . BASE_URL);

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