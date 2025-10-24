<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}

$message = '';
$message_type = '';


$companies = [];
try {
    $pdo = new PDO("sqlite:yavuzlar.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $compStmt = $pdo->query("SELECT id, name FROM Bus_Company ORDER BY name");
    $companies = $compStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Şirketler yüklenirken hata: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $discount = floatval($_POST['discount'] ?? 0);
    $usage_limit = intval($_POST['usage_limit'] ?? 0);
    $expire_date = $_POST['expire_date'] ?? '';
    $target_type = $_POST['target_type'] ?? 'all'; 
    $company_id = $_POST['company_id'] ?? null;

    if (empty($code) || $discount <= 0 || $discount > 100 || $usage_limit <= 0 || empty($expire_date)) {
        $message = 'Tüm alanlar geçerli olmalıdır.';
        $message_type = 'error';
    } else {
        try {
            
            $check = $pdo->prepare("SELECT id FROM Coupons WHERE code = ?");
            $check->execute([$code]);
            if ($check->fetch()) {
                $message = 'Bu kupon kodu zaten mevcut.';
                $message_type = 'error';
            } else {
                
                $coupon_company_id = null;
                if ($target_type === 'company' && !empty($company_id)) {
                   
                    $compCheck = $pdo->prepare("SELECT id FROM Bus_Company WHERE id = ?");
                    $compCheck->execute([$company_id]);
                    if (!$compCheck->fetch()) {
                        throw new Exception('Geçersiz şirket seçimi.');
                    }
                    $coupon_company_id = $company_id;
                }
                

                $stmt = $pdo->prepare("
                    INSERT INTO Coupons (id, code, discount, usage_limit, expire_date, company_id)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([uniqid('cpn_'), $code, $discount, $usage_limit, $expire_date, $coupon_company_id]);

                $message = 'Yeni kupon başarıyla oluşturuldu!';
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = 'Kupon oluşturulurken hata oluştu: ' . $e->getMessage();
            $message_type = 'error';
            error_log("Coupon error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kupon Yönetimi</title>
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
            box-shadow: 0 4px 12px rgba(0,51,102,0.1);
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
        input[type="number"],
        input[type="date"],
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

        
        #company-select { display: none; }
        #company-select.show { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <h2> Yeni Kupon Tanımla</h2>

        <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" id="coupon-form">
            <div class="form-group">
                <label for="code">Kupon Kodu *</label>
                <input type="text" id="code" name="code" placeholder="Örn: YAZ2025" required>
            </div>

            <div class="form-group">
                <label for="discount">İndirim (%) *</label>
                <input type="number" id="discount" name="discount" min="1" max="100" step="0.1" placeholder="10.5" required>
            </div>

            <div class="form-group">
                <label for="usage_limit">Kullanım Limiti *</label>
                <input type="number" id="usage_limit" name="usage_limit" min="1" placeholder="100" required>
            </div>

            <div class="form-group">
                <label for="expire_date">Bitiş Tarihi *</label>
                <input type="date" id="expire_date" name="expire_date" required>
            </div>

            
            <div class="form-group">
                <label>Hedef Kullanıcılar *</label>
                <select id="target_type" name="target_type" required>
                    <option value="all">Tüm Kullanıcılar</option>
                    <option value="company">Belirli Bir Şirket</option>
                </select>
            </div>

            
            <div class="form-group" id="company-select">
                <label for="company_id">Şirket Seçin *</label>
                <select id="company_id" name="company_id">
                    <option value="">Şirket seçin</option>
                    <?php foreach ($companies as $comp): ?>
                        <option value="<?= htmlspecialchars($comp['id']) ?>">
                            <?= htmlspecialchars($comp['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn">Kuponu Oluştur</button>
        </form>

        <a href="admin_dashboard.php" class="back-link">← Yönetim Paneline Dön</a>
    </div>

    <script>
        
        document.getElementById('target_type').addEventListener('change', function() {
            const companySelect = document.getElementById('company-select');
            if (this.value === 'company') {
                companySelect.classList.add('show');
                companySelect.querySelector('select').setAttribute('required', 'required');
            } else {
                companySelect.classList.remove('show');
                companySelect.querySelector('select').removeAttribute('required');
            }
        });
    </script>
</body>
</html>