<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company') {
    die("Yetkisiz erişim.");
}

$company_id = $_GET['company_id'] ?? null;
if (!$company_id || $company_id !== ($_SESSION['company_id'] ?? null)) {
    die("Geçersiz firma kimliği.");
}


$db = new PDO('sqlite:yavuzlar.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


$stmt = $db->prepare("SELECT name FROM Bus_Company WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);
$company_name = $company ? $company['name'] : 'Şirket';


$edit_trip = null;
if (isset($_GET['edit_trip'])) {
    $trip_id = $_GET['edit_trip'];
    $stmt = $db->prepare("SELECT * FROM Trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$trip_id, $company_id]);
    $edit_trip = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$edit_trip) die("Sefer bulunamadı.");
}


$edit_coupon = null;
if (isset($_GET['edit_coupon'])) {
    $coupon_id = $_GET['edit_coupon'];
    $stmt = $db->prepare("SELECT * FROM Coupons WHERE id = ? AND company_id = ?");
    $stmt->execute([$coupon_id, $company_id]);
    $edit_coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$edit_coupon) die("Kupon bulunamadı.");
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_trip'])) {
    $trip_id = $_POST['trip_id'];
    $departure_city = trim($_POST['departure_city']);
    $destination_city = trim($_POST['destination_city']);
    $trip_date = $_POST['trip_date'];
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $price = (int)$_POST['price'];
    $capacity = (int)$_POST['capacity'];

    $stmt = $db->prepare("
        UPDATE Trips SET
            departure_city = ?,
            destination_city = ?,
            trip_date = ?,
            departure_time = ?,
            arrival_time = ?,
            price = ?,
            capacity = ?
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([
        $departure_city,
        $destination_city,
        $trip_date,
        $departure_time,
        $arrival_time,
        $price,
        $capacity,
        $trip_id,
        $company_id
    ]);
    header('Location: company_dashboard.php?company_id=' . urlencode($company_id));
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_coupon'])) {
    $coupon_id = $_POST['coupon_id'];
    $code = trim($_POST['code']);
    $discount = (float)$_POST['discount'];
    $usage_limit = (int)$_POST['usage_limit'];
    $expire_date = $_POST['expire_date'];

    $stmt = $db->prepare("
        UPDATE Coupons SET
            code = ?,
            discount = ?,
            usage_limit = ?,
            expire_date = ?
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([
        $code,
        $discount,
        $usage_limit,
        $expire_date,
        $coupon_id,
        $company_id
    ]);
    header('Location: company_dashboard.php?company_id=' . urlencode($company_id));
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trip'])) {
    $departure_city = trim($_POST['departure_city']);
    $destination_city = trim($_POST['destination_city']);
    $trip_date = $_POST['trip_date'];
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $price = (int)$_POST['price'];
    $capacity = (int)$_POST['capacity'];

    $stmt = $db->prepare("
        INSERT INTO Trips (
            id, company_id, departure_city, destination_city,
            trip_date, departure_time, arrival_time, price, capacity, created_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
    ");
    $stmt->execute([
        uniqid('trip_'),
        $company_id,
        $departure_city,
        $destination_city,
        $trip_date,
        $departure_time,
        $arrival_time,
        $price,
        $capacity
    ]);
    header('Location: company_dashboard.php?company_id=' . urlencode($company_id));
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = trim($_POST['code']);
    $discount = (float)$_POST['discount'];
    $usage_limit = (int)$_POST['usage_limit'];
    $expire_date = $_POST['expire_date'];

    $stmt = $db->prepare("
        INSERT INTO Coupons (id, code, discount, usage_limit, expire_date, created_at, company_id)
        VALUES (?, ?, ?, ?, ?, datetime('now'), ?)
    ");
    $stmt->execute([
        uniqid('cpn_'),
        $code,
        $discount,
        $usage_limit,
        $expire_date,
        $company_id
    ]);
    header('Location: company_dashboard.php?company_id=' . urlencode($company_id));
    exit;
}


if (isset($_GET['delete_trip'])) {
    $trip_id = $_GET['delete_trip'];
    $stmt = $db->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$trip_id, $company_id]);
    header('Location: company_dashboard.php?company_id=' . urlencode($company_id));
    exit;
}


if (isset($_GET['delete_coupon'])) {
    $coupon_id = $_GET['delete_coupon'];
    $stmt = $db->prepare("DELETE FROM Coupons WHERE id = ? AND company_id = ?");
    $stmt->execute([$coupon_id, $company_id]);
    header('Location: company_dashboard.php?company_id=' . urlencode($company_id));
    exit;
}


$stmt = $db->prepare("SELECT * FROM Trips WHERE company_id = ? ORDER BY trip_date, departure_time");
$stmt->execute([$company_id]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT * FROM Coupons WHERE company_id = ? ORDER BY expire_date DESC");
$stmt->execute([$company_id]);
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Şirket Yönetim Paneli</title>
    <style>
        :root {
            --facebook-blue: #1877F2;
            --light-bg: #f0f2f5;
            --white: #ffffff;
            --gray: #65676b;
            --light-gray: #e4e6eb;
            --danger: #dc3545;
            --warning: #ffc107;
        }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: var(--light-bg);
            margin: 0;
            padding: 0;
            color: #1c1e21;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 16px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            background: var(--white);
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .header h1 {
            color: var(--facebook-blue);
            margin: 0;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .header h1::before {
            content: "🏢";
            font-size: 24px;
        }

        
        .company-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 8px;
        }
        .company-badge {
            font-size: 20px;
            color: #0066cc;
        }
        .company-info {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #e7f3ff;
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid #cce6ff;
        }
        .company-label {
            font-weight: 600;
            color: #0055aa;
            font-size: 14px;
        }
        .company-name {
            font-weight: 600;
            color: #0066cc;
            font-size: 15px;
        }
        .btn-dashboard {
            padding: 8px 16px;
            background-color: #1877F2;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-block;
            transition: background-color 0.2s;
        }
        .btn-dashboard:hover {
            background-color: #166fe5;
        }

        
        .logout-btn {
            padding: 8px 16px;
            background-color: var(--danger);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }

        
        .card {
            background: var(--white);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .card h2 {
            color: var(--facebook-blue);
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--light-gray);
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--gray);
        }
        .form-control {
            width: 98%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin-right: 6px;
            margin-bottom: 6px;
        }
        .btn-primary {
            background-color: var(--facebook-blue);
            color: white;
        }
        .btn-primary:hover {
            background-color: #166fe5;
        }
        .btn-secondary {
            background-color: #65676b;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #545659;
        }
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
        }
        th {
            color: var(--gray);
            font-weight: 600;
        }
        .no-data {
            text-align: center;
            color: var(--gray);
            padding: 20px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Şirket Yönetim Paneli</h1>
                
                
                <div class="company-header">
                    <div class="company-info">
                        <span class="company-label">Şirket:</span>
                        <span class="company-name"><?= htmlspecialchars($company_name) ?></span>
                    </div>
                    <a href="company_dashboard.php?company_id=<?= urlencode($company_id) ?>" class="btn-dashboard">
                        📊 Dashboard
                    </a>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">Çıkış Yap</a>
        </div>

        
        <?php if ($edit_trip): ?>
        <div class="card">
            <h2>Seferi Düzenle</h2>
            <form method="POST">
                <input type="hidden" name="trip_id" value="<?= htmlspecialchars($edit_trip['id']) ?>">
                <div class="form-group">
                    <label>Kalkış Şehri</label>
                    <input type="text" name="departure_city" class="form-control" value="<?= htmlspecialchars($edit_trip['departure_city']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Varış Şehri</label>
                    <input type="text" name="destination_city" class="form-control" value="<?= htmlspecialchars($edit_trip['destination_city']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Sefer Tarihi</label>
                    <input type="date" name="trip_date" class="form-control" value="<?= htmlspecialchars($edit_trip['trip_date']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Kalkış Saati</label>
                    <input type="time" name="departure_time" class="form-control" value="<?= htmlspecialchars($edit_trip['departure_time']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Varış Saati</label>
                    <input type="time" name="arrival_time" class="form-control" value="<?= htmlspecialchars($edit_trip['arrival_time']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Fiyat (TL)</label>
                    <input type="number" name="price" class="form-control" value="<?= htmlspecialchars($edit_trip['price']) ?>" min="0" required>
                </div>
                <div class="form-group">
                    <label>Kapasite</label>
                    <input type="number" name="capacity" class="form-control" value="<?= htmlspecialchars($edit_trip['capacity']) ?>" min="1" required>
                </div>
                <button type="submit" name="update_trip" class="btn btn-primary">Güncelle</button>
                <a href="company_dashboard.php?company_id=<?= urlencode($company_id) ?>" class="btn btn-secondary">İptal</a>
            </form>
        </div>
        <?php endif; ?>

        
        <?php if ($edit_coupon): ?>
        <div class="card">
            <h2>Kuponu Düzenle</h2>
            <form method="POST">
                <input type="hidden" name="coupon_id" value="<?= htmlspecialchars($edit_coupon['id']) ?>">
                <div class="form-group">
                    <label>Kupon Kodu</label>
                    <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($edit_coupon['code']) ?>" required>
                </div>
                <div class="form-group">
                    <label>İndirim Oranı (%)</label>
                    <input type="number" name="discount" class="form-control" value="<?= htmlspecialchars($edit_coupon['discount']) ?>" min="0" max="100" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Kullanım Limiti</label>
                    <input type="number" name="usage_limit" class="form-control" value="<?= htmlspecialchars($edit_coupon['usage_limit']) ?>" min="1" required>
                </div>
                <div class="form-group">
                    <label>Son Kullanım Tarihi</label>
                    <input type="date" name="expire_date" class="form-control" value="<?= htmlspecialchars($edit_coupon['expire_date']) ?>" required>
                </div>
                <button type="submit" name="update_coupon" class="btn btn-primary">Güncelle</button>
                <a href="company_dashboard.php?company_id=<?= urlencode($company_id) ?>" class="btn btn-secondary">İptal</a>
            </form>
        </div>
        <?php endif; ?>

        
        <div class="card">
            <h2>Yeni Sefer Oluştur</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Kalkış Şehri</label>
                    <input type="text" name="departure_city" class="form-control" placeholder="Ankara" required>
                </div>
                <div class="form-group">
                    <label>Varış Şehri</label>
                    <input type="text" name="destination_city" class="form-control" placeholder="İstanbul" required>
                </div>
                <div class="form-group">
                    <label>Sefer Tarihi</label>
                    <input type="date" name="trip_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Kalkış Saati</label>
                    <input type="time" name="departure_time" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Varış Saati</label>
                    <input type="time" name="arrival_time" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Fiyat (TL)</label>
                    <input type="number" name="price" class="form-control" min="0" placeholder="150" required>
                </div>
                <div class="form-group">
                    <label>Kapasite</label>
                    <input type="number" name="capacity" class="form-control" min="1" placeholder="40" required>
                </div>
                <button type="submit" name="add_trip" class="btn btn-primary">Sefer Ekle</button>
            </form>
        </div>

    
        <div class="card">
            <h2>Mevcut Seferler</h2>
            <?php if ($trips): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Kalkış</th>
                            <th>Varış</th>
                            <th>Tarih</th>
                            <th>Kalkış</th>
                            <th>Varış</th>
                            <th>Fiyat</th>
                            <th>Kapasite</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trips as $trip): ?>
                        <tr>
                            <td><?= htmlspecialchars($trip['departure_city']) ?></td>
                            <td><?= htmlspecialchars($trip['destination_city']) ?></td>
                            <td><?= htmlspecialchars($trip['trip_date']) ?></td>
                            <td><?= htmlspecialchars($trip['departure_time']) ?></td>
                            <td><?= htmlspecialchars($trip['arrival_time']) ?></td>
                            <td><?= htmlspecialchars($trip['price']) ?> TL</td>
                            <td><?= htmlspecialchars($trip['capacity']) ?></td>
                            <td>
                                <a href="?company_id=<?= urlencode($company_id) ?>&edit_trip=<?= urlencode($trip['id']) ?>" 
                                   class="btn btn-primary">Düzenle</a>
                                <a href="?company_id=<?= urlencode($company_id) ?>&delete_trip=<?= urlencode($trip['id']) ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Bu seferi silmek istediğinize emin misiniz?')">Sil</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">Henüz seferiniz yok.</div>
            <?php endif; ?>
        </div>

        
        <div class="card">
            <h2>Yeni İndirim Kuponu Oluştur</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Kupon Kodu</label>
                    <input type="text" name="code" class="form-control" placeholder="YAZ2025" required>
                </div>
                <div class="form-group">
                    <label>İndirim Oranı (%)</label>
                    <input type="number" name="discount" class="form-control" min="0" max="100" step="0.01" placeholder="10.00" required>
                </div>
                <div class="form-group">
                    <label>Kullanım Limiti</label>
                    <input type="number" name="usage_limit" class="form-control" min="1" placeholder="100" required>
                </div>
                <div class="form-group">
                    <label>Son Kullanım Tarihi</label>
                    <input type="date" name="expire_date" class="form-control" required>
                </div>
                <button type="submit" name="add_coupon" class="btn btn-primary">Kupon Ekle</button>
            </form>
        </div>

        
        <div class="card">
            <h2>Mevcut Kuponlar</h2>
            <?php if ($coupons): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Kod</th>
                            <th>İndirim (%)</th>
                            <th>Kullanım Limiti</th>
                            <th>Son Kullanım</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coupons as $coupon): ?>
                        <tr>
                            <td><?= htmlspecialchars($coupon['code']) ?></td>
                            <td><?= htmlspecialchars($coupon['discount']) ?>%</td>
                            <td><?= htmlspecialchars($coupon['usage_limit']) ?></td>
                            <td><?= htmlspecialchars($coupon['expire_date']) ?></td>
                            <td>
                                <a href="?company_id=<?= urlencode($company_id) ?>&edit_coupon=<?= urlencode($coupon['id']) ?>" 
                                   class="btn btn-primary">Düzenle</a>
                                <a href="?company_id=<?= urlencode($company_id) ?>&delete_coupon=<?= urlencode($coupon['id']) ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Bu kuponu silmek istediğinize emin misiniz?')">Sil</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">Kuponunuz yok.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>