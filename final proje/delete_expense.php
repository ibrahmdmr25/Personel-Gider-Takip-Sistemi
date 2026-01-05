<?php
require_once 'config.php';
// Sadece 'personel' rolüne izin ver
check_auth(['personel']); 

$user_id = $_SESSION['user_id'];
$expense_id = $_GET['id'] ?? null;

if (!$expense_id) {
    header('Location: dashboard.php?error=Gider_ID_Belirtilmedi');
    exit;
}

try {
    // DELETE sorgusu (DELETE)
    // Silme işleminden önce kaydın oturumdaki kullanıcıya ait olduğu ve durumunun 'Bekliyor' olduğu kontrol edilir.
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ? AND status = 'Bekliyor'");
    $stmt->execute([$expense_id, $user_id]);
    
    // Kaç satır etkilendiğini kontrol et
    if ($stmt->rowCount() > 0) {
        header('Location: dashboard.php?message=Gider_Başarıyla_Silindi');
    } else {
        header('Location: dashboard.php?error=Silme_Yetkisi_Yok_Veya_Durum_Beklemede_Değil');
    }
    exit;

} catch (PDOException $e) {
    // Hata durumunda da dashboard'a yönlendir
    header('Location: dashboard.php?error=Silme_Sırasında_Hata_Oluştu');
    exit;
}
?>