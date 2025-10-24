<?php
session_start();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $message = 'E-posta ve şifre gereklidir.';
        $message_type = 'error';
    } else {
        try {
            $pdo = new PDO('sqlite:yavuzlar.db');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            
            $stmt = $pdo->prepare('
                SELECT id, email, password, role, full_name, company_id
                FROM "User"
                WHERE email = :email
            ');
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
               
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['full_name'];

                
                if ($user['role'] === 'company') {
                    if (empty($user['company_id'])) {
                        $message = 'Bu hesap bir şirkete atanmamış. Lütfen yöneticinizle iletişime geçin.';
                        $message_type = 'error';
                    } else {
                        $_SESSION['company_id'] = $user['company_id'];
                        
                        header("Location: company_dashboard.php?company_id=" . urlencode($user['company_id']));
                        exit();
                    }
                } elseif ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                    exit();
                } elseif ($user['role'] === 'user') {
                    header("Location: index.php");
                    exit();
                } else {
                    $message = 'Geçersiz kullanıcı rolü.';
                    $message_type = 'error';
                }
            } else {
                $message = 'Geçersiz e-posta veya şifre.';
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            $message = 'Sistem hatası oluştu.';
            $message_type = 'error';
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f8ff; 
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
            background-color: #fafcff; /
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
        }

        .btn-primary {
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

        .btn-primary:hover {
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
        <h2>Giriş Yap</h2>

        <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">E-posta</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-primary">Giriş Yap</button>
        </form>

        <div class="footer-links">
            <p>Hesabınız yok mu? <a href="register.php">Kayıt Olun</a></p>
        </div>
    </div>
</body>
</html>