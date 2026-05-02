<?php
session_start();

// Check if user is logged in
if (isset($_SESSION['affiliate_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $token = $_POST['token'] ?? '';
    
    // Hardened authentication
    require_once 'config/database.php';
    
    $stmt = $db->prepare("SELECT id, username, password_hash, role FROM affiliates WHERE username = ? AND active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password . SALT, $user['password_hash'])) {
        // Verify 2FA token if enabled
        if (verify2FA($user['id'], $token)) {
            $_SESSION['affiliate_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();
            $_SESSION['user_agent'] = hash('sha256', $_SERVER['HTTP_USER_AGENT']);
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            
            // Log login
            logActivity($user['id'], 'LOGIN', $_SERVER['REMOTE_ADDR']);
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid 2FA token';
        }
    } else {
        $error = 'Invalid credentials';
        sleep(2); // Rate limiting
    }
}

function verify2FA($userId, $token) {
    // TOTP verification
    require_once 'vendor/GoogleAuthenticator.php';
    $ga = new PHPGangsta_GoogleAuthenticator();
    
    require_once 'config/database.php';
    $stmt = $db->prepare("SELECT secret_key FROM affiliates WHERE id = ?");
    $stmt->execute([$userId]);
    $secret = $stmt->fetchColumn();
    
    return $ga->verifyCode($secret, $token, 2);
}

function logActivity($userId, $action, $ip) {
    require_once 'config/database.php';
    $stmt = $db->prepare("INSERT INTO activity_log (affiliate_id, action, ip_address, timestamp) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$userId, $action, $ip]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LockBit Affiliate Panel - Secure Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; color: #00ff00; font-family: 'Courier New', monospace; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .login-box { background: #111; border: 1px solid #00ff00; padding: 40px; border-radius: 5px; width: 400px; }
        .login-box h1 { text-align: center; margin-bottom: 30px; color: #ff0000; }
        input { width: 100%; padding: 12px; margin: 10px 0; background: #222; border: 1px solid #333; color: #00ff00; font-family: 'Courier New', monospace; }
        input:focus { outline: none; border-color: #00ff00; }
        .btn { background: #ff0000; color: #fff; border: none; cursor: pointer; font-weight: bold; }
        .btn:hover { background: #cc0000; }
        .error { color: #ff0000; text-align: center; margin: 10px 0; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>🔒 LOCKBIT PANEL</h1>
        <form method="POST">
            <input type="text" name="username" placeholder="Affiliate ID" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="text" name="token" placeholder="2FA Token" required>
            <input type="submit" value="AUTHENTICATE" class="btn">
            <?php if ($error): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
        </form>
        <div class="footer">LockBit RaaS v3.0 | Encrypted Connection</div>
    </div>
</body>
</html>
