<?php
require_once 'config.php';
// Sadece 'personel' rolüne izin ver
check_auth(['personel']); 

$user_id = $_SESSION['user_id'];
$expense_id = $_GET['id'] ?? null;
$message = '';
$error = '';
$expense = null;

if (!$expense_id) {
    header('Location: dashboard.php');
    exit;
}

// 1. Mevcut Gideri Çekme (Yetki Kontrollü READ)
try {
    // Sadece oturumdaki kullanıcının giderini ve durumu 'Bekliyor' olanı çek
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ? AND user_id = ? AND status = 'Bekliyor'");
    $stmt->execute([$expense_id, $user_id]);
    $expense = $stmt->fetch();

    if (!$expense) {
        $error = "Gider bulunamadı veya düzenleme yetkiniz yok (Onaylanmış veya Reddedilmiş olabilir).";
    }
} catch (PDOException $e) {
    $error = "Hata oluştu: " . $e->getMessage();
}


// 2. Form Gönderimi (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $expense) {
    $description = trim($_POST['description'] ?? '');
    $amount = filter_var($_POST['amount'] ?? '', FILTER_VALIDATE_FLOAT);
    $category = $_POST['category'] ?? '';
    $expense_date = $_POST['expense_date'] ?? '';

    if (empty($description) || $amount === false || $amount <= 0 || empty($category) || empty($expense_date)) {
        $error = "Lütfen tüm alanları doğru şekilde doldurunuz.";
    } else {
        try {
            // UPDATE sorgusu (UPDATE)
            $stmt = $pdo->prepare("UPDATE expenses SET description = ?, amount = ?, category = ?, expense_date = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$description, $amount, $category, $expense_date, $expense_id, $user_id]);
            
            $message = "Gider kaydınız başarıyla güncellendi.";
            // Güncel veriyi formda göstermek için tekrar çek
            $expense['description'] = $description;
            $expense['amount'] = $amount;
            $expense['category'] = $category;
            $expense['expense_date'] = $expense_date;
            
        } catch (PDOException $e) {
            $error = "Gider güncellenirken bir hata oluştu: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <title>Gider Düzenle</title>
    <style> /* add_expense.php'deki stilin aynısı */
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 600px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type="text"], input[type="number"], input[type="date"], select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; box-sizing: border-box; }
        button { background-color: #007bff; color: white; padding: 10px 15px; border: none; cursor: pointer; margin-top: 15px; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gider Düzenle (ID: <?php echo htmlspecialchars($expense_id); ?>)</h1>
            <a href="dashboard.php">Geri Dön</a>
        </div>

        <?php if ($message): ?>
            <p class="message success"><?php echo $message; ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="message error"><?php echo $error; ?></p>
        <?php endif; ?>

        <?php if ($expense): ?>
        <form method="POST">
            <label for="description">Açıklama:</label>
            <input type="text" id="description" name="description" value="<?php echo htmlspecialchars($expense['description']); ?>" required>
            
            <label for="amount">Tutar (TL):</label>
            <input type="number" id="amount" name="amount" step="0.01" min="0.01" value="<?php echo htmlspecialchars($expense['amount']); ?>" required>
            
            <label for="category">Kategori:</label>
            <select id="category" name="category" required>
                <option value="">Seçiniz</option>
                <?php 
                    $categories = ['Seyahat', 'Yemek', 'Konaklama', 'Temsil'];
                    foreach ($categories as $cat) {
                        $selected = ($expense['category'] == $cat) ? 'selected' : '';
                        echo "<option value=\"$cat\" $selected>$cat</option>";
                    }
                ?>
            </select>
            
            <label for="expense_date">Harcama Tarihi:</label>
            <input type="date" id="expense_date" name="expense_date" value="<?php echo htmlspecialchars($expense['expense_date']); ?>" required>
            
            <button type="submit">Gideri Güncelle</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>