<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'includes/session.php';

if (isLoggedIn()) {
    header('Location: pages/dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/auth.php';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $user = authenticate($username, $password);
    if ($user) {
        login($user['id'], $user['username']);
        header('Location: pages/dashboard.php');
        exit();
    } else {
        $error = "Nom d'utilisateur ou mot de passe incorrect";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <h1>👑 <?= SITE_NAME ?></h1>
            <p>Administration - Gestion des pointages</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error']) && $_GET['error'] == 'session_expired'): ?>
            <div class="alert alert-warning">Votre session a expiré. Veuillez vous reconnecter.</div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">🔐 Se connecter</button>
        </form>
        
        <div class="login-footer">
            <p style="margin-bottom: 10px;">Identifiants par défaut: <strong>admin</strong> / <strong>admin123</strong></p>
            <hr>
            <p style="margin-top: 10px; font-size: 13px;">
                🏷️ <a href="scan_badge.php" style="color: #27ae60; font-weight: bold; text-decoration: none;">
                    Accès Employé - Scan Badge
                </a>
            </p>
        </div>
    </div>
</body>
</html>