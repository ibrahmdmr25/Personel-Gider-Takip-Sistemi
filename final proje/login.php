<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php'); // Zaten giriş yapmışsa yönlendir
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Kullanıcı adı ve şifre boş bırakılamaz.";
    } else {
        // Kullanıcıyı veritabanından çekme
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Başarılı giriş: Oturum değişkenlerini ayarla
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];

            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Hatalı kullanıcı adı veya şifre.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <title>Giriş Yap - Gider Takip Sistemi</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Daha modern bir font */
            color: #333;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: url('https://static5.depositphotos.com/1010050/513/i/950/depositphotos_5135344-stock-photo-modern-office.jpg');
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            position: relative;
        }

        /* Arkaplanın üzerine okunaklılık için yarı saydam siyah katman ekler */
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6); /* Daha belirgin bir karartma (%60 opaklık) */
            z-index: 1;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95); /* Hafif şeffaf beyaz kutu */
            padding: 2.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3); /* Gölgeyi biraz artırdık */
            width: 380px; /* Paneli biraz genişlettik */
            max-width: 90%; /* Küçük ekranlarda taşmayı önle */
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .login-container h2 {
            margin-bottom: 1.8rem; /* Başlık ve form arasına boşluk */
            color: #2c3e50; /* Koyu gri, modern bir ton */
            font-size: 2rem;
            font-weight: 600; /* Biraz daha kalın */
        }

        label {
            display: block;
            text-align: left;
            font-weight: 600; /* Daha belirgin etiketler */
            margin-top: 15px;
            margin-bottom: 5px;
            color: #555;
        }

        input[type="text"], input[type="password"] {
            width: calc(100% - 24px); /* Padding'i hesaba kat */
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid #c0c0c0; /* Açık gri kenarlık */
            border-radius: 8px; /* Köşeleri daha yuvarlak */
            box-sizing: border-box;
            font-size: 1rem;
        }
        
        input[type="text"]:focus, input[type="password"]:focus {
            border-color: #007bff; /* Focus rengi mavi */
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25); /* Hafif mavi gölge */
            outline: none; /* Varsayılan focus stilini kaldır */
        }

        button {
            background-color: #007bff; /* Kurumsal mavi tonu */
            color: white;
            padding: 14px 20px; /* Buton boyutunu artırdık */
            border: none;
            cursor: pointer;
            width: 100%;
            border-radius: 8px; /* Köşeleri daha yuvarlak */
            font-size: 1.1rem; /* Daha büyük font */
            font-weight: 700; /* Daha kalın font */
            margin-top: 1.8rem; /* Boşluğu artırdık */
            transition: background-color 0.3s ease; /* Hover efekti için yumuşak geçiş */
        }
        
        button:hover {
            background-color: #0056b3; /* Koyu mavi hover */
        }

        .error {
            color: #dc3545; /* Kırmızı hata mesajı */
            background-color: #f8d7da; /* Açık kırmızı arkaplan */
            border: 1px solid #f5c6cb; /* Kırmızı kenarlık */
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }

        .register-link {
            text-align: center;
            margin-top: 2rem; /* Boşluğu artırdık */
            font-size: 0.95rem;
        }
        
        .register-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Gider Takip Sistemi</h2>
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="username">Kullanıcı Adı</label>
            <input type="text" id="username" name="username" required>
            
            <label for="password">Şifre</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit">Giriş Yap</button>
        </form>
        
        <div class="register-link">
            <p>Hesabınız yok mu? <a href="register.php">Hemen Kayıt Olun</a></p>
        </div>
    </div>
</body>
</html>