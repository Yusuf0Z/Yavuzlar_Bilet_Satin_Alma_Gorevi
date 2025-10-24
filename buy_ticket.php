<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: login.php?msg=Giriş yapmalısınız.&type=error");
    exit;
}

$trip_id = $_GET['trip_id'] ?? null;
if (!$trip_id) {
    header("Location: homepage.php?msg=Geçersiz sefer.&type=error");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';
$coupon_code = '';
$discount_amount = 0;
$final_price = 0;

try {
    $pdo = new PDO("sqlite:yavuzlar.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    
    $tripStmt = $pdo->prepare("
        SELECT id, departure_city, destination_city, departure_time, price, capacity
        FROM Trips 
        WHERE id = ?
    ");
    $tripStmt->execute([$trip_id]);
    $trip = $tripStmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        header("Location: homepage.php?msg=Sefer bulunamadı.&type=error");
        exit;
    }

    
    $bookedStmt = $pdo->prepare("
        SELECT seat_number 
        FROM Booked_Seats bs
        JOIN Tickets t ON bs.ticket_id = t.id
        WHERE t.trip_id = ? AND t.status = 'active'
    ");
    $bookedStmt->execute([$trip_id]);
    $booked_seats = $bookedStmt->fetchAll(PDO::FETCH_COLUMN);

    $total_seats = (int)$trip['capacity'];
    if ($total_seats <= 0) $total_seats = 38;

    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $seat_number = (int)($_POST['seat_number'] ?? 0);
        $coupon_code = trim($_POST['coupon_code'] ?? '');

        
        if ($seat_number < 1 || $seat_number > $total_seats) {
            $message = 'Geçersiz koltuk numarası.';
            $message_type = 'error';
        } elseif (in_array($seat_number, $booked_seats)) {
            $message = 'Bu koltuk zaten alınmış.';
            $message_type = 'error';
        } else {
            
            if (!empty($coupon_code)) {
                
                $couponStmt = $pdo->prepare("
                    SELECT id, discount, usage_limit, expire_date, company_id
                    FROM Coupons
                    WHERE code = ? AND expire_date >= date('now')
                ");
                $couponStmt->execute([$coupon_code]);
                $coupon = $couponStmt->fetch(PDO::FETCH_ASSOC);

                if (!$coupon) {
                    $message = 'Geçersiz veya süresi dolmuş kupon.';
                    $message_type = 'error';
                } else {
                
                    $usedCount = $pdo->prepare("
                        SELECT COUNT(*) 
                        FROM User_Coupons 
                        WHERE coupon_id = ? AND user_id = ?
                    ");
                    $usedCount->execute([$coupon['id'], $user_id]);
                    $usedTimes = $usedCount->fetchColumn();

                    if ($usedTimes >= $coupon['usage_limit']) {
                        $message = 'Bu kuponun kullanım limiti doldu.';
                        $message_type = 'error';
                    } else {
                        
                        if ($coupon['company_id'] !== null) {
                            
                            $userStmt = $pdo->prepare("SELECT company_id FROM User WHERE id = ?");
                            $userStmt->execute([$user_id]);
                            $user = $userStmt->fetch();

                            if ($user['company_id'] !== $coupon['company_id']) {
                                $message = 'Bu kupon size özel değil.';
                                $message_type = 'error';
                            } else {
                                
                                $discount_amount = ($trip['price'] * $coupon['discount']) / 100;
                                $final_price = $trip['price'] - $discount_amount;
                            }
                        } else {
                            
                            $discount_amount = ($trip['price'] * $coupon['discount']) / 100;
                            $final_price = $trip['price'] - $discount_amount;
                        }
                    }
                }
            } else {
                $final_price = $trip['price'];
            }

            
            if ($message) {
                
            } else {
                
                $balance = $pdo->prepare("SELECT balance FROM User WHERE id = ?");
                $balance->execute([$user_id]);
                $current_balance = $balance->fetchColumn();

                if ($current_balance < $final_price) {
                    $message = 'Yetersiz bakiye!';
                    $message_type = 'error';
                } else {
                    
                    $pdo->beginTransaction();
                    try {
                        $ticket_id = uniqid('tkt_');
                        $pdo->prepare("
                            INSERT INTO Tickets (id, trip_id, user_id, status, total_price)
                            VALUES (?, ?, ?, 'active', ?)
                        ")->execute([$ticket_id, $trip_id, $user_id, $final_price]);

                        $pdo->prepare("
                            INSERT INTO Booked_Seats (id, ticket_id, seat_number)
                            VALUES (?, ?, ?)
                        ")->execute([uniqid('seat_'), $ticket_id, $seat_number]);

                        
                        if (!empty($coupon_code) && isset($coupon)) {
                            $pdo->prepare("
                                INSERT INTO User_Coupons (coupon_id, user_id)
                                VALUES (?, ?)
                            ")->execute([$coupon['id'], $user_id]);
                        }

                        $new_balance = $current_balance - $final_price;
                        $pdo->prepare("UPDATE User SET balance = ? WHERE id = ?")
                             ->execute([$new_balance, $user_id]);

                        $pdo->commit();

                        header("Location: dashboard.php?msg=Biletiniz başarıyla alındı!&type=success");
                        exit;
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        throw $e;
                    }
                }
            }
        }
    }

} catch (Exception $e) {
    error_log("Buy ticket error: " . $e->getMessage());
    header("Location: homepage.php?msg=Bilet alınamadı.&type=error");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Koltuk Seçimi</title>
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
        h2 {
            text-align: center;
            color: #0055aa;
            margin-bottom: 20px;
        }
        .trip-info {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
            border: 1px solid #d0e6ff;
        }
        .bus-layout {
            position: relative;
            background: #f8fbff;
            padding: 20px;
            border-radius: 12px;
            border: 2px solid #cce6ff;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        .row {
            display: flex;
            justify-content: center;
            gap: 12px;
            width: 100%;
        }
        .seat {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .seat:hover:not(:disabled) {
            transform: scale(1.1);
        }
        .seat.selected {
            background: #28a745;
            color: white;
        }
        .seat.booked {
            background: #dc3545;
            color: white;
            cursor: not-allowed;
        }
        .seat.available {
            background: #0077cc;
            color: white;
        }
        .seat.disabled {
            background: #ccc;
            color: #666;
            cursor: not-allowed;
        }
        .steering-wheel {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 30px;
            height: 30px;
            border: 2px solid #aaa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #666;
        }
        .legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 15px 0;
            font-size: 14px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
        .available { background: #0077cc; }
        .booked { background: #dc3545; }
        .btn {
            width: 100%;
            padding: 12px;
            background: #0077cc;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #0077cc;
            text-decoration: none;
            font-weight: 600;
        }
        .note {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 10px;
        }
        .coupon-section {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            border: 1px solid #d0e6ff;
        }
        .coupon-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #b3d1ff;
            border-radius: 6px;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .coupon-btn {
            width: 100%;
            padding: 8px;
            background: #17a2b8;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .coupon-info {
            font-size: 14px;
            color: #0077cc;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🚌 Koltuk Seçimi</h2>

        <div class="trip-info">
            <div><strong><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></strong></div>
            <div>Kalkış: <?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?></div>
            <div>Ücret: <span id="original-price"><?= number_format($trip['price'], 0, ',', '.') ?> ₺</span></div>
        </div>

        
        <div class="coupon-section">
            <label for="coupon_code">İndirim Kodu (Opsiyonel)</label>
            <input type="text" id="coupon_code" name="coupon_code" class="coupon-input" placeholder="Kupon kodu girin" value="<?= htmlspecialchars($coupon_code) ?>">
            <button type="button" class="coupon-btn" onclick="applyCoupon()">Kuponu Uygula</button>
            <div class="coupon-info" id="coupon-message">
                <?php if ($discount_amount > 0): ?>
                    ✅ <?= htmlspecialchars($coupon_code) ?> kuponu uygulandı. İndirim: <?= number_format($discount_amount, 2, ',', '.') ?> ₺
                <?php endif; ?>
            </div>
        </div>

        
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color available"></div>
                <span>Boş</span>
            </div>
            <div class="legend-item">
                <div class="legend-color booked"></div>
                <span>Dolu</span>
            </div>
            <div class="legend-item">
                <div class="legend-color selected"></div>
                <span></span>
            </div>
        </div>

        
        <form method="POST" id="seat-form">
            <input type="hidden" name="seat_number" id="selected-seat" required>
            <input type="hidden" name="coupon_code" id="hidden-coupon-code" value="<?= htmlspecialchars($coupon_code) ?>">

            <div class="bus-layout">
              

                
                <div class="row">
                    <button type="button" class="seat <?= in_array(3, $booked_seats) ? 'booked' : 'available' ?>" data-seat="3" onclick="selectSeat(3)">3</button>
                    <button type="button" class="seat <?= in_array(6, $booked_seats) ? 'booked' : 'available' ?>" data-seat="6" onclick="selectSeat(6)">6</button>
                    <button type="button" class="seat <?= in_array(9, $booked_seats) ? 'booked' : 'available' ?>" data-seat="9" onclick="selectSeat(9)">9</button>
                    <button type="button" class="seat <?= in_array(12, $booked_seats) ? 'booked' : 'available' ?>" data-seat="12" onclick="selectSeat(12)">12</button>
                    <button type="button" class="seat <?= in_array(15, $booked_seats) ? 'booked' : 'available' ?>" data-seat="15" onclick="selectSeat(15)">15</button>
                    
                    <button type="button" class="seat <?= in_array(18, $booked_seats) ? 'booked' : 'available' ?>" data-seat="18" onclick="selectSeat(18)">18</button>
                    <button type="button" class="seat <?= in_array(21, $booked_seats) ? 'booked' : 'available' ?>" data-seat="21" onclick="selectSeat(21)">21</button>
                    <button type="button" class="seat <?= in_array(24, $booked_seats) ? 'booked' : 'available' ?>" data-seat="24" onclick="selectSeat(24)">24</button>
                    <button type="button" class="seat <?= in_array(27, $booked_seats) ? 'booked' : 'available' ?>" data-seat="27" onclick="selectSeat(27)">27</button>
                    <button type="button" class="seat <?= in_array(30, $booked_seats) ? 'booked' : 'available' ?>" data-seat="30" onclick="selectSeat(30)">30</button>
                    <button type="button" class="seat <?= in_array(33, $booked_seats) ? 'booked' : 'available' ?>" data-seat="33" onclick="selectSeat(33)">33</button>
                    <button type="button" class="seat <?= in_array(36, $booked_seats) ? 'booked' : 'available' ?>" data-seat="36" onclick="selectSeat(36)">36</button>
                </div>

                <div class="row">
                    <button type="button" class="seat <?= in_array(2, $booked_seats) ? 'booked' : 'available' ?>" data-seat="2" onclick="selectSeat(2)">2</button>
                    <button type="button" class="seat <?= in_array(5, $booked_seats) ? 'booked' : 'available' ?>" data-seat="5" onclick="selectSeat(5)">5</button>
                    <button type="button" class="seat <?= in_array(8, $booked_seats) ? 'booked' : 'available' ?>" data-seat="8" onclick="selectSeat(8)">8</button>
                    <button type="button" class="seat <?= in_array(11, $booked_seats) ? 'booked' : 'available' ?>" data-seat="11" onclick="selectSeat(11)">11</button>
                    <button type="button" class="seat <?= in_array(14, $booked_seats) ? 'booked' : 'available' ?>" data-seat="14" onclick="selectSeat(14)">14</button>
                    
                    <button type="button" class="seat <?= in_array(17, $booked_seats) ? 'booked' : 'available' ?>" data-seat="17" onclick="selectSeat(17)">17</button>
                    <button type="button" class="seat <?= in_array(20, $booked_seats) ? 'booked' : 'available' ?>" data-seat="20" onclick="selectSeat(20)">20</button>
                    <button type="button" class="seat <?= in_array(23, $booked_seats) ? 'booked' : 'available' ?>" data-seat="23" onclick="selectSeat(23)">23</button>
                    <button type="button" class="seat <?= in_array(26, $booked_seats) ? 'booked' : 'available' ?>" data-seat="26" onclick="selectSeat(26)">26</button>
                    <button type="button" class="seat <?= in_array(29, $booked_seats) ? 'booked' : 'available' ?>" data-seat="29" onclick="selectSeat(29)">29</button>
                    <button type="button" class="seat <?= in_array(32, $booked_seats) ? 'booked' : 'available' ?>" data-seat="32" onclick="selectSeat(32)">32</button>
                    <button type="button" class="seat <?= in_array(35, $booked_seats) ? 'booked' : 'available' ?>" data-seat="35" onclick="selectSeat(35)">35</button>
                </div>

                <div class="row">
                    <button type="button" class="seat <?= in_array(1, $booked_seats) ? 'booked' : 'available' ?>" data-seat="1" onclick="selectSeat(1)">1</button>
                    <button type="button" class="seat <?= in_array(4, $booked_seats) ? 'booked' : 'available' ?>" data-seat="4" onclick="selectSeat(4)">4</button>
                    <button type="button" class="seat <?= in_array(7, $booked_seats) ? 'booked' : 'available' ?>" data-seat="7" onclick="selectSeat(7)">7</button>
                    <button type="button" class="seat <?= in_array(10, $booked_seats) ? 'booked' : 'available' ?>" data-seat="10" onclick="selectSeat(10)">10</button>
                    <button type="button" class="seat <?= in_array(13, $booked_seats) ? 'booked' : 'available' ?>" data-seat="13" onclick="selectSeat(13)">13</button>
                    <button type="button" class="seat <?= in_array(16, $booked_seats) ? 'booked' : 'available' ?>" data-seat="16" onclick="selectSeat(16)">16</button>
                    
                    <button type="button" class="seat <?= in_array(19, $booked_seats) ? 'booked' : 'available' ?>" data-seat="19" onclick="selectSeat(19)">19</button>
                    <button type="button" class="seat <?= in_array(22, $booked_seats) ? 'booked' : 'available' ?>" data-seat="22" onclick="selectSeat(22)">22</button>
                    <button type="button" class="seat <?= in_array(25, $booked_seats) ? 'booked' : 'available' ?>" data-seat="25" onclick="selectSeat(25)">25</button>
                    <button type="button" class="seat <?= in_array(28, $booked_seats) ? 'booked' : 'available' ?>" data-seat="28" onclick="selectSeat(28)">28</button>
                    <button type="button" class="seat <?= in_array(31, $booked_seats) ? 'booked' : 'available' ?>" data-seat="31" onclick="selectSeat(31)">31</button>
                    <button type="button" class="seat <?= in_array(34, $booked_seats) ? 'booked' : 'available' ?>" data-seat="34" onclick="selectSeat(34)">34</button>
                </div>

            
           

            <button type="submit" class="btn" id="confirm-btn" disabled>
                Seçili Koltuğu Al
            </button>
        </form>

        <a href="homepage.php" class="back-link">← Seferlere Dön</a>
    </div>

    <script>
        let selectedSeat = null;

        function selectSeat(seatNumber) {
            
            if (selectedSeat) {
                const prevBtn = document.querySelector(`.seat[data-seat="${selectedSeat}"]`);
                if (prevBtn.classList.contains('selected')) {
                    prevBtn.classList.remove('selected');
                }
            }

            
            selectedSeat = seatNumber;
            const btn = document.querySelector(`.seat[data-seat="${seatNumber}"]`);
            btn.classList.add('selected');

            
            document.getElementById('selected-seat').value = seatNumber;

            
            document.getElementById('confirm-btn').disabled = false;
        }

        
        function applyCoupon() {
            const couponInput = document.getElementById('coupon_code');
            const couponCode = couponInput.value.trim();
            const hiddenCoupon = document.getElementById('hidden-coupon-code');
            const couponMessage = document.getElementById('coupon-message');
            const originalPrice = <?= $trip['price'] ?>;
            let finalPrice = originalPrice;

            if (couponCode === '') {
                couponMessage.innerHTML = '';
                hiddenCoupon.value = '';
                return;
            }

           
            document.getElementById('seat-form').submit(); 
        }

        document.getElementById('seat-form').addEventListener('submit', function(e) {
            if (!selectedSeat) {
                e.preventDefault();
                alert('Lütfen bir koltuk seçin.');
            }
        });
    </script>
</body>
</html>