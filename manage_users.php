<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);
$message = '';
$message_type = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $target_user_id = $_POST['user_id'];

    
    if ($target_user_id === $user_id) {
        $message = 'Kendi hesabınızı silemezsiniz.';
        $message_type = 'error';
    } else {
        try {
            $pdo = new PDO("sqlite:yavuzlar.db");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            
            $check = $pdo->prepare("SELECT id FROM User WHERE id = ? AND role IN ('user', 'company')");
            $check->execute([$target_user_id]);
            if (!$check->fetch()) {
                $message = 'Geçersiz kullanıcı.';
                $message_type = 'error';
            } else {
                $pdo->beginTransaction();

                
                $pdo->prepare("DELETE FROM Booked_Seats WHERE ticket_id IN (SELECT id FROM Tickets WHERE user_id = ?)")->execute([$target_user_id]);
             
                $pdo->prepare("DELETE FROM User_Coupons WHERE user_id = ?")->execute([$target_user_id]);
                
                $pdo->prepare("DELETE FROM Tickets WHERE user_id = ?")->execute([$target_user_id]);
                
                $pdo->prepare("DELETE FROM User WHERE id = ?")->execute([$target_user_id]);

                $pdo->commit();
                $message = 'Kullanıcı ve tüm verileri başarıyla silindi.';
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Silme işlemi sırasında hata oluştu.';
            $message_type = 'error';
            error_log("User delete error: " . $e->getMessage());
        }
    }
}


$users = [];
try {
    $pdo = new PDO("sqlite:yavuzlar.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
        SELECT id, full_name, email, role, balance, created_at, company_id
        FROM User
        WHERE role IN ('user', 'company')
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Manage users load error: " . $e->getMessage());
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kullanıcıları Yönet</title>
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
            max-width: 1100px;
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
        .role-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .role-user { background: #0077cc; }
        .role-company { background: #17a2b8; }
        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border: 1px solid var(--light-gray);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }
        th, td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #e0ecff;
        }
        th {
            background-color: #e6f2ff;
            color: #0055aa;
            font-weight: 600;
        }
        .balance {
            font-weight: bold;
            color: #0077cc;
        }
        .no-users {
            text-align: center;
            color: #777;
            padding: 30px;
            font-style: italic;
        }
        .back-link {
            color: var(--facebook-blue);
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
            display: inline-block;
            margin-top: 20px;
        }
        .back-link:hover {
            background-color: #f0f2f5;
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
            background-color: var(--facebook-blue);
        }
        .btn-blue:hover {
            background-color: #166fe5;
        }
        .btn-red {
            background-color: var(--danger);
        }
        .btn-red:hover {
            background-color: #c82333;
        }

        
        .btn-delete {
            background-color: var(--danger);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-delete:hover {
            background-color: #c82333;
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
        <header>
            <div>
                <h1>👤 Kullanıcıları Yönet</h1>
                <p>Hoş geldiniz, <strong><?= $user_name ?></strong>!</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="index.php" class="btn-header btn-blue">Anasayfa</a>
                <a href="logout.php" class="btn-header btn-red">Çıkış Yap</a>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (!empty($users)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Ad Soyad</th>
                        <th>E-posta</th>
                        <th>Rol</th>
                        <th>Bakiye</th>
                        <th>Kayıt Tarihi</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <?php if ($u['id'] !== $user_id): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['full_name']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <?php if ($u['role'] === 'user'): ?>
                                    <span class="role-badge role-user">Yolcu</span>
                                <?php elseif ($u['role'] === 'company'): ?>
                                    <span class="role-badge role-company">Şirket Admin</span>
                                <?php endif; ?>
                            </td>
                            <td class="balance"><?= number_format($u['balance'], 2, ',', '.') ?> ₺</td>
                            <td><?= date('d.m.Y H:i', strtotime($u['created_at'])) ?></td>
                            <td>
                                <form method="POST" style="display:inline;" 
                                      onsubmit="return confirm('⚠️ Bu kullanıcı ve tüm verileri kalıcı olarak silinecek!\nDevam etmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
                                    <button type="submit" name="delete_user" class="btn-delete">Sil</button>
                                </form>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-users">
                Görüntülenecek kullanıcı bulunamadı.
            </div>
        <?php endif; ?>

        <a href="admin_dashboard.php" class="back-link">← Yönetim Paneline Dön</a>
    </div>
</body>
</html>