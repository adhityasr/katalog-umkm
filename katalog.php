<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
// Redirect permanen ke landing di index.php (best practice)
header("Location: " . BASE_URL . "/index.php#katalog", true, 301);
exit;
