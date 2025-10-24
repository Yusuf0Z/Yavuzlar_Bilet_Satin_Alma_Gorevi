<?php
// admin_dashboard.php — Tam yetkili admin paneli
session_start();

// Sadece admin girebilir
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}

$user_name = htmlspecialchars($_SESSION['user_name']);

try {
    $pdo = new PDO("sqlite:yavuzlar.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // İstatistikler
    $stats = [
        'companies' => $pdo->query("SELECT COUNT(*) FROM Bus_Company")->fetchColumn(),
        'trips' => $pdo->query("SELECT COUNT(*) FROM Trips")->fetchColumn(),
        'users' => $pdo->query("SELECT COUNT(*) FROM User WHERE role = 'user'")->fetchColumn(),
        'company_admins' => $pdo->query("SELECT COUNT(*) FROM User WHERE role = 'company'")->fetchColumn(),
        'active_tickets' => $pdo->query("SELECT COUNT(*) FROM Tickets WHERE status = 'active'")->fetchColumn(),
    ];

} catch (Exception $e) {
    $error = "Veri yüklenemedi.";
    error_log("Admin dashboard error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yönetici Paneli</title>
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
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-gray);
        }
        h1 {
            color: var(--facebook-blue);
            margin: 0;
            font-size: 24px;
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
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--white);
            border: 1px solid var(--light-gray);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: var(--facebook-blue);
            margin: 10px 0;
        }
        .stat-label {
            color: #0055aa;
            font-size: 14px;
        }
        .quick-links {
            background: var(--white);
            border: 1px solid var(--light-gray);
            border-radius: 10px;
            padding: 20px;
        }
        .quick-links h2 {
            color: var(--facebook-blue);
            margin-top: 0;
            font-size: 20px;
        }
        .quick-links ul {
            list-style: none;
            padding: 0;
        }
        .quick-links li {
            margin: 12px 0;
        }
        .quick-links a {
            color: var(--facebook-blue);
            text-decoration: none;
            font-weight: 600;
            padding: 8px 0;
            display: inline-block;
        }
        .quick-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Yönetici Paneli</h1>
            <a href="logout.php" class="logout-btn">Çıkış Yap</a>
        </header>

        <p>Hoş geldiniz, <strong><?= htmlspecialchars($user_name) ?></strong>!</p>

        <!-- İstatistikler -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-label">Şirketler</div>
                <div class="stat-value"><?= $stats['companies'] ?? 0 ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Seferler</div>
                <div class="stat-value"><?= $stats['trips'] ?? 0 ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Yolcular</div>
                <div class="stat-value"><?= $stats['users'] ?? 0 ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Firma Adminleri</div>
                <div class="stat-value"><?= $stats['company_admins'] ?? 0 ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Aktif Biletler</div>
                <div class="stat-value"><?= $stats['active_tickets'] ?? 0 ?></div>
            </div>
        </div>

        <!-- Hızlı Erişim -->
        <div class="quick-links">
            <h2>Yönetim İşlemleri</h2>
            <ul>
                <li><a href="manage_companies.php">🏢 Şirketleri Yönet</a></li>
                <li><a href="manage_trips.php">🚌 Tüm Seferleri Yönet</a></li>
                <li><a href="manage_users.php">👤 Yolcuları Yönet</a></li>
                <li><a href="create_company_admin.php">👨‍💼 Yeni Firma Admini Oluştur</a></li>
                <li><a href="manage_coupons.php">🎟️Sistem Kuponlarını Yönet</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
</body>
</html>