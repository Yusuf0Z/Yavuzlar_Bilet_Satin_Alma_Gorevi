<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);
$message = '';
$message_type = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_ticket'])) {
    $ticket_id = $_POST['ticket_id'];

    try {
        $pdo = new PDO("sqlite:yavuzlar.db");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        
        $check = $pdo->prepare("
            SELECT 
                tk.id,
                t.trip_date,          
                t.departure_time,
                tk.total_price
            FROM Tickets tk
            JOIN Trips t ON tk.trip_id = t.id
            WHERE tk.id = ? AND tk.user_id = ? AND tk.status = 'active'
        ");
        $check->execute([$ticket_id, $user_id]);
        $ticket = $check->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            $message = 'İptal edilecek geçerli bilet bulunamadı.';
            $message_type = 'error';
        } else {
            
            $departure_time = new DateTime($ticket['trip_date'] . ' ' . $ticket['departure_time']);
            $now = new DateTime();
            $cutoff_time = clone $departure_time;
            $cutoff_time->sub(new DateInterval('PT1H')); // 1 saat önce

            if ($now > $cutoff_time) {
                $message = 'Bilet, seferin kalkış saatinden 1 saat öncesine kadar iptal edilebilir. İptal süresi doldu.';
                $message_type = 'error';
            } else {
                
                $pdo->beginTransaction();
                try {
                    $pdo->prepare("UPDATE Tickets SET status = 'canceled' WHERE id = ?")->execute([$ticket_id]);
                    $balance_stmt = $pdo->prepare("SELECT balance FROM User WHERE id = ?");
                    $balance_stmt->execute([$user_id]);
                    $current_balance = $balance_stmt->fetchColumn();
                    $new_balance = $current_balance + $ticket['total_price'];
                    $pdo->prepare("UPDATE User SET balance = ? WHERE id = ?")->execute([$new_balance, $user_id]);
                    $_SESSION['user_balance'] = $new_balance;
                    $pdo->commit();

                    $message = 'Biletiniz iptal edildi ve bakiyeniz iade edildi.';
                    $message_type = 'success';
                    header("Location: dashboard.php?msg=" . urlencode($message) . "&type=success");
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            }
        }
    } catch (Exception $e) {
        $message = 'İptal işlemi sırasında hata oluştu.';
        $message_type = 'error';
        error_log("Cancel error: " . $e->getMessage());
    }
}


try {
    $pdo = new PDO("sqlite:yavuzlar.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    
    $balance = $pdo->prepare("SELECT balance FROM User WHERE id = ?");
    $balance->execute([$user_id]);
    $user_balance = $balance->fetchColumn();
    $_SESSION['user_balance'] = $user_balance;

    
    $tickets = $pdo->prepare("
        SELECT 
            tk.id,
            t.departure_city,
            t.destination_city,
            t.trip_date,          -- ✅ EKLENDİ
            t.departure_time,
            tk.total_price,
            tk.status
        FROM Tickets tk
        JOIN Trips t ON tk.trip_id = t.id
        WHERE tk.user_id = ?
        ORDER BY t.trip_date DESC, t.departure_time DESC
    ");
    $tickets->execute([$user_id]);
    $ticketList = $tickets->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $ticketList = [];
    $user_balance = 0;
    error_log("Dashboard load error: " . $e->getMessage());
}


if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
    $message_type = $_GET['type'] ?? 'info';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yolcu Paneli</title>
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
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #cce6ff;
        }
        .balance {
            background: #e6f2ff;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            color: #0077cc;
        }
        .ticket-card {
            background: white;
            border: 1px solid #d0e6ff;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .route {
            font-weight: bold;
            color: #0066cc;
            font-size: 18px;
        }
        .time {
            color: #0077cc;
            margin: 4px 0;
        }
        .price {
            font-weight: bold;
            color: #0077cc;
        }
        .status-active { color: #28a745; }
        .status-canceled { color: #dc3545; }
        .btn-cancel {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-cancel:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .btn-pdf {
            background: #17a2b8;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
            margin-left: 8px;
        }
        .btn-pdf:hover {
            background: #138496;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            text-align: center;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .cancel-info {
            font-size: 13px;
            color: #888;
            margin-top: 6px;
        }
        .actions {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }

        
        .btn-header {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            color: white;
            display: inline-block;
            text-align: center;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        .btn-blue {
            background-color: #1877F2;
        }
        .btn-blue:hover {
            background-color: #166fe5;
        }
        .btn-red {
            background-color: #dc3545;
        }
        .btn-red:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div>
                <h2>Merhaba, <?= $user_name ?>!</h2>
                <div class="balance">Bakiye: <?= number_format($user_balance, 2, ',', '.') ?> ₺</div>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="index.php" class="btn-header btn-blue">Anasayfa</a>
                <a href="logout.php" class="btn-header btn-red">Çıkış Yap</a>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>"><?= $message ?></div>
        <?php endif; ?>

        <h3>🎟️ Biletlerim</h3>
        <?php if (!empty($ticketList)): ?>
            <?php foreach ($ticketList as $tk): ?>
                <div class="ticket-card">
                    <div class="route">
                        <?= htmlspecialchars($tk['departure_city']) ?> → <?= htmlspecialchars($tk['destination_city']) ?>
                    </div>
                    <div class="time">
                        Kalkış: <?= date('d.m.Y H:i', strtotime($tk['trip_date'] . ' ' . $tk['departure_time'])) ?>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                        <span class="price"><?= number_format($tk['total_price'], 0, ',', '.') ?> ₺</span>
                        <span class="<?= $tk['status'] === 'active' ? 'status-active' : 'status-canceled' ?>">
                            <?= $tk['status'] === 'active' ? 'Aktif' : 'İptal Edildi' ?>
                        </span>
                    </div>

                    <?php if ($tk['status'] === 'active'): ?>
                        <?php
                        $departure = new DateTime($tk['trip_date'] . ' ' . $tk['departure_time']);
                        $now = new DateTime();
                        $cutoff = clone $departure;
                        $cutoff->sub(new DateInterval('PT1H'));
                        $canCancel = ($now <= $cutoff);
                        ?>
                        <div class="actions">
                            <form method="POST" style="display:inline;" 
                                onsubmit="return confirm('İptal etmek istediğinize emin misiniz?');">
                                <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($tk['id']) ?>">
                                <button type="submit" name="cancel_ticket" class="btn-cancel"
                                    <?= !$canCancel ? 'disabled title="İptal süresi doldu"' : 'title="Kalkıştan 1 saat öncesine kadar iptal edilebilir"' ?>>
                                    İptal Et
                                </button>
                            </form>
                            
                            <a href="ticket_pdf.php?ticket_id=<?= urlencode($tk['id']) ?>" 
                               class="btn-pdf"
                               target="_blank"
                               title="Bileti PDF olarak indir">
                                PDF İndir
                            </a>
                        </div>

                        <?php if (!$canCancel): ?>
                            <div class="cancel-info">❌ İptal süresi doldu (Kalkış: <?= date('d.m.Y H:i', strtotime($tk['trip_date'] . ' ' . $tk['departure_time'])) ?>)</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Henüz biletiniz yok.</p>
        <?php endif; ?>

        <div style="margin-top: 30px; text-align: center;">
            <a href="add_balance.php" style="color: #0077cc; font-weight: 600;">💰 Bakiye Yükle</a>
        </div>
    </div>
</body>
</html>

     
     
