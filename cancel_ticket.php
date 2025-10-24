<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company') {
    header("Location: company_login.php");
    exit;
}

$company_id = $_SESSION['company_id'] ?? null;
$ticket_id = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;

if (!$company_id || $ticket_id <= 0) {
    die("<h2 style='color:red;'>Geçersiz istek: Eksik veya hatalı parametre.</h2>");
}

try {
    $pdo = new PDO("sqlite:yavuzlar.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    
    $cols = $pdo->query("PRAGMA table_info(Tickets)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('status', $cols)) {
        $pdo->exec("ALTER TABLE Tickets ADD COLUMN status TEXT DEFAULT 'active'");
    }

    
    $stmt = $pdo->prepare("
        SELECT t.id 
        FROM Tickets t
        JOIN Trips tr ON t.trip_id = tr.id
        WHERE t.id = ? AND tr.company_id = ? AND t.status = 'active'
    ");
    $stmt->execute([$ticket_id, $company_id]);

    if (!$stmt->fetch()) {
        die("<h2 style='color:orange;'>Bu bilet iptal edilemez veya size ait değil.</h2>");
    }

    
    $pdo->prepare("UPDATE Tickets SET status = 'cancelled' WHERE id = ?")->execute([$ticket_id]);

    header("Location: company_dashboard.php?msg=cancelled_success");
    exit;

} catch (Exception $e) {
    error_log("Cancel error: " . $e->getMessage());
    die("<h2 style='color:red;'>İptal işlemi başarısız oldu. Lütfen tekrar deneyin.</h2>");
}
?>