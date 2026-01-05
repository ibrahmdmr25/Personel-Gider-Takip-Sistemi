<?php
require_once 'config.php';
// Sadece 'patron' rolüne izin ver
check_auth(['patron']); 

$user_to_delete_id = $_GET['id'] ?? null;
$patron_id = $_SESSION['user_id'];
$redirect_url = 'dashboard.php';

if (!$user_to_delete_id) {
    header('Location: ' . $redirect_url . '?error=Kullanici_ID_Belirtilmedi');
    exit;
}

// 1. Güvenlik: Patronun KENDİ hesabını silmesini engelle
if ($user_to_delete_id == $patron_id) {
    header('Location: ' . $redirect_url . '?error=Kendi_Hesabinizi_Silemezsiniz');
    exit;
}

try {
    // 2. Güvenlik: Silinecek kullanıcının rolünü kontrol et
    $stmt_check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt_check->execute([$user_to_delete_id]);
    $user_to_delete = $stmt_check->fetch();

    if ($user_to_delete && $user_to_delete['role'] === 'patron') {
        // 3. Güvenlik: Diğer 'patron' rollerini silmeyi engelle
        header('Location: ' . $redirect_url . '?error=Diger_Patron_Hesaplarini_Silemezsiniz');
        exit;
    }

    // 4. Silme İşlemi (DELETE)
    $stmt_delete = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt_delete->execute([$user_to_delete_id]);

    if ($stmt_delete->rowCount() > 0) {
        header('Location: ' . $redirect_url . '?message=Kullanici_Basariyla_Silindi');
    } else {
        header('Location: ' . $redirect_url . '?error=Kullanici_Bulunamadi_Veya_Silinemedi');
    }
    exit;

} catch (PDOException $e) {
    // Veritabanı Hatası (Foreign Key Kısıtlaması olabilir)
    // MySQL Hata Kodu 1451: (Cannot delete or update a parent row: a foreign key constraint fails)
    if ($e->getCode() == '23000' || strpos($e->getMessage(), '1451') !== false) {
        header('Location: ' . $redirect_url . '?error=Bu_Kullanici_Silinemez_Cunku_Mevcut_Gider_Veya_Gelir_Kayitlari_Var');
    } else {
        header('Location: ' . $redirect_url . '?error=Silme_Sirasinda_Veritabani_Hatasi_Olustu');
    }
    exit;
}
?>