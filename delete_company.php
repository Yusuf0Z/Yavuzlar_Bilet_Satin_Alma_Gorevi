<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

try {
    $pdo = new PDO("sqlite:yavuzlar.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $company_id = $_GET['id'] ?? null;
    if (!$company_id) {
        throw new Exception("Geçersiz şirket ID.");
    }

    
    $check = $pdo->prepare("SELECT id FROM Bus_Company WHERE id = ?");
    $check->execute([$company_id]);
    if (!$check->fetch()) {
        throw new Exception("Şirket bulunamadı.");
    }

   
    $pdo->prepare("DELETE FROM Trips WHERE company_id = ?")->execute([$company_id]);

    
    $pdo->prepare("UPDATE User SET company_id = NULL WHERE company_id = ?")->execute([$company_id]);

    
    $pdo->prepare("DELETE FROM Bus_Company WHERE id = ?")->execute([$company_id]);

    header("Location: manage_companies.php?message=Şirket başarıyla silindi.&type=success");
    exit;

} catch (Exception $e) {
    header("Location: manage_companies.php?message=" . urlencode($e->getMessage()) . "&type=error");
    exit;
}
?>