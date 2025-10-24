<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $expiry = $_POST['expiry'] ?? '';
    $cvv = $_POST['cvv'] ?? '';

    
    if ($amount < 10) {
        $message = 'Minimum yükleme tutarı 10 ₺ olmalıdır.';
        $message_type = 'error';
    } elseif ($amount > 50000) {
        $message = 'Maksimum yükleme tutarı 50.000 ₺ olabilir.';
        $message_type = 'error';
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $message = 'Geçersiz tutar girildi.';
        $message_type = 'error';
    }
    
    elseif (strlen($card_number) !== 16 || !ctype_digit($card_number)) {
        $message = 'Geçersiz kart numarası.';
        $message_type = 'error';
    } elseif (!preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
        $message = 'Son kullanma tarihi AA/YY formatında olmalıdır (örn: 12/25).';
        $message_type = 'error';
    } elseif (strlen($cvv) < 3 || strlen($cvv) > 4 || !ctype_digit($cvv)) {
        $message = 'Geçersiz CVV.';
        $message_type = 'error';
    } else {
    
        try {
            $pdo = new PDO("sqlite:yavuzlar.db");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            
            $stmt = $pdo->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);

            
            $balance = $pdo->prepare("SELECT balance FROM User WHERE id = ?");
            $balance->execute([$user_id]);
            $_SESSION['user_balance'] = $balance->fetchColumn();

            $message = "✅ Başarıyla " . number_format($amount, 2, ',', '.') . " ₺ bakiye yüklendi!";
            $message_type = 'success';

            
            header("Refresh: 2; url=dashboard.php");
            exit;

        } catch (Exception $e) {
            $message = 'Bakiye yüklenirken hata oluştu.';
            $message_type = 'error';
            error_log("Balance update error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Bakiye Yükle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f8ff;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #003366;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 51, 102, 0.2);
            width: 90%;
            max-width: 500px;
        }
        h2 {
            text-align: center;
            color: #0055aa;
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
        input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #b3d1ff;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #0077cc;
            box-shadow: 0 0 0 2px rgba(0, 119, 204, 0.25);
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
            transition: background 0.3s;
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
        .limits {
            font-size: 13px;
            color: #666;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>💰 Bakiye Yükle</h2>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" id="payment-form">
            <div class="form-group">
                <label for="amount">Yüklenecek Tutar (₺)</label>
                <input type="number" 
                       id="amount" 
                       name="amount" 
                       step="10"
                       min="10"
                       max="50000"
                       placeholder="Örn: 100"
                       required>
                <div class="limits">Minimum: 10 ₺ | Maksimum: 50.000 ₺</div>
            </div>

            <div class="form-group">
                <label for="card_number">Kart Numarası</label>
                <input type="text" 
                       id="card_number" 
                       name="card_number" 
                       placeholder="1234 5678 9012 3456" 
                       maxlength="19"
                       required
                       oninput="formatCardNumber(this)">
            </div>

            <div style="display: flex; gap: 12px;">
                <div class="form-group" style="flex: 1;">
                    <label for="expiry">Son Kullanma Tarihi</label>
                    <input type="text" 
                           id="expiry" 
                           name="expiry" 
                           placeholder="AA/YY" 
                           maxlength="5"
                           required
                           oninput="formatExpiry(this)">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="cvv">CVV</label>
                    <input type="text" 
                           id="cvv" 
                           name="cvv" 
                           placeholder="123" 
                           maxlength="4"
                           required
                           oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                </div>
            </div>

            <button type="submit" class="btn">Ödemeyi Tamamla</button>
        </form>

        <a href="dashboard.php" class="back-link">← İptal / Geri Dön</a>
    </div>

    <script>
        function formatCardNumber(input) {
            let value = input.value.replace(/\D/g, '');
            let formatted = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) formatted += ' ';
                formatted += value[i];
            }
            input.value = formatted.substring(0, 19);
        }

        function formatExpiry(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length >= 2) {
                input.value = value.substring(0, 2) + '/' + value.substring(2, 4);
            } else {
                input.value = value;
            }
        }
    </script>
</body>
</html>