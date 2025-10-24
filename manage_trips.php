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

    
    $stmt = $pdo->prepare("
        SELECT 
            t.id,
            t.departure_city,
            t.destination_city,
            t.trip_date,
            t.departure_time,
            t.arrival_time,
            t.price,
            t.capacity,
            c.name AS company_name
        FROM Trips t
        JOIN Bus_Company c ON t.company_id = c.id
        ORDER BY t.departure_time DESC
    ");
    $stmt->execute();
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $message = 'Seferler yüklenirken hata oluştu.';
    $message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Tüm Seferleri Yönet</title>
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
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
        }
        h2 {
            color: var(--facebook-blue);
            text-align: center;
            margin-bottom: 25px;
            font-size: 24px;
        }
        .btn {
            padding: 10px 16px;
            background-color: var(--facebook-blue);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #166fe5;
        }
        .btn-secondary {
            background-color: #6c757d;
            margin-top: 20px;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .trip-item {
            background: var(--white);
            border: 1px solid var(--light-gray);
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .route {
            font-weight: bold;
            color: #0066cc;
            font-size: 18px;
            margin-bottom: 8px;
        }
        .time {
            color: #0077cc;
            margin: 6px 0;
            font-size: 15px;
        }
        .details {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #444;
            margin: 8px 0;
        }
        .price {
            font-weight: bold;
            color: #0077cc;
            font-size: 18px;
        }
        .no-data {
            text-align: center;
            color: #777;
            padding: 30px;
            font-style: italic;
            background: var(--white);
            border-radius: 10px;
            margin-top: 10px;
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
        <h2> Tüm Seferleri Yönet</h2>

        <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <a href="add_trip.php" class="btn">+ Yeni Sefer Ekle</a>

        <?php if (!empty($trips)): ?>
            <?php foreach ($trips as $trip): ?>
                <div class="trip-item">
                    <div class="route">
                        <?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?>
                    </div>
                    <div class="time">
                        Kalkış: <?= date('d.m.Y H:i', strtotime($trip['trip_date'] . ' ' . $trip['departure_time'])) ?> •
                        Varış: <?= date('d.m.Y H:i', strtotime($trip['trip_date'] . ' ' . $trip['arrival_time'])) ?>
                    </div>
                    <div class="details">
                        <span>Şirket: <?= htmlspecialchars($trip['company_name']) ?></span>
                        <span>Kapasite: <?= (int)$trip['capacity'] ?> kişi</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                        <span class="price"><?= number_format($trip['price'], 0, ',', '.') ?> ₺</span>
                       
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-data">Henüz sefer eklenmemiş.</div>
        <?php endif; ?>

        <a href="admin_dashboard.php" class="btn btn-secondary">← Geri Dön</a>
    </div>
</body>
</html>