<?php
require_once '../config/config.php';
require_once '../includes/session.php';
requireLogin();
require_once '../includes/functions.php';
require_once '../classes/Database.php';
require_once '../classes/Employe.php';
require_once '../classes/Pointage.php';

$employe = new Employe();
$pointage = new Pointage();

$statsEmployes = $employe->getStats();
$pointagesRecents = $pointage->getPointages(['limit' => 10]);

$dateAujourdhui = date('Y-m-d');
$pointagesAujourdhui = $pointage->getPointages([
    'date_debut' => $dateAujourdhui,
    'date_fin' => $dateAujourdhui
]);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand"><?= SITE_NAME ?></a>
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                <li><a href="pointage.php">⏱️ Pointage</a></li>
                <li><a href="employes.php">👥 Employés</a></li>
                <li><a href="generer_badges.php">🎫 Badges</a></li>
                <li><a href="export.php">📤 Export</a></li>
                <li><a href="admin_logout.php" style="color: #e74c3c;">🚪 Déconnexion</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>Tableau de bord</h1>
            <p>Bonjour <?= htmlspecialchars($_SESSION['username'] ?? 'utilisateur') ?> !</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <h3>Total Employés</h3>
                <p class="stat-number"><?= $statsEmployes['total'] ?? 0 ?></p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <h3>Employés Actifs</h3>
                <p class="stat-number"><?= $statsEmployes['actifs'] ?? 0 ?></p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <h3>Pointages Aujourd'hui</h3>
                <p class="stat-number"><?= count($pointagesAujourdhui) ?></p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <h3>Total Pointages</h3>
                <p class="stat-number"><?= count($pointagesRecents) ?></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Derniers pointages</h2>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Employé</th>
                        <th>Matricule</th>
                        <th>Type</th>
                        <th>Date et Heure</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pointagesRecents) > 0): ?>
                        <?php foreach ($pointagesRecents as $index => $p): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?></td>
                            <td><?= htmlspecialchars($p['matricule']) ?></td>
                            <td><?= getTypeBadge($p['type']) ?></td>
                            <td><?= formatDateTime($p['date_heure']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Aucun pointage enregistré</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>