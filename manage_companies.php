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

    
    if (isset($_GET['message'])) {
        $message = $_GET['message'];
        $message_type = $_GET['type'] ?? 'info';
    }

    
    $stmt = $pdo->query("SELECT id, name, logo_path, created_at FROM Bus_Company ORDER BY name");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $message = 'Şirketler yüklenirken hata oluştu.';
    $message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Şirketleri Yönet</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
    <style>
        .company-card {
            background: white;
            border: 1px solid #d0e6ff;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .company-info h3 {
            margin: 0;
            color: #0066cc;
        }
        .company-info p {
            margin: 4px 0;
            color: #555;
            font-size: 14px;
        }
        .btn-group {
            display: flex;
            gap: 8px;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            text-align: center;
            border: none;
            cursor: pointer;
        }
        .btn-add { background: #28a745; }
        .btn-edit { background: #0077cc; }
        .btn-delete { background: #d9534f; }
        .btn-user { background: #17a2b8; }
        .btn-add:hover { background: #218838; }
        .btn-edit:hover { background: #005fa3; }
        .btn-delete:hover { background: #c9302c; }
        .btn-user:hover { background: #138496; }
        .no-data {
            text-align: center;
            color: #777;
            padding: 20px;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            text-align: center;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h2> Şirketleri Yönet</h2>

        <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

       
        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <a href="add_company.php" class="btn btn-add">+ Yeni Şirket Ekle</a>
            <a href="create_company_admin.php" class="btn btn-user">+ Firma Admini Oluştur</a>
        </div>

        <?php if (!empty($companies)): ?>
            <?php foreach ($companies as $comp): ?>
                <div class="company-card">
                    <div class="company-info">
                        <h3><?= htmlspecialchars($comp['name']) ?></h3>
                        <p>Oluşturulma: <?= date('d.m.Y', strtotime($comp['created_at'])) ?></p>
                        <?php if (!empty($comp['logo_path']) && file_exists($comp['logo_path'])): ?>
                            <p>Logo: <img src="<?= htmlspecialchars($comp['logo_path']) ?>" alt="Logo" width="50"></p>
                        <?php endif; ?>
                    </div>
                    <div class="btn-group">
                        <a href="edit_company.php?id=<?= urlencode($comp['id']) ?>" class="btn-sm btn-edit">Düzenle</a>
                        <a href="delete_company.php?id=<?= urlencode($comp['id']) ?>" 
                           class="btn-sm btn-delete"
                           onclick="return confirm('⚠️ Bu işlem geri alınamaz!\nŞirketi ve tüm ilişkili verileri silmek istediğinize emin misiniz?');">
                            Sil
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-data">Henüz şirket eklenmemiş.</div>
        <?php endif; ?>

        <a href="admin_dashboard.php" class="btn" style="background: #6c757d; width: auto; display: inline-block; margin-top: 20px;">← Geri Dön</a>
    </div>
</body>
</html>