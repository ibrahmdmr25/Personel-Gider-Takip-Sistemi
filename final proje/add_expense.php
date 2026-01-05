<?php
require_once 'config.php';
// Sadece 'personel' rolüne izin ver
check_auth(['personel']); 

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description'] ?? '');
    $amount = filter_var($_POST['amount'] ?? '', FILTER_VALIDATE_FLOAT);
    $category = $_POST['category'] ?? '';
    $expense_date = $_POST['expense_date'] ?? '';
    
    // Fiş Yükleme (Opsiyonel veya Zorunlu yapılabilir)
    $receipt_path = null;
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_name = time() . '_' . basename($_FILES['receipt']['name']);
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['receipt']['tmp_name'], $target_file)) {
            $receipt_path = $target_file;
        } else {
            $error = "Dosya yüklenirken bir hata oluştu.";
        }
    }

    if (empty($description) || $amount === false || $amount <= 0 || empty($category) || empty($expense_date)) {
        $error = "Lütfen tüm zorunlu alanları doldurunuz.";
    } elseif (!$error) { // Dosya hatası yoksa devam et
        try {
            // INSERT sorgusu (CREATE)
            $stmt = $pdo->prepare("INSERT INTO expenses (user_id, description, amount, category, expense_date, receipt_path, status) VALUES (?, ?, ?, ?, ?, ?, 'Bekliyor')");
            $stmt->execute([$user_id, $description, $amount, $category, $expense_date, $receipt_path]);
            
            $message = "Gider kaydınız başarıyla eklendi ve onay bekliyor.";
            unset($_POST); // Formu temizle

        } catch (PDOException $e) {
            $error = "Gider eklenirken bir hata oluştu: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Gider Ekle</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* ORTAK TEMA STİLLERİ (Dashboard ile Aynı) */
        :root {
            --primary-blue: #0056b3;
            --dark-blue: #003d82;
            --sidebar-bg: #1e293b;
            --sidebar-hover: #334155;
            --bg-light: #f1f5f9;
            --border-color: #e2e8f0;
        }

        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Inter', sans-serif;
            color: #334155;
        }

        body {
            /* Giriş ekranındaki görsel */
            background-image: url('http://googleusercontent.com/image_collection/image_retrieval/5925840185505150092_0');
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            display: flex;
        }

        /* Saydam koyu katman */
        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.6); /* Biraz daha koyu okunaklılık için */
            z-index: -1;
        }

        /* --- Menü (Sidebar) --- */
        .sidebar {
            width: 260px;
            height: 100vh;
            background-color: var(--sidebar-bg);
            color: white;
            position: fixed;
            top: 0; left: 0;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .sidebar-header {
            padding: 25px;
            border-bottom: 1px solid #334155;
            font-size: 1.3rem;
            font-weight: 700;
            color: #38bdf8;
            letter-spacing: 1px;
            text-align: center;
        }

        .sidebar-menu {
            margin-top: 20px;
            list-style: none;
            padding: 0;
            flex: 1;
        }

        .sidebar-menu li a {
            display: block;
            padding: 15px 25px;
            color: #cbd5e1;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .sidebar-menu li a:hover {
            background-color: var(--sidebar-hover);
            color: white;
            border-left: 4px solid #38bdf8;
            padding-left: 30px;
        }

        .sidebar-footer {
            padding: 20px 25px;
            border-top: 1px solid #334155;
            font-size: 0.85rem;
            background-color: #0f172a;
        }

        /* --- İçerik Alanı --- */
        .content {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 40px;
            box-sizing: border-box;
            display: flex;
            justify-content: center; /* Formu ortala */
            align-items: flex-start; /* Yukarıdan başlat */
        }

        /* Form Kartı (Glassmorphism) */
        .form-card {
            background: rgba(255, 255, 255, 0.95); /* Çok hafif şeffaf beyaz */
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 600px; /* Form genişliği */
            backdrop-filter: blur(10px);
        }

        .form-header {
            border-bottom: 2px solid var(--primary-blue);
            padding-bottom: 15px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-header h2 {
            margin: 0;
            color: var(--primary-blue);
            font-size: 1.5rem;
        }

        .back-link {
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .back-link:hover { color: var(--primary-blue); }

        /* Form Elemanları */
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
            font-size: 0.95rem;
        }

        input[type="text"], input[type="number"], input[type="date"], select, input[type="file"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-sizing: border-box;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        input:focus, select:focus {
            border-color: var(--primary-blue);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1);
        }

        button {
            background-color: var(--primary-blue);
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            transition: background 0.3s;
        }

        button:hover {
            background-color: var(--dark-blue);
        }

        /* Mesajlar */
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 25px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            GİDER TAKİP SİSTEMİ
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">🏠 Ana Panel</a></li>
            <li><a href="add_expense.php" style="background-color: var(--sidebar-hover); border-left: 4px solid #38bdf8;">➕ Yeni Gider Ekle</a></li> <li><a href="logout.php" style="color: #f87171;">🚪 Güvenli Çıkış</a></li>
        </ul>
        <div class="sidebar-footer">
            Kullanıcı: <b><?php echo $_SESSION['username']; ?></b><br>
            Yetki: <span style="color: #38bdf8;"><?php echo strtoupper($_SESSION['user_role']); ?></span>
        </div>
    </div>

    <div class="content">
        <div class="form-card">
            <div class="form-header">
                <h2>Yeni Gider Kaydı</h2>
                <a href="dashboard.php" class="back-link">← Vazgeç ve Dön</a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">✔️ <?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <label for="description">Açıklama (Örn: Müşteri Yemeği)</label>
                <input type="text" id="description" name="description" value="<?php echo htmlspecialchars($_POST['description'] ?? ''); ?>" placeholder="Harcama detayını giriniz..." required>
                
                <div style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <label for="amount">Tutar (TL)</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" placeholder="0.00" value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" required>
                    </div>
                    <div style="flex: 1;">
                        <label for="expense_date">Harcama Tarihi</label>
                        <input type="date" id="expense_date" name="expense_date" value="<?php echo htmlspecialchars($_POST['expense_date'] ?? date('Y-m-d')); ?>" required>
                    </div>
                </div>
                
                <label for="category">Kategori</label>
                <select id="category" name="category" required>
                    <option value="">Kategori Seçiniz</option>
                    <option value="Seyahat" <?php echo (($_POST['category'] ?? '') == 'Seyahat' ? 'selected' : ''); ?>>✈️ Seyahat</option>
                    <option value="Yemek" <?php echo (($_POST['category'] ?? '') == 'Yemek' ? 'selected' : ''); ?>>🍽️ Yemek</option>
                    <option value="Konaklama" <?php echo (($_POST['category'] ?? '') == 'Konaklama' ? 'selected' : ''); ?>>🏨 Konaklama</option>
                    <option value="Temsil" <?php echo (($_POST['category'] ?? '') == 'Temsil' ? 'selected' : ''); ?>>🤝 Temsil ve Ağırlama</option>
                    <option value="Ofis" <?php echo (($_POST['category'] ?? '') == 'Ofis' ? 'selected' : ''); ?>>📎 Ofis Malzemesi</option>
                    <option value="Diğer" <?php echo (($_POST['category'] ?? '') == 'Diğer' ? 'selected' : ''); ?>>📦 Diğer</option>
                </select>

                <label for="receipt">Fiş / Fatura Yükle (İsteğe Bağlı)</label>
                <input type="file" id="receipt" name="receipt" accept="image/*,.pdf">
                
                <button type="submit">Gideri Kaydet ve Onaya Gönder</button>
            </form>
        </div>
    </div>

</body>
</html>