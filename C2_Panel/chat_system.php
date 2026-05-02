<?php
session_start();
if (!isset($_SESSION['affiliate_id'])) { header('Location: index.php'); exit; }
require_once 'config/database.php';

$victimId = $_GET['victim_id'] ?? 0;
$stmt = $db->prepare("SELECT * FROM victims WHERE id = ? AND affiliate_id = ?");
$stmt->execute([$victimId, $_SESSION['affiliate_id']]);
$victim = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = $_POST['message'];
    $stmt = $db->prepare("INSERT INTO chat_messages (victim_id, sender_type, message, timestamp) VALUES (?, 'affiliate', ?, NOW())");
    $stmt->execute([$victimId, $message]);
}

// Fetch messages
$stmt = $db->prepare("SELECT * FROM chat_messages WHERE victim_id = ? ORDER BY timestamp ASC");
$stmt->execute([$victimId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat - LockBit</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; color: #00ff00; font-family: 'Courier New', monospace; padding: 20px; }
        .chat-container { max-width: 800px; margin: 0 auto; background: #111; border: 1px solid #333; }
        .chat-header { background: #1a1a1a; padding: 20px; border-bottom: 1px solid #333; }
        .messages { height: 400px; overflow-y: auto; padding: 20px; }
        .message { margin-bottom: 15px; }
        .affiliate { color: #ff0000; text-align: right; }
        .victim { color: #00ff00; text-align: left; }
        .timestamp { color: #666; font-size: 11px; }
        .input-area { padding: 20px; border-top: 1px solid #333; display: flex; gap: 10px; }
        input { flex: 1; background: #222; color: #00ff00; border: 1px solid #333; padding: 10px; font-family: 'Courier New', monospace; }
        .btn { background: #ff0000; color: #fff; padding: 10px 20px; border: none; cursor: pointer; }
        .victim-info { background: #1a1a1a; padding: 15px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="victim-info">
        <h2>Company: <?php echo htmlspecialchars($victim['company_name'] ?? 'Unknown'); ?></h2>
        <p>Ransom: $<?php echo number_format($victim['ransom_amount'] ?? 0, 2); ?></p>
        <p>Deadline: <?php echo $victim['deadline'] ?? 'N/A'; ?></p>
        <p>Status: <?php echo strtoupper($victim['status'] ?? 'unknown'); ?></p>
    </div>
    
    <div class="chat-container">
        <div class="chat-header">
            <h2>💬 CHAT WITH VICTIM</h2>
        </div>
        <div class="messages" id="messages">
            <?php foreach ($messages as $msg): ?>
            <div class="message <?php echo $msg['sender_type']; ?>">
                <p><?php echo htmlspecialchars($msg['message']); ?></p>
                <span class="timestamp"><?php echo $msg['timestamp']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="input-area">
            <form method="POST" style="display: flex; gap: 10px; width: 100%;">
                <input type="text" name="message" placeholder="Type your message..." required>
                <button type="submit" class="btn">SEND</button>
            </form>
        </div>
    </div>
</body>
</html>
