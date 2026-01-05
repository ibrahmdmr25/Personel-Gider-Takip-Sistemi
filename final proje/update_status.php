<?php
require_once 'config.php';
// Sadece 'muhasebeci' rolüne izin ver
check_auth(['muhasebeci']); 

$expense_id = $_GET['id'] ?? null;
$new_status = $_GET['status'] ?? null;
$redirect_url = 'dashboard.php'; 
$muhasebeci_id = $_SESSION['user_id']; // İşlemi yapan muhasebecinin ID'si

if (!$expense_id || !in_array($new_status, ['Onaylandı', 'Reddedildi'])) {
    header('Location: ' . $redirect_url . '?error=Gecersiz_Parametreler');
    exit;
}

try {
    // 1. UPDATE sorgusu (Giderin durumunu güncelle)
    $stmt = $pdo->prepare("UPDATE expenses SET status = ? WHERE id = ? AND status = 'Bekliyor'");
    $stmt->execute([$new_status, $expense_id]);
    
    if ($stmt->rowCount() > 0) {
        // 2. 💡 YENİ EKLEME: Log kaydı oluştur (INSERT)
        $log_stmt = $pdo->prepare("INSERT INTO logs (expense_id, action_user_id, action_type) VALUES (?, ?, ?)");
        $log_stmt->execute([$expense_id, $muhasebeci_id, $new_status]);
        
        header('Location: ' . $redirect_url . '?message=Durum_Başarıyla_Güncellendi');
    } else {
        header('Location: ' . $redirect_url . '?error=Durum_Güncellenemedi_Cunku_Zaten_Islem_Gormustu');
    }
    exit;

} catch (PDOException $e) {
    // Veritabanı hatası durumunda hata mesajıyla yönlendir
    header('Location: ' . $redirect_url . '?error=Durum_Güncelleme_Sırasında_Hata_Oluştu');
    exit;
}
?>