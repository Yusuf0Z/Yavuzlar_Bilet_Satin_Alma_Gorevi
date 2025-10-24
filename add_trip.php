<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php"); 
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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $company_id = $_POST['company_id'] ?? '';
        $departure_city = trim($_POST['departure_city'] ?? '');
        $destination_city = trim($_POST['destination_city'] ?? '');
        $trip_date = $_POST['trip_date'] ?? ''; // YENİ: Sefer tarihi
        $departure_time = $_POST['departure_time'] ?? '';
        $arrival_time = $_POST['arrival_time'] ?? '';
        $price = (int)($_POST['price'] ?? 0);
        $capacity = (int)($_POST['capacity'] ?? 0);

    
        if (empty($company_id) || empty($departure_city) || empty($destination_city) || 
            empty($trip_date) || empty($departure_time) || empty($arrival_time) || 
            $price <= 0 || $capacity <= 0) {
            $message = 'Tüm alanlar zorunludur ve geçerli olmalıdır.';
            $message_type = 'error';
        } 
        
        elseif (strtotime($trip_date . ' ' . $arrival_time) <= strtotime($trip_date . ' ' . $departure_time)) {
            $message = 'Varış saati, kalkış saatinden sonra olmalıdır.';
            $message_type = 'error';
        } 
        else {
            
            $checkComp = $pdo->prepare("SELECT id FROM Bus_Company WHERE id = ?");
            $checkComp->execute([$company_id]);
            if (!$checkComp->fetch()) {
                $message = 'Geçersiz şirket seçimi.';
                $message_type = 'error';
            } else {
                
                $stmt = $pdo->prepare("
                    INSERT INTO Trips (
                        id, company_id, departure_city, destination_city,
                        trip_date, departure_time, arrival_time,
                        price, capacity, created_date
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
                ");
                $trip_id = uniqid('trip_');
                $stmt->execute([
                    $trip_id,
                    $company_id,
                    $departure_city,
                    $destination_city,
                    $trip_date,          
                    $departure_time,
                    $arrival_time,
                    $price,
                    $capacity
                ]);

                $message = 'Yeni sefer başarıyla eklendi!';
                $message_type = 'success';
            }
        }
    }
} catch (Exception $e) {
    $message = 'Bir hata oluştu.';
    $message_type = 'error';
    error_log("Add trip error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Sefer Ekle (Admin)</title>
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
        input[type="number"],
        input[type="date"],
        input[type="time"],
        select {
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
        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2> Yeni Sefer Ekle</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="company_id">Şirket *</label>
                <select name="company_id" id="company_id" required>
                    <option value="">Seçiniz</option>
                    <?php foreach ($companies as $comp): ?>
                        <option value="<?= htmlspecialchars($comp['id']) ?>">
                            <?= htmlspecialchars($comp['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="departure_city">Kalkış Şehri *</label>
                <input type="text" name="departure_city" id="departure_city" required>
            </div>

            <div class="form-group">
                <label for="destination_city">Varış Şehri *</label>
                <input type="text" name="destination_city" id="destination_city" required>
            </div>

            <div class="form-group">
                <label for="trip_date">Sefer Tarihi *</label>
                <input type="date" name="trip_date" id="trip_date" required>
            </div>

            <div class="form-group">
                <label for="departure_time">Kalkış Saati *</label>
                <input type="time" name="departure_time" id="departure_time" required>
            </div>

            <div class="form-group">
                <label for="arrival_time">Varış Saati *</label>
                <input type="time" name="arrival_time" id="arrival_time" required>
            </div>

            <div class="form-group">
                <label for="price">Fiyat (TL) *</label>
                <input type="number" name="price" id="price" min="1" required>
            </div>

            <div class="form-group">
                <label for="capacity">Kapasite *</label>
                <input type="number" name="capacity" id="capacity" min="1" required>
            </div>

            <button type="submit" class="btn">Seferi Ekle</button>
        </form>

        <a href="admin_dashboard.php" class="back-link">← Admin Paneline Dön</a>
    </div>
</body>
</html>