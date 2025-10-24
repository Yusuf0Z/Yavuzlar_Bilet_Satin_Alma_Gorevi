<?php

session_start();
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = 'user'; 
    $company_id = null; 

   
    if (empty($full_name) || empty($email) || empty($password)) {
        $message = 'Tüm alanlar zorunludur.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Geçerli bir e-posta adresi girin.';
        $message_type = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Şifre en az 6 karakter olmalıdır.';
        $message_type = 'error';
    } else {
        try {
            $pdo = new PDO("sqlite:yavuzlar.db"); 
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            
            $stmt = $pdo->prepare("SELECT id FROM User WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $message = 'Bu e-posta zaten kayıtlı.';
                $message_type = 'error';
            } else {
            
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

               
                $stmt = $pdo->prepare("
                    INSERT INTO User (id, full_name, email, role, password, company_id)
                    VALUES (?, ?, ?, ?, ?, NULL)
                ");
                $user_id = uniqid('usr_', true);
                $stmt->execute([$user_id, $full_name, $email, $role, $hashed_password]);

                $message = 'Kayıt başarılı! Şimdi giriş yapabilirsiniz.';
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = 'Bir hata oluştu.';
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kayıt Ol</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f8ff; /* Açık mavi arka plan */
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            width: 95%;
            max-width: 450px;
            border: 1px solid #e0e0e0;
        }

        .container h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #0056b3;
            font-weight: 600;
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
            background-color: #fafcff;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background: #0056b3;
        }

        .message {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .footer-links {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }

        .footer-links a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }

        .footer-links a:hover {
            text-decoration: underline;
            color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Yolcu Hesabı Oluştur</h2>

        <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="full_name">Ad Soyad</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>

            <div class="form-group">
                <label for="email">E-posta</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>

            <button type="submit" class="btn">Kayıt Ol</button>
        </form>

        <div class="footer-links">
            <p>Zaten hesabınız var mı? <a href="login.php">Giriş Yapın</a></p>
        </div>
    </div>
</body>
</html>