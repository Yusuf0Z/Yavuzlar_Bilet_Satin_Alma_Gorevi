<?php

session_start();

$loggedIn = false;
$userRole = null;
if (isset($_SESSION['user_id'])) {
    $loggedIn = true;
    $userRole = $_SESSION['user_role'];
}

$message = "";
$message_type = "";
if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
    $message_type = $_GET['type'] ?? 'info';
}


$cities = [];

try {
    $pdo = new PDO("sqlite:yavuzlar.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $cityStmt = $pdo->query("
        SELECT departure_city FROM Trips
        UNION
        SELECT destination_city FROM Trips
        ORDER BY 1
    ");
    $cities = $cityStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    error_log("City load error: " . $e->getMessage());
}


$departure_city = $_GET['departure_city'] ?? "";
$destination_city = $_GET['destination_city'] ?? "";


$trips = [];

try {
    $sql = "
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
        WHERE datetime(t.trip_date || ' ' || t.departure_time) >= datetime('now')
    ";
    $params = [];

    if (!empty($departure_city)) {
        $sql .= " AND t.departure_city = ?";
        $params[] = $departure_city;
    }
    if (!empty($destination_city)) {
        $sql .= " AND t.destination_city = ?";
        $params[] = $destination_city;
    }

    $sql .= " ORDER BY t.trip_date ASC, t.departure_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Homepage trips error: " . $e->getMessage());
    $trips = [];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Halkın Kahramanı Turizm - Anasayfa</title>
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
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #cce6ff;
            position: relative;
        }
        h1 {
            color: #0055aa;
            margin: 0;
        }
        .subtitle {
            color: #0077cc;
            margin-top: 8px;
            font-size: 18px;
        }
        .top-nav {
            position: absolute;
            top: 0;
            right: 0;
            display: flex;
            gap: 10px;
        }
        .nav-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-account { background: #0077cc; color: white; }
        .btn-login { background: #28a745; color: white; }
        .btn-logout { background: #d9534f; color: white; }

        
        .filter-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border: 1px solid #d0e6ff;
        }
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #004080;
        }
        select, .btn-filter {
            width: 100%;
            padding: 10px;
            border: 1px solid #b3d1ff;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .btn-filter {
            background: #0077cc;
            color: white;
            border: none;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-filter:hover {
            background: #005fa3;
        }

        
        .trip-card {
            background: white;
            border: 1px solid #d0e6ff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(0, 51, 102, 0.08);
        }
        .route {
            font-size: 20px;
            font-weight: bold;
            color: #0066cc;
            margin-bottom: 10px;
        }
        .time-info {
            color: #0077cc;
            font-weight: 600;
            margin: 8px 0;
        }
        .details {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #444;
            margin: 10px 0;
        }
        .price {
            font-size: 22px;
            color: #0077cc;
            font-weight: bold;
        }

        
        .btn-buy {
            background: #28a745;
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
            transition: background 0.2s;
        }
        .btn-buy:hover {
            background: #218838;
        }

        
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            text-align: center;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

        .no-trips {
            text-align: center;
            color: #777;
            padding: 30px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>0(Sıfır) Bilet </h1>
            <div class="subtitle">Güncel seferleri görüntüleyin</div>
            
            <div class="top-nav">
                <?php if ($loggedIn): ?>
                    <?php
                    $dashboardUrl = 'dashboard.php';
                    if ($userRole === 'admin') $dashboardUrl = 'admin_dashboard.php';
                    elseif ($userRole === 'company') $dashboardUrl = 'company_dashboard.php?company_id=' . ($_SESSION['company_id'] ?? '');
                    ?>
                    <a href="<?= htmlspecialchars($dashboardUrl) ?>" class="nav-btn btn-account">Hesabım</a>
                    <a href="logout.php" class="nav-btn btn-logout">Çıkış Yap</a>
                <?php else: ?>
                    <a href="login.php" class="nav-btn btn-login">Giriş Yap</a>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>"><?= $message ?></div>
        <?php endif; ?>

        
        <div class="filter-form">
            <h3>📍 Sefer Ara</h3>
            <form method="GET">
                <div class="form-row">
                    <div class="form-group">
                        <label for="departure_city">Kalkış Şehri</label>
                        <select id="departure_city" name="departure_city">
                            <option value="">Tümü</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?= htmlspecialchars($city) ?>" <?= $departure_city === $city ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($city) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="destination_city">Varış Şehri</label>
                        <select id="destination_city" name="destination_city">
                            <option value="">Tümü</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?= htmlspecialchars($city) ?>" <?= $destination_city === $city ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($city) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-filter">Seferleri Listele</button>
            </form>
        </div>

        
        <?php if (!empty($trips)): ?>
            <?php foreach ($trips as $trip): ?>
                <div class="trip-card">
                    <div class="route">
                        <?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?>
                    </div>
                    <div class="time-info">
                        Kalkış: <?= date('d.m.Y H:i', strtotime($trip['trip_date'] . ' ' . $trip['departure_time'])) ?> |
                        Varış: <?= date('d.m.Y H:i', strtotime($trip['trip_date'] . ' ' . $trip['arrival_time'])) ?>
                    </div>
                    <div class="details">
                        <span>Şirket: <?= htmlspecialchars($trip['company_name']) ?></span>
                        <span>Kapasite: <?= (int)$trip['capacity'] ?> kişi</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                        <div class="price"><?= number_format($trip['price'], 0, ',', '.') ?> ₺</div>
                        
                        <?php if ($loggedIn && $userRole === 'user'): ?>
                            
                            <a href="buy_ticket.php?trip_id=<?= urlencode($trip['id']) ?>" class="btn-buy">
                                Bilet Al
                            </a>
                        <?php else: ?>
                            
                            <a href="login.php?msg=Bilet%20satın%20almadan%20önce%20lütfen%20giriş%20yapın.&type=info" class="btn-buy">
                                Bilet Al
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-trips">
                Şu anda gösterilecek aktif sefer bulunmamaktadır.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>