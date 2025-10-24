<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $logo = null;

    if (empty($name)) {
        $message = 'Şirket adı zorunludur.';
        $message_type = 'error';
    } else {
        try {
            $pdo = new PDO("sqlite:yavuzlar.db");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            
            $check = $pdo->prepare("SELECT id FROM Bus_Company WHERE name = ?");
            $check->execute([$name]);
            if ($check->fetch()) {
                $message = 'Bu şirket zaten mevcut.';
                $message_type = 'error';
            } else {
                
                if (!empty($_FILES['logo']['name'])) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed) && $_FILES['logo']['size'] <= 2 * 1024 * 1024) {
                        $logo_name = uniqid('logo_') . '.' . $ext;
                        $logo_path = 'uploads/logos/' . $logo_name;
                        if (!is_dir(dirname($logo_path))) {
                            mkdir(dirname($logo_path), 0777, true);
                        }
                        if (move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path)) {
                            $logo = $logo_path;
                        }
                    }
                }

                
                $stmt = $pdo->prepare("
                    INSERT INTO Bus_Company (id, name, logo_path)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([uniqid('comp_'), $name, $logo]);

                $message = 'Yeni şirket başarıyla eklendi!';
                $message_type = 'success';
                
                $_POST = [];
            }
        } catch (Exception $e) {
            $message = 'Şirket eklenirken hata oluştu.';
            $message_type = 'error';
            error_log("Add company error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Şirket Ekle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --facebook-blue: #1877F2;
            --light-bg: #f0f2f5;
            --white: #ffffff;
            --gray: #65676b;
            --light-gray: #e4e6eb;
            --danger: #dc3545;
            --success: #4CAF50;
        }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: var(--light-bg);
            margin: 0;
            padding: 0;
            color: #1c1e21;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            padding: 20px;
        }
        h2 {
            color: var(--facebook-blue);
            text-align: center;
            margin-bottom: 25px;
            font-size: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #000;
        }
        input[type="text"],
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background-color: var(--facebook-blue);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #166fe5;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
        }
        .back-link:hover {
            background-color: #5a6268;
        }
        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .note {
            font-size: 13px;
            color: #777;
            margin-top: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🏢 Yeni Şirket Ekle</h2>

        <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Şirket Adı *</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="logo">Logo (isteğe bağlı)</label>
                <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/gif">
                <p class="note">Desteklenen formatlar: JPG, PNG veya GIF (maksimum 2 MB)</p>
            </div>

            <button type="submit" class="btn">Şirketi Ekle</button>
        </form>

        <a href="manage_companies.php" class="back-link">← Şirketleri Yönet</a>
    </div>
</body>
</html>