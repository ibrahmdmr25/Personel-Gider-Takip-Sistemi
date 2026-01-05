<?php
require_once 'config.php';
// Sadece 'muhasebeci' rolüne izin ver
check_auth(['muhasebeci']);

$message = '';
$error = '';

// Personel listesini çek (Dropdown için)
$stmt_users = $pdo->prepare("SELECT id, username, email FROM users WHERE role = 'personel' ORDER BY username ASC");
$stmt_users->execute();
$personnel_list = $stmt_users->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'] ?? '';
    $salary_period = $_POST['salary_period'] ?? ''; // YYYY-MM
    $amount = filter_var($_POST['amount'] ?? '', FILTER_VALIDATE_FLOAT);
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');

    if (empty($employee_id) || empty($salary_period) || $amount === false || $amount <= 0) {
        $error = "Lütfen personel, dönem ve tutar bilgilerini eksiksiz girin.";
    } else {
        try {
            // Açıklamayı otomatik oluştur: "2023-10 Dönemi Maaş Ödemesi"
            $description = $salary_period . " Dönemi Maaş Ödemesi";
            
            // Maaş kaydını 'expenses' tablosuna 'Maaş' kategorisi ve 'Onaylandı' statüsü ile ekle
            // user_id olarak personelin ID'sini kaydediyoruz ki o kişiye yapılan harcama olarak görünsün.
            $stmt = $pdo->prepare("INSERT INTO expenses (user_id, description, amount, category, expense_date, status) VALUES (?, ?, ?, 'Maaş', ?, 'Onaylandı')");
            $stmt->execute([$employee_id, $description, $amount, $payment_date]);
            
            $message = "Maaş ödemesi başarıyla kaydedildi.";
            unset($_POST); // Formu temizle

        } catch (PDOException $e) {
            $error = "Veritabanı hatası: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Maaş Ödemesi Ekle</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* ORTAK TEMA STİLLERİ */
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
            background-image: url('http://googleusercontent.com/image_collection/image_retrieval/5925840185505150092_0');
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            display: flex;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.6); 
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
            justify-content: center;
            align-items: flex-start;
        }

        /* Form Kartı */
        .form-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
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

        .form-header h2 { margin: 0; color: var(--primary-blue); font-size: 1.5rem; }
        .back-link { color: #64748b; text-decoration: none; font-size: 0.9rem; }
        .back-link:hover { color: var(--primary-blue); }

        label { display: block; margin-bottom: 8px; font-weight: 600; color: #334155; font-size: 0.95rem; }
        
        input[type="text"], input[type="number"], input[type="date"], input[type="month"], select {
            width: 100%; padding: 12px; margin-bottom: 20px;
            border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box;
            font-family: inherit; transition: border-color 0.3s;
        }
        input:focus, select:focus { border-color: var(--primary-blue); outline: none; box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1); }

        button {
            background-color: var(--primary-blue); color: white; padding: 14px 20px;
            border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: 600; width: 100%;
            transition: background 0.3s;
        }
        button:hover { background-color: var(--dark-blue); }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 25px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">GİDER TAKİP SİSTEMİ</div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">🏠 Ana Panel</a></li>
            <li><a href="add_monthly_income.php">💰 Aylık Gelir Girişi</a></li>
            <li><a href="add_company_expense.php">🏢 Şirket Gideri Ekle</a></li>
            <li><a href="add_salary.php" style="background-color: var(--sidebar-hover); border-left: 4px solid #38bdf8;">💸 Maaş Ödemesi Yap</a></li>
            <li><a href="logout.php" style="color: #f87171;">🚪 Güvenli Çıkış</a></li>
        </ul>
        <div class="sidebar-footer">
            Kullanıcı: <b><?php echo $_SESSION['username']; ?></b><br>
            Yetki: <span style="color: #38bdf8;"><?php echo strtoupper($_SESSION['user_role']); ?></span>
        </div>
    </div>

    <div class="content">
        <div class="form-card">
            <div class="form-header">
                <h2>Maaş Ödemesi Girişi</h2>
                <a href="dashboard.php" class="back-link">← Panele Dön</a>
            </div>

            <?php if ($message): ?> <div class="alert alert-success">✔️ <?php echo htmlspecialchars($message); ?></div> <?php endif; ?>
            <?php if ($error): ?> <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div> <?php endif; ?>

            <form method="POST">
                <label for="employee_id">Ödeme Yapılacak Personel</label>
                <select id="employee_id" name="employee_id" required>
                    <option value="">Personel Seçiniz...</option>
                    <?php foreach ($personnel_list as $person): ?>
                        <option value="<?php echo $person['id']; ?>">
                            👤 <?php echo htmlspecialchars($person['username']); ?> (<?php echo htmlspecialchars($person['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <div style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <label for="salary_period">Maaş Dönemi (Ay/Yıl)</label>
                        <input type="month" id="salary_period" name="salary_period" value="<?php echo date('Y-m'); ?>" required>
                    </div>
                    <div style="flex: 1;">
                        <label for="amount">Ödenecek Tutar (TL)</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" placeholder="0.00" required>
                    </div>
                </div>

                <label for="payment_date">Ödeme Tarihi</label>
                <input type="date" id="payment_date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>

                <button type="submit">Maaş Ödemesini Onayla ve Kaydet</button>
            </form>
        </div>
    </div>

</body>
</html>