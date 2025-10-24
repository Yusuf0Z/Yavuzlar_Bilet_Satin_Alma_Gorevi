<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}

$message = '';
$message_type = '';


$upload_dir = 'uploads/logos/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

try {
    $pdo = new PDO("sqlite:yavuzlar.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    
    if (!isset($_GET['id']) || trim($_GET['id']) === '') {
        throw new Exception('Geçersiz şirket ID.');
    }
    $company_id = trim($_GET['id']); 

    
    $stmt = $pdo->prepare("SELECT id, name, logo_path FROM Bus_Company WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        throw new Exception('Şirket bulunamadı.');
    }

    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $logo_path = $company['logo_path']; 

        if (empty($name)) {
            throw new Exception('Şirket adı gereklidir.');
        }

        
        if (!empty($_FILES['logo']['name'])) {
            $logo = $_FILES['logo'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; 

            if ($logo['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Logo yüklenirken hata oluştu.');
            }

            if (!in_array($logo['type'], $allowed_types)) {
                throw new Exception('Sadece JPG, PNG veya GIF formatı kabul edilir.');
            }

            if ($logo['size'] > $max_size) {
                throw new Exception('Logo dosyası 2 MB\'den büyük olamaz.');
            }

            
            if (!empty($company['logo_path']) && file_exists($company['logo_path'])) {
                unlink($company['logo_path']);
            }

            
            $ext = pathinfo($logo['name'], PATHINFO_EXTENSION);
            $new_filename = 'logo_' . uniqid() . '.' . $ext; 
            $target_path = $upload_dir . $new_filename;

            if (move_uploaded_file($logo['tmp_name'], $target_path)) {
                $logo_path = $target_path;
            } else {
                throw new Exception('Logo dosyası sunucuya kaydedilemedi.');
            }
        }

        
        $stmt = $pdo->prepare("UPDATE Bus_Company SET name = ?, logo_path = ? WHERE id = ?");
        $stmt->execute([$name, $logo_path, $company_id]);

        $message = 'Şirket başarıyla güncellendi.';
        $message_type = 'success';

        
        header("Location: edit_company.php?id=" . urlencode($company_id) . "&message=" . urlencode($message) . "&type=success");
        exit;
    }

    
    if (isset($_GET['message'])) {
        $message = $_GET['message'];
        $message_type = $_GET['type'] ?? 'info';
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
    <title>Şirket Düzenle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --facebook-blue: #1877F2;
            --light-bg: #f0f2f5;
            --white: #ffffff;
            --gray: #65676b;
            --light-gray: #e4e6eb;
            --danger: #dc3545;
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
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h2 {
            color: var(--facebook-blue);
            margin-top: 0;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #000;
        }
        .form-group input[type="text"],
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .form-group img {
            max-width: 150px;
            margin-top: 8px;
            border: 1px solid var(--light-gray);
            border-radius: 4px;
        }
        .current-logo {
            font-size: 14px;
            color: #555;
            margin-top: 8px;
        }
        .current-logo p {
            margin: 4px 0;
            font-weight: 600;
        }
        .form-group p {
            font-size: 12px;
            color: #777;
            margin-top: 6px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin-top: 10px;
        }
        .btn-primary {
            background-color: var(--facebook-blue);
            color: white;
        }
        .btn-primary:hover {
            background-color: #166fe5;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            margin-left: 10px;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
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
    </style>
</head>
<body>
    <div class="container">
        <h2> Şirket Düzenle</h2>

        <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Şirket Adı</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($company['name']) ?>" required>
            </div>

            <div class="form-group">
                <label for="logo">Logo (isteğe bağlı)</label>
                <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/gif">
                <?php if (!empty($company['logo_path']) && file_exists($company['logo_path'])): ?>
                    <div class="current-logo">
                        <p>Mevcut logo:</p>
                        <img src="<?= htmlspecialchars($company['logo_path']) ?>" alt="Mevcut Logo">
                    </div>
                <?php endif; ?>
                <p>Desteklenen formatlar: JPG, PNG veya GIF (maksimum 2 MB)</p>
            </div>

            <button type="submit" class="btn btn-primary">Güncelle</button>
            <a href="manage_companies.php" class="btn btn-secondary">İptal</a>
        </form>
    </div>
</body>
</html>