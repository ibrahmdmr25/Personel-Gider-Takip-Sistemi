<?php
// Oturumu başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Veritabanı Ayarları
define('DB_HOST', 'localhost'); // Genellikle localhost
define('DB_NAME', 'gider_takip'); // Veritabanı adınızı buraya yazın
define('DB_USER', 'root'); // MySQL kullanıcı adınızı buraya yazın
define('DB_PASS', ''); // MySQL şifrenizi buraya yazın

// Veritabanı Bağlantısı (PDO Kullanımı)
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

/**
 * Oturum kontrolü ve yetkilendirme fonksiyonu
 * @param array $allowed_roles İzin verilen rollerin dizisi
 */
function check_auth($allowed_roles = []) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    if (!empty($allowed_roles) && !in_array($_SESSION['user_role'], $allowed_roles)) {
        // İzin verilmeyen bir role sahipse ana sayfasına yönlendir
        header('Location: dashboard.php');
        exit;
    }
}
?>