<?php
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        // Fallback for default admin
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'admin';
            $_SESSION['role'] = 'admin';
            header('Location: index.php');
            exit;
        }
        
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = 'admin';
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid credentials or inactive account';
            }
        } catch (Exception $e) {
            $error = 'Database error. Please ensure the database is set up.';
        }
    } else {
        $error = 'Please enter username and password';
    }
}

$app_name = getAppName();
$app_logo = getAppLogo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($app_name) ?> Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/design-system.css">
    <style>
        :root {
            --primary: #2a395a;
            --accent: #fc4466;
            --bg-body: #F8F9FA;
        }
        [data-theme="dark"] {
            --primary: #4a6fa5;
            --accent: #ff6b88;
            --bg-body: #0f172a;
        }
        body {
            background: linear-gradient(135deg, var(--primary) 0%, #1e2a42 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .login-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }
        .login-header {
            background: var(--primary);
            padding: 2rem;
            text-align: center;
        }
        .login-logo {
            width: 64px;
            height: 64px;
            background: rgba(255,255,255,0.15);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        .login-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .login-header h1 {
            color: #fff;
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
        .login-header p {
            color: rgba(255,255,255,0.7);
            font-size: 0.875rem;
        }
        .login-body {
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">
                <?php if ($app_logo): ?>
                    <img src="<?= htmlspecialchars($app_logo) ?>" alt="Logo">
                <?php else: ?>
                    <i class="fas fa-graduation-cap text-3xl text-white"></i>
                <?php endif; ?>
            </div>
            <h1><?= htmlspecialchars($app_name) ?></h1>
            <p>Sign in to continue</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div style="position: relative;">
                        <i class="fas fa-user" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                        <input type="text" name="username" class="form-control" style="padding-left: 40px;" placeholder="Enter username" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div style="position: relative;">
                        <i class="fas fa-lock" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                        <input type="password" name="password" class="form-control" style="padding-left: 40px;" placeholder="Enter password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-accent w-100" style="padding: 0.75rem 1.5rem; font-size: 1rem;">
                    <i class="fas fa-sign-in-alt"></i>Sign In
                </button>
            </form>
            <div style="text-align: center; margin-top: 1.5rem; color: var(--text-light); font-size: 0.75rem;">
                <i class="fas fa-shield-alt mr-1"></i>Admin access only
            </div>
        </div>
    </div>
</body>
</html>