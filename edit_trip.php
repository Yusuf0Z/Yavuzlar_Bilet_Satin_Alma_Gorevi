<?php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company') {
    header("Location: company_login.php");
    exit;
}

$company_id = $_SESSION['company_id'] ?? null;
if (!$company_id) die("Şirket bilgisi eksik.");

try {
    $pdo = new PDO("sqlite:yavuzlar.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $trip_id = $_GET['id'] ?? null;
    if (!$trip_id) die("Geçersiz sefer ID.");

    
    $stmt = $pdo->prepare("
        SELECT * FROM Trips 
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$trip_id, $company_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$trip) die("Sefer bulunamadı.");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $departure_city = $_POST['departure_city'];
        $destination_city = $_POST['destination_city'];
        $departure_time = $_POST['departure_time'];
        $arrival_time = $_POST['arrival_time'];
        $price = (int)$_POST['price'];
        $capacity = (int)$_POST['capacity'];

        $update = $pdo->prepare("
            UPDATE Trips 
            SET departure_city = ?, destination_city = ?, departure_time = ?, 
                arrival_time = ?, price = ?, capacity = ?
            WHERE id = ? AND company_id = ?
        ");
        $update->execute([
            $departure_city, $destination_city, $departure_time, 
            $arrival_time, $price, $capacity, $trip_id, $company_id
        ]);

        header("Location: company_dashboard.php?msg=Sefer başarıyla güncellendi.&type=success");
        exit;
    }

} catch (Exception $e) {
    die("Hata: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sefer Düzenle</title>
    <style>
        body { font-family: Arial; background: #f0f8ff; padding: 20px; }
        .container { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn { padding: 10px 20px; background: #0077cc; color: white; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Sefer Düzenle</h2>
        <form method="POST">
            <div class="form-group">
                <label>Kalkış Şehri</label>
                <input type="text" name="departure_city" value="<?= htmlspecialchars($trip['departure_city']) ?>" required>
            </div>
            <div class="form-group">
                <label>Varış Şehri</label>
                <input type="text" name="destination_city" value="<?= htmlspecialchars($trip['destination_city']) ?>" required>
            </div>
            <div class="form-group">
                <label>Kalkış Tarihi ve Saati</label>
                <input type="datetime-local" name="departure_time" 
                       value="<?= date('Y-m-d\TH:i', strtotime($trip['departure_time'])) ?>" required>
            </div>
            <div class="form-group">
                <label>Varış Tarihi ve Saati</label>
                <input type="datetime-local" name="arrival_time" 
                       value="<?= date('Y-m-d\TH:i', strtotime($trip['arrival_time'])) ?>" required>
            </div>
            <div class="form-group">
                <label>Ücret (₺)</label>
                <input type="number" name="price" value="<?= $trip['price'] ?>" min="1" required>
            </div>
            <div class="form-group">
                <label>Kapasite</label>
                <input type="number" name="capacity" value="<?= $trip['capacity'] ?>" min="1" required>
            </div>
            <button type="submit" class="btn">Güncelle</button>
            <a href="company_dashboard.php" style="display:inline-block; margin-left:10px;">İptal</a>
        </form>
    </div>
</body>
</html>