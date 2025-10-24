<?php

session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

$message = '';
$message_type = '';

try {
    $pdo = new PDO("sqlite:yavuzlar.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    $companies = $pdo->query("SELECT id, name FROM Bus_Company ORDER BY name")->fetchAll();

    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $company_id = $_POST['company_id'] ?? '';

        
        if (empty($full_name) || empty($email) || empty($password) || empty($company_id)) {
            throw new Exception('Tüm alanlar zorunludur.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Geçersiz e-posta adresi.');
        }

        if (strlen($password) < 6) {
            throw new Exception('Şifre en az 6 karakter olmalıdır.');
        }

        
        $checkEmail = $pdo->prepare("SELECT id FROM User WHERE email = ?");
        $checkEmail->execute([$email]);
        if ($checkEmail->fetch()) {
            throw new Exception('Bu e-posta zaten kullanımda.');
        }

        
        $checkCompany = $pdo->prepare("SELECT id FROM Bus_Company WHERE id = ?");
        $checkCompany->execute([$company_id]);
        if (!$checkCompany->fetch()) {
            throw new Exception('Geçersiz şirket seçimi.');
        }

        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $user_id = uniqid('usr_');
        $pdo->prepare("
            INSERT INTO User (id, full_name, email, role, password, company_id)
            VALUES (?, ?, ?, 'company', ?, ?)
        ")->execute([$user_id, $full_name, $email, $hashed_password, $company_id]);

        $message = 'Yeni firma admini başarıyla oluşturuldu!';
        $message_type = 'success';
    }

} catch (Exception $e) {
    $message = $e->getMessage();
    $message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Firma Admini Oluştur</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f8ff;
            margin: 0;
            padding: 0;
            color: #003366;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 51, 102, 0.1);
        }
        h2 {
            color: #0055aa;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #004080;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #b3d1ff;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #0077cc;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn:hover {
            background: #005fa3;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            text-align: center;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #0077cc;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2> Yeni Firma Admini Oluştur</h2>

        <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="full_name">Ad Soyad *</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>

            <div class="form-group">
                <label for="email">E-posta *</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Şifre *</label>
                <input type="password" id="password" name="password" minlength="6" required>
            </div>

            <div class="form-group">
                <label for="company_id">Şirket *</label>
                <select id="company_id" name="company_id" required>
                    <option value="">Bir şirket seçin</option>
                    <?php foreach ($companies as $comp): ?>
                        <option value="<?= htmlspecialchars($comp['id']) ?>">
                            <?= htmlspecialchars($comp['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn">Firma Admini Oluştur</button>
        </form>

        <a href="admin_dashboard.php" class="back-link">← İptal / Geri Dön</a>
    </div>
</body>
</html>