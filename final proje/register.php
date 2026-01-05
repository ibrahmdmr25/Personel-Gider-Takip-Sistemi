<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php'); // Zaten giriş yapmışsa yönlendir
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

    if (empty($username) || empty($password) || empty($email)) {
        $error = "Lütfen tüm alanları doldurun.";
    } elseif (strlen($password) < 6) {
        $error = "Şifre en az 6 karakter olmalıdır.";
    } elseif (!$email) {
        $error = "Geçerli bir e-posta adresi giriniz.";
    } else {
        try {
            // Kullanıcı adının veya e-postanın zaten var olup olmadığını kontrol et
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Bu kullanıcı adı veya e-posta zaten kullanımda.";
            } else {
                // Şifreyi hash'le
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Kullanıcıyı varsayılan rol 'personel' ile ekle
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, 'personel', ?)");
                $stmt->execute([$username, $hashed_password, $email]);

                $success = "Kayıt başarılı! Şimdi giriş yapabilirsiniz.";
                unset($_POST);
            }
        } catch (PDOException $e) {
            $error = "Kayıt sırasında bir hata oluştu: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <title>Kayıt Ol - Gider Takip Sistemi</title>
    <style>
        /* login.php ile aynı stil mantığı */
        html, body {
            height: 100%;
            margin: 0;
            font-family: Arial, sans-serif;
            color: #333;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            /* Arkaplan Resmi (Seçeneklerden biri) */
            background-image: url('https://st2.depositphotos.com/2124221/45766/i/600/depositphotos_457669968-stock-photo-abstrack-colour-background-can-use.jpg');
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            position: relative;
        }

        /* Arkaplanın üzerine okunaklılık için yarı saydam siyah katman */
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Siyah katman (%50 opaklık) */
            z-index: 1;
        }

        .register-container { /* Sınıf adını .register-container olarak güncelledik */
            background: white;
            padding: 2.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 350px;
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .register-container h2 {
            margin-bottom: 1.5rem;
            color: #333;
        }

        input[type="text"], input[type="password"], input[type="email"] { /* email eklendi */
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }
        
        label {
            display: block;
            text-align: left;
            font-weight: bold;
            margin-top: 10px;
        }

        button {
            background-color: #337ab7; /* Kayıt ol butonu için Mavi renk */
            color: white;
            padding: 12px 18px;
            border: none;
            cursor: pointer;
            width: 100%;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: bold;
            margin-top: 1rem;
        }
        
        button:hover {
            background-color: #286090;
        }

        .error, .success { /* Hata ve Başarı mesajları için ortak stil */
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        
        .error {
            color: red;
            background-color: #fdd;
            border: 1px solid red;
        }
        
        .success { /* Başarı mesajı stili eklendi */
            color: green;
            background-color: #d4edda;
            border: 1px solid green;
        }

        .login-link { /* Sınıf adını login-link olarak değiştirdik */
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }
        
        .login-link a {
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Yeni Kullanıcı Kaydı</h2>
        
        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="username">Kullanıcı Adı:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
            
            <label for="email">E-posta:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>

            <label for="password">Şifre (En az 6 karakter):</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit">Kayıt Ol</button>
        </form>
        
        <div class="login-link">
            <p>Zaten hesabınız var mı? <a href="login.php">Giriş Yap</a></p>
        </div>
    </div>
</body>
</html>