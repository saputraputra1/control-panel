<?php
session_start();
if (!isset($_SESSION['affiliate_id'])) { header('Location: index.php'); exit; }
require_once 'config/database.php';

// Update payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $victimId = $_POST['victim_id'];
    $paidAmount = floatval($_POST['paid_amount']);
    $txHash = $_POST['tx_hash'];
    
    $stmt = $db->prepare("UPDATE victims SET status = 'paid', paid_amount = ?, tx_hash = ?, paid_at = NOW() WHERE id = ? AND affiliate_id = ?");
    $stmt->execute([$paidAmount, $txHash, $victimId, $_SESSION['affiliate_id']]);
}

$stmt = $db->prepare("SELECT * FROM victims WHERE affiliate_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['affiliate_id']]);
$victims = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPaid = 0;
$totalPending = 0;
foreach ($victims as $v) {
    if ($v['status'] == 'paid') $totalPaid += $v['paid_amount'] ?? 0;
    else $totalPending += $v['ransom_amount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Tracker - LockBit</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; color: #00ff00; font-family: 'Courier New', monospace; padding: 20px; }
        .header { background: #111; padding: 20px; margin-bottom: 20px; border: 1px solid #333; }
        h1 { color: #ff0000; }
        .summary { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .summary-box { background: #111; padding: 20px; border: 1px solid #333; text-align: center; }
        table { width: 100%; border-collapse: collapse; background: #111; }
        th, td { padding: 12px; border: 1px solid #222; }
        th { background: #1a1a1a; color: #ff0000; }
        .btn { background: #ff0000; color: #fff; padding: 5px 10px; border: none; cursor: pointer; }
        input { background: #222; color: #00ff00; border: 1px solid #333; padding: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>💰 PAYMENT TRACKER</h1>
        <a href="dashboard.php" style="color:#00ff00;">Back to Dashboard</a>
    </div>
    
    <div class="summary">
        <div class="summary-box">
            <h3>Total Paid</h3>
            <h2 style="color:#00ff00;">$<?php echo number_format($totalPaid, 2); ?></h2>
        </div>
        <div class="summary-box">
            <h3>Total Pending</h3>
            <h2 style="color:#ff8800;">$<?php echo number_format($totalPending, 2); ?></h2>
        </div>
        <div class="summary-box">
            <h3>Your Share (80%)</h3>
            <h2 style="color:#00ff00;">$<?php echo number_format($totalPaid * 0.80, 2); ?></h2>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Company</th>
                <th>Ransom</th>
                <th>Paid</th>
                <th>TX Hash</th>
                <th>Date Paid</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($victims as $v): ?>
            <tr>
                <td><?php echo htmlspecialchars($v['company_name']); ?></td>
                <td>$<?php echo number_format($v['ransom_amount'], 2); ?></td>
                <td><?php echo $v['paid_amount'] ? '$'.number_format($v['paid_amount'], 2) : '-'; ?></td>
                <td><?php echo $v['tx_hash'] ? substr($v['tx_hash'], 0, 20).'...' : '-'; ?></td>
                <td><?php echo $v['paid_at'] ?? '-'; ?></td>
                <td>
                    <?php if ($v['status'] != 'paid'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="victim_id" value="<?php echo $v['id']; ?>">
                        <input type="number" name="paid_amount" placeholder="Amount" step="0.00000001" style="width:120px;">
                        <input type="text" name="tx_hash" placeholder="TX Hash" style="width:200px;">
                        <button type="submit" name="mark_paid" class="btn">MARK PAID</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
