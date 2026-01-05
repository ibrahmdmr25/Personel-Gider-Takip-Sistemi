<?php
require_once 'config.php';

// Oturum değişkenlerini temizle
session_unset();
// Oturumu tamamen sonlandır
session_destroy();

// Giriş sayfasına (login.php) yönlendir
header('Location: login.php');
exit;
?>