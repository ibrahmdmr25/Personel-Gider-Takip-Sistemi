<?php
require_once 'config.php';
check_auth(); // Giriş kontrolü

$role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// Değişkenleri Başlat
$expenses = [];
$company_expenses = []; 
$salary_expenses = []; // YENİ: Maaş ödemeleri listesi
$corp_expenses = []; 
$total_amount = 0;
$logs = []; 
$all_users = []; 
$user_expense_summary = []; 
$patron_monthly_income = 0;
$patron_monthly_expense = 0;
$net_profit = 0;

// Mesajları Yakala
$display_message = $_GET['message'] ?? '';
$display_error = $_GET['error'] ?? '';
$display_message = str_replace('_', ' ', $display_message);
$display_error = str_replace('_', ' ', $display_error);

// Tarih Ayarları
$selected_month = $_GET['month'] ?? date('Y-m');
$selected_month_start = $selected_month . '-01'; 
$selected_month_end = date('Y-m-t', strtotime($selected_month_start));

// Aktif Sayfayı Belirle (Patron için)
$active_page = $_GET['page'] ?? 'ozet';
$page_title = "Kontrol Paneli";

// --- ROL BAZLI VERİ ÇEKME İŞLEMLERİ ---

if ($role === 'personel') {
    $page_title = "Giderlerim";
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE user_id = ? ORDER BY expense_date DESC");
    $stmt->execute([$user_id]);
    $expenses = $stmt->fetchAll();
    
    $stmt_sum = $pdo->prepare("SELECT SUM(amount) AS total FROM expenses WHERE user_id = ? AND status != 'Reddedildi'");
    $stmt_sum->execute([$user_id]);
    $total_amount = $stmt_sum->fetchColumn();

} elseif ($role === 'muhasebeci') {
    $page_title = "Muhasebe Onay Paneli";
    
    // 1. Aylık Gelir Bilgisi
    $stmt_income = $pdo->prepare("SELECT income_amount FROM monthly_income WHERE month_year = ?");
    $stmt_income->execute([$selected_month]);
    $monthly_income = $stmt_income->fetchColumn() ?: 0;
    
    // 2. Durum Özeti
    $stmt_summary = $pdo->prepare("SELECT status, SUM(amount) AS total_amount FROM expenses GROUP BY status");
    $stmt_summary->execute();
    $summary_data = $stmt_summary->fetchAll();
    $summary = ['Bekliyor' => ['total' => 0], 'Onaylandı' => ['total' => 0], 'Reddedildi' => ['total' => 0]];
    foreach ($summary_data as $row) { $summary[$row['status']]['total'] = $row['total_amount']; }
    
    // 3. Onay Bekleyen Personel Giderleri
    $stmt = $pdo->prepare("SELECT e.*, u.username AS personel_adi FROM expenses e JOIN users u ON e.user_id = u.id WHERE e.status = 'Bekliyor' ORDER BY e.expense_date ASC");
    $stmt->execute();
    $expenses = $stmt->fetchAll();

    // 4. Muhasebecinin Eklediği Son Şirket Giderleri (Vergi, Kira vb.)
    // Kategori 'Maaş' OLMAYANLAR (Maaşları ayrı tabloda göstereceğiz)
    $stmt_comp = $pdo->prepare("SELECT * FROM expenses WHERE user_id = ? AND category != 'Maaş' ORDER BY id DESC LIMIT 10");
    $stmt_comp->execute([$user_id]);
    $company_expenses = $stmt_comp->fetchAll();

    // 5. (YENİ) Son Yapılan Maaş Ödemeleri
    $stmt_salary = $pdo->prepare("
        SELECT e.*, u.username AS personel_adi, u.email 
        FROM expenses e 
        JOIN users u ON e.user_id = u.id 
        WHERE e.category = 'Maaş' 
        ORDER BY e.expense_date DESC 
        LIMIT 10
    ");
    $stmt_salary->execute();
    $salary_expenses = $stmt_salary->fetchAll();

} elseif ($role === 'patron') {
    
    // --- PATRON SAYFALAMA MANTIĞI ---
    if ($active_page === 'ozet') {
        $page_title = "Finansal Analiz ve Özet";
        
        $stmt_total_income = $pdo->prepare("SELECT income_amount FROM monthly_income WHERE month_year = ?");
        $stmt_total_income->execute([$selected_month]);
        $patron_monthly_income = $stmt_total_income->fetchColumn() ?: 0;
        
        $stmt_approved_expense = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE status = 'Onaylandı' AND expense_date BETWEEN ? AND ?");
        $stmt_approved_expense->execute([$selected_month_start, $selected_month_end]);
        $patron_monthly_expense = $stmt_approved_expense->fetchColumn() ?: 0;
        
        $net_profit = $patron_monthly_income - $patron_monthly_expense;

        $stmt = $pdo->prepare("SELECT category, SUM(amount) AS total FROM expenses WHERE status = 'Onaylandı' AND expense_date BETWEEN ? AND ? GROUP BY category ORDER BY total DESC");
        $stmt->execute([$selected_month_start, $selected_month_end]);
        $expenses = $stmt->fetchAll(); 
        
        $stmt_user_summary = $pdo->prepare("
            SELECT u.username, SUM(e.amount) AS total_spent_by_user 
            FROM expenses e 
            JOIN users u ON e.user_id = u.id 
            WHERE e.status = 'Onaylandı' 
            AND u.role = 'personel' 
            AND e.expense_date BETWEEN ? AND ? 
            GROUP BY e.user_id, u.username 
            ORDER BY total_spent_by_user DESC
        ");
        $stmt_user_summary->execute([$selected_month_start, $selected_month_end]);
        $user_expense_summary = $stmt_user_summary->fetchAll();

        $stmt_corp = $pdo->prepare("
            SELECT e.*, u.username AS muhasebeci_adi
            FROM expenses e 
            JOIN users u ON e.user_id = u.id 
            WHERE u.role = 'muhasebeci' 
            AND e.expense_date BETWEEN ? AND ? 
            ORDER BY e.expense_date DESC
        ");
        $stmt_corp->execute([$selected_month_start, $selected_month_end]);
        $corp_expenses = $stmt_corp->fetchAll();

    } elseif ($active_page === 'kullanicilar') {
        $page_title = "Kullanıcı Yönetimi";
        $stmt_users = $pdo->prepare("SELECT id, username, email, role, created_at FROM users ORDER BY role, username");
        $stmt_users->execute();
        $all_users = $stmt_users->fetchAll();

    } elseif ($active_page === 'loglar') {
        $page_title = "Sistem İşlem Kayıtları (Loglar)";
        $log_stmt = $pdo->prepare("SELECT l.action_time, l.action_type, u.username AS muhasebeci_adi, e.description AS gider_aciklamasi, e.amount FROM logs l JOIN users u ON l.action_user_id = u.id JOIN expenses e ON l.expense_id = e.id ORDER BY l.action_time DESC LIMIT 50");
        $log_stmt->execute();
        $logs = $log_stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #0056b3;
            --dark-blue: #003d82;
            --sidebar-bg: #1e293b;
            --sidebar-hover: #334155;
            --bg-light: #f1f5f9;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            display: flex;
            background-color: var(--bg-light);
            color: #334155;
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

        .sidebar-menu li a:hover, .sidebar-menu li a.active {
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

        /* --- Ana İçerik --- */
        .content {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 40px;
            box-sizing: border-box;
        }

        .main-card {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-blue);
        }

        .header-row h1 { color: var(--primary-blue); margin: 0; font-size: 1.8rem; }

        /* --- Rapor Kartları --- */
        .summary-container { display: flex; gap: 20px; margin-bottom: 30px; }
        .summary-card {
            flex: 1;
            padding: 25px;
            border-radius: 12px;
            background: #fff;
            border: 1px solid var(--border-color);
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .summary-card:hover { transform: translateY(-5px); }
        .summary-card h4 { margin: 0; font-size: 0.85rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .summary-value { font-size: 1.8rem; font-weight: 700; color: var(--primary-blue); display: block; margin-top: 10px; }

        /* --- Tablo --- */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; border-radius: 8px; overflow: hidden; }
        th { background-color: var(--primary-blue); color: white; text-align: left; padding: 15px; font-size: 0.9rem; font-weight: 600; }
        td { padding: 15px; border-bottom: 1px solid #e2e8f0; font-size: 0.95rem; color: #475569; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #f8fafc; }

        /* --- Rozetler ve Butonlar --- */
        .status { padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: inline-block; }
        .status-onaylandi { background: #dcfce7; color: #166534; }
        .status-reddedildi { background: #fee2e2; color: #991b1b; }
        .status-bekliyor { background: #fef9c3; color: #854d0e; }

        .btn { background: var(--primary-blue); color: white; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-block; border: none; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background: var(--dark-blue); }
        
        .action-link { color: var(--primary-blue); text-decoration: none; font-weight: 600; margin-right: 10px; }
        .action-delete { color: #ef4444; text-decoration: none; font-weight: 600; }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: center; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* Form Elemanları */
        form { background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 15px; margin-bottom: 30px; }
        input[type="month"] { padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; }
        
        h2, h3 { color: #1e293b; margin-top: 40px; margin-bottom: 15px; border-left: 5px solid var(--primary-blue); padding-left: 10px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        GİDER TAKİP SİSTEMİ
    </div>
    <ul class="sidebar-menu">
        <?php if ($role === 'personel'): ?>
            <li><a href="dashboard.php" class="active">🏠 Ana Panel</a></li>
            <li><a href="add_expense.php">➕ Yeni Gider Ekle</a></li>
        <?php endif; ?>

        <?php if ($role === 'muhasebeci'): ?>
            <li><a href="dashboard.php" class="active">🏠 Ana Panel</a></li>
            <li><a href="add_monthly_income.php">💰 Aylık Gelir Girişi</a></li>
            <li><a href="add_company_expense.php">🏢 Şirket Gideri Ekle</a></li>
            <li><a href="add_salary.php">💸 Maaş Ödemesi Yap</a></li>
        <?php endif; ?>

        <?php if ($role === 'patron'): ?>
            <li><a href="dashboard.php?page=ozet" class="<?php echo ($active_page === 'ozet') ? 'active' : ''; ?>">📊 Finansal Özet</a></li>
            <li><a href="dashboard.php?page=kullanicilar" class="<?php echo ($active_page === 'kullanicilar') ? 'active' : ''; ?>">👥 Kullanıcı Yönetimi</a></li>
            <li><a href="dashboard.php?page=loglar" class="<?php echo ($active_page === 'loglar') ? 'active' : ''; ?>">📝 İşlem Logları</a></li>
        <?php endif; ?>

        <li><a href="logout.php" style="color: #f87171;">🚪 Güvenli Çıkış</a></li>
    </ul>
    <div class="sidebar-footer">
        Merhaba, <b><?php echo htmlspecialchars($_SESSION['username']); ?></b><br>
        Rol: <span style="color: #38bdf8;"><?php echo strtoupper($role); ?></span>
    </div>
</div>

<div class="content">
    <div class="main-card">
        <div class="header-row">
            <h1><?php echo $page_title; ?></h1>
        </div>

        <?php if ($display_message): ?> <div class="alert alert-success">✔️ <?php echo htmlspecialchars($display_message); ?></div> <?php endif; ?>
        <?php if ($display_error): ?> <div class="alert alert-error">❌ <?php echo htmlspecialchars($display_error); ?></div> <?php endif; ?>

        <?php if ($role === 'personel'): ?>
            <div class="summary-container">
                <div class="summary-card" style="border-top: 4px solid var(--primary-blue);">
                    <h4>TOPLAM HARCAMANIZ</h4>
                    <span class="summary-value"><?php echo number_format($total_amount, 2); ?> TL</span>
                </div>
            </div>
            <a href="add_expense.php" class="btn">+ Yeni Gider Kaydı</a>
            <table>
                <thead><tr><th>Tarih</th><th>Açıklama</th><th>Tutar</th><th>Kategori</th><th>Durum</th><th>İşlem</th></tr></thead>
                <tbody>
                    <?php foreach ($expenses as $e): ?>
                    <tr>
                        <td><?php echo $e['expense_date']; ?></td>
                        <td><?php echo htmlspecialchars($e['description']); ?></td>
                        <td><?php echo number_format($e['amount'], 2); ?> TL</td>
                        <td><?php echo htmlspecialchars($e['category']); ?></td>
                        <td><span class="status status-<?php echo strtolower($e['status']); ?>"><?php echo $e['status']; ?></span></td>
                        <td>
                            <?php if ($e['status'] === 'Bekliyor'): ?>
                                <a href="edit_expense.php?id=<?php echo $e['id']; ?>" class="action-link">Düzenle</a>
                                <a href="delete_expense.php?id=<?php echo $e['id']; ?>" class="action-delete" onclick="return confirm('Silmek istediğine emin misin?')">Sil</a>
                            <?php else: ?> - <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($role === 'muhasebeci'): ?>
            <div class="summary-container">
                <div class="summary-card"><h4>BU AYKİ GELİR</h4><span class="summary-value"><?php echo number_format($monthly_income, 2); ?> TL</span></div>
                <div class="summary-card"><h4>ONAYLANAN</h4><span class="summary-value" style="color:#166534;"><?php echo number_format($summary['Onaylandı']['total'], 2); ?> TL</span></div>
                <div class="summary-card"><h4>BEKLEYEN</h4><span class="summary-value" style="color:#854d0e;"><?php echo number_format($summary['Bekliyor']['total'], 2); ?> TL</span></div>
            </div>
            
            <h3>Onay Bekleyen Personel Giderleri</h3>
            <table>
                <thead><tr><th>Personel</th><th>Tarih</th><th>Açıklama</th><th>Tutar</th><th>Belge</th><th>İşlem</th></tr></thead>
                <tbody>
                    <?php if(!empty($expenses)): ?>
                        <?php foreach ($expenses as $e): ?>
                        <tr>
                            <td><?php echo $e['personel_adi']; ?></td>
                            <td><?php echo $e['expense_date']; ?></td>
                            <td><?php echo htmlspecialchars($e['description']); ?></td>
                            <td><?php echo number_format($e['amount'], 2); ?> TL</td>
                            <td><?php if ($e['receipt_path']): ?><a href="<?php echo htmlspecialchars($e['receipt_path']); ?>" target="_blank" class="action-link">Görüntüle</a><?php else: ?>Yok<?php endif; ?></td>
                            <td>
                                <a href="update_status.php?id=<?php echo $e['id']; ?>&status=Onaylandı" class="action-link" style="color:#166534;">Onayla</a> | 
                                <a href="update_status.php?id=<?php echo $e['id']; ?>&status=Reddedildi" class="action-delete">Reddet</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">Onay bekleyen kayıt yok.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h3>🏢 Sizin Eklediğiniz Şirket Giderleri (Son 10)</h3>
            <table>
                <thead><tr><th>Tarih</th><th>Kategori</th><th>Açıklama</th><th>Tutar</th><th>Durum</th></tr></thead>
                <tbody>
                    <?php if(!empty($company_expenses)): ?>
                        <?php foreach ($company_expenses as $ce): ?>
                        <tr>
                            <td><?php echo $ce['expense_date']; ?></td>
                            <td><?php echo htmlspecialchars($ce['category']); ?></td>
                            <td><?php echo htmlspecialchars($ce['description']); ?></td>
                            <td><?php echo number_format($ce['amount'], 2); ?> TL</td>
                            <td><span class="status status-onaylandi">Onaylandı</span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;">Henüz şirket gideri eklemediniz.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h3>💸 Son Yapılan Maaş Ödemeleri</h3>
            <table>
                <thead><tr><th>Personel</th><th>Açıklama (Dönem)</th><th>Ödeme Tarihi</th><th>Tutar</th></tr></thead>
                <tbody>
                    <?php if(!empty($salary_expenses)): ?>
                        <?php foreach ($salary_expenses as $salary): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($salary['personel_adi']); ?><br>
                                <small style="color:#64748b;"><?php echo htmlspecialchars($salary['email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($salary['description']); ?></td>
                            <td><?php echo $salary['expense_date']; ?></td>
                            <td style="color:#166534; font-weight:bold;"><?php echo number_format($salary['amount'], 2); ?> TL</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;">Henüz maaş ödemesi yapılmadı.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($role === 'patron'): ?>
            
            <?php if ($active_page === 'ozet'): ?>
                <form method="GET">
                    <input type="hidden" name="page" value="ozet">
                    <label>Rapor Dönemi:</label>
                    <input type="month" name="month" value="<?php echo $selected_month; ?>">
                    <button type="submit" class="btn">Raporu Getir</button>
                </form>

                <div class="summary-container">
                    <div class="summary-card"><h4>💵 TOPLAM GELİR</h4><span class="summary-value"><?php echo number_format($patron_monthly_income, 2); ?> TL</span></div>
                    <div class="summary-card"><h4>📉 TOPLAM GİDER</h4><span class="summary-value" style="color:#ef4444;"><?php echo number_format($patron_monthly_expense, 2); ?> TL</span></div>
                    <div class="summary-card" style="background:#f0f9ff;">
                        <h4>📈 NET DURUM</h4>
                        <span class="summary-value" style="color: <?php echo ($net_profit >= 0) ? '#166534' : '#ef4444'; ?>;">
                            <?php echo number_format(abs($net_profit), 2); ?> TL <?php echo ($net_profit >= 0) ? '(Kâr)' : '(Zarar)'; ?>
                        </span>
                    </div>
                </div>

                <h3>🏢 Kurumsal Şirket Giderleri (<?php echo $selected_month; ?>)</h3>
                <table>
                    <thead><tr><th>Tarih</th><th>Kategori</th><th>Açıklama</th><th>Tutar</th><th>Ekleyen Muhasebeci</th></tr></thead>
                    <tbody>
                        <?php if(!empty($corp_expenses)): ?>
                            <?php foreach ($corp_expenses as $ce): ?>
                            <tr>
                                <td><?php echo $ce['expense_date']; ?></td>
                                <td><?php echo htmlspecialchars($ce['category']); ?></td>
                                <td><?php echo htmlspecialchars($ce['description']); ?></td>
                                <td><?php echo number_format($ce['amount'], 2); ?> TL</td>
                                <td><?php echo htmlspecialchars($ce['muhasebeci_adi']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center;">Seçilen ay için şirket gideri bulunamadı.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <h3>📊 Kategori Bazlı Genel Giderler</h3>
                <table>
                    <thead><tr><th>Kategori</th><th>Toplam</th></tr></thead>
                    <tbody>
                        <?php foreach ($expenses as $e): ?>
                        <tr><td><?php echo htmlspecialchars($e['category']); ?></td><td><?php echo number_format($e['total'], 2); ?> TL</td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h3>👤 Personel Harcama Listesi (<?php echo $selected_month; ?>)</h3>
                <table>
                    <thead><tr><th>Personel</th><th>Onaylanmış Toplam</th></tr></thead>
                    <tbody>
                        <?php if(!empty($user_expense_summary)): ?>
                            <?php foreach ($user_expense_summary as $us): ?>
                            <tr><td><?php echo htmlspecialchars($us['username']); ?></td><td><b><?php echo number_format($us['total_spent_by_user'], 2); ?> TL</b></td></tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" style="text-align:center;">Kayıt bulunamadı.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            
            <?php elseif ($active_page === 'kullanicilar'): ?>
                <h3>Kullanıcı Listesi ve Yönetimi</h3>
                <table>
                    <thead><tr><th>Kullanıcı</th><th>E-posta</th><th>Rol</th><th>Kayıt Tarihi</th><th>İşlem</th></tr></thead>
                    <tbody>
                        <?php foreach ($all_users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="status" style="background:#e2e8f0; color:#475569;"><?php echo strtoupper($user['role']); ?></span></td>
                            <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if ($user['id'] !== $_SESSION['user_id'] && $user['role'] !== 'patron'): ?>
                                    <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="action-delete" onclick="return confirm('Kullanıcıyı silmek istediğine emin misin?')">Sil</a>
                                <?php else: ?> <span style="color:#94a3b8; font-size:0.8rem;">Kısıtlı</span> <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php elseif ($active_page === 'loglar'): ?>
                <h3>Sistem İşlem Kayıtları (Son 50)</h3>
                <table>
                    <thead><tr><th>Zaman</th><th>Muhasebeci</th><th>İşlem</th><th>Açıklama</th><th>Tutar</th></tr></thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo $log['action_time']; ?></td>
                            <td><?php echo htmlspecialchars($log['muhasebeci_adi']); ?></td>
                            <td><span class="status status-<?php echo strtolower($log['action_type']); ?>"><?php echo $log['action_type']; ?></span></td>
                            <td><?php echo htmlspecialchars($log['gider_aciklamasi']); ?></td>
                            <td><?php echo number_format($log['amount'], 2); ?> TL</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</div>

</body>
</html>