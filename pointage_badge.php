<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'includes/functions.php';

// Vérifier si un employé est connecté
if (!isset($_SESSION['employe_id'])) {
    header('Location: scan_badge.php');
    exit();
}

require_once 'classes/Database.php';
require_once 'classes/Pointage.php';

$pointage = new Pointage();
$employeId = $_SESSION['employe_id'];
$employeNom = $_SESSION['employe_nom'] . ' ' . $_SESSION['employe_prenom'];
$employeMatricule = $_SESSION['employe_matricule'];

// Récupérer le dernier pointage
$dernierPointage = $pointage->getDernierPointage($employeId);
$dernierType = $dernierPointage['type'] ?? null;

// Déterminer le prochain type de pointage
$typePossible = ($dernierType === 'entree') ? 'sortie' : 'entree';

// Message
$message = '';
$messageType = '';
$redirectAfter = false;

// Traitement du pointage
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pointer') {
    $type = $_POST['type'] ?? '';
    
    if (!empty($type)) {
        // Vérifier la cohérence
        if ($dernierType === $type) {
            $message = "⚠️ Vous ne pouvez pas faire deux " . ($type === 'entree' ? 'entrées' : 'sorties') . " consécutives !";
            $messageType = 'warning';
        } else {
            $result = $pointage->enregistrer($employeId, $type);
            if ($result) {
                $message = "✅ Pointage " . ($type === 'entree' ? "d'entrée" : "de sortie") . " enregistré avec succès !";
                $messageType = 'success';
                $redirectAfter = true;  // ← Redirection après succès
                
                // Mettre à jour les données
                $dernierPointage = $pointage->getDernierPointage($employeId);
                $dernierType = $dernierPointage['type'] ?? null;
                $typePossible = ($dernierType === 'entree') ? 'sortie' : 'entree';
            } else {
                $message = "❌ Erreur lors de l'enregistrement !";
                $messageType = 'danger';
            }
        }
    }
}

// Récupérer les pointages du jour
$pointagesJour = $pointage->getPointageJour($employeId);

// Calcul des heures
$entreeJour = null;
$sortieJour = null;
foreach ($pointagesJour as $p) {
    if ($p['type'] === 'entree') {
        $entreeJour = $p['date_heure'];
    } elseif ($p['type'] === 'sortie') {
        $sortieJour = $p['date_heure'];
    }
}
$heuresTravail = calculateWorkHours($entreeJour, $sortieJour);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pointage - <?= SITE_NAME ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        .header { margin-bottom: 30px; }
        .header .logo { font-size: 48px; margin-bottom: 10px; }
        .header h1 { color: #2c3e50; font-size: 24px; }
        .header p { color: #7f8c8d; font-size: 14px; }
        .employe-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .employe-info .matricule {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            letter-spacing: 3px;
        }
        .employe-info .nom {
            font-size: 20px;
            color: #34495e;
            margin-top: 5px;
        }
        .heure-actuelle {
            font-size: 48px;
            font-weight: bold;
            color: #2c3e50;
            padding: 20px;
            background: #f0f2f5;
            border-radius: 12px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
        }
        .btn-pointer {
            width: 100%;
            padding: 30px 20px;
            font-size: 28px;
            font-weight: bold;
            border: none;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 10px 0;
            color: white;
        }
        .btn-pointer:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .btn-pointer:active { transform: scale(0.98); }
        .btn-entree { background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); }
        .btn-sortie { background: linear-gradient(135deg, #e74c3c 0%, #f39c12 100%); }
        .btn-disabled {
            background: #95a5a6 !important;
            cursor: not-allowed !important;
            opacity: 0.6;
        }
        .btn-disabled:hover {
            transform: none !important;
            box-shadow: none !important;
        }
        .btn-pointer .sub-text {
            display: block;
            font-size: 14px;
            font-weight: normal;
            opacity: 0.8;
            margin-top: 5px;
        }
        .badge-pointage {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            margin: 5px;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin: 15px 0;
            font-weight: 500;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #27ae60;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #e74c3c;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #f39c12;
        }
        .historique {
            margin-top: 30px;
            text-align: left;
        }
        .historique h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .historique-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 15px;
            border-bottom: 1px solid #ecf0f1;
            align-items: center;
        }
        .historique-item:last-child { border-bottom: none; }
        .deconnexion {
            display: inline-block;
            margin-top: 20px;
            color: #95a5a6;
            text-decoration: none;
            font-size: 14px;
            padding: 8px 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        .deconnexion:hover {
            background: #e74c3c;
            color: white;
            border-color: #e74c3c;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
            font-size: 12px;
            color: #95a5a6;
        }
        .flash-message { animation: flash 0.5s ease; }
        @keyframes flash {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .badge-scan {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0,0,0,0.8);
            color: #fff;
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 12px;
            letter-spacing: 1px;
        }
        .retour-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 12px 30px;
            background: #2c3e50;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
        }
        .retour-btn:hover {
            background: #34495e;
            transform: scale(1.02);
        }
        .success-icon {
            font-size: 60px;
            display: block;
            margin: 10px 0;
        }
        @media (max-width: 480px) {
            .container { padding: 20px; }
            .heure-actuelle { font-size: 32px; padding: 15px; }
            .btn-pointer { font-size: 20px; padding: 25px 15px; }
            .employe-info .matricule { font-size: 24px; }
            .header h1 { font-size: 20px; }
        }
    </style>
</head>
<body>
    <div class="badge-scan">🏷️ Scan Badge</div>

    <div class="container">
        <!-- En-tête -->
        <div class="header">
            <div class="logo">🏢</div>
            <h1>Pointage</h1>
            <p>Système de gestion des présences</p>
        </div>

        <!-- Info employé -->
        <div class="employe-info">
            <div class="matricule">#<?= htmlspecialchars($employeMatricule) ?></div>
            <div class="nom"><?= htmlspecialchars($employeNom) ?></div>
        </div>

        <!-- Heure -->
        <div class="heure-actuelle" id="horloge">
            <?= date('H:i:s') ?>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> flash-message">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Si succès, afficher le bouton retour -->
        <?php if ($redirectAfter): ?>
            <div style="margin: 20px 0;">
                <span class="success-icon">✅</span>
                <p style="font-size: 18px; color: #27ae60; font-weight: bold;">
                    Pointage enregistré avec succès !
                </p>
                <a href="pages/dashboard.php" class="retour-btn">
                    📊 Retour au Dashboard
                </a>
                <br>
                <a href="pointage_badge.php" style="display: inline-block; margin-top: 10px; color: #3498db; text-decoration: none;">
                    🔄 Faire un autre pointage
                </a>
            </div>
        <?php else: ?>
            <!-- Boutons de pointage -->
            <form method="POST">
                <input type="hidden" name="action" value="pointer">

                <?php if ($typePossible === 'entree'): ?>
                    <button type="submit" name="type" value="entree" class="btn-pointer btn-entree">
                        ✅ POINTER L'ENTRÉE
                        <span class="sub-text">Cliquez pour enregistrer votre arrivée</span>
                    </button>
                    <button type="submit" name="type" value="sortie" class="btn-pointer btn-disabled" disabled>
                        ⛔ SORTIE
                        <span class="sub-text">Enregistrez d'abord une entrée</span>
                    </button>
                <?php else: ?>
                    <button type="submit" name="type" value="entree" class="btn-pointer btn-disabled" disabled>
                        ⛔ ENTRÉE
                        <span class="sub-text">Vous avez déjà pointé l'entrée</span>
                    </button>
                    <button type="submit" name="type" value="sortie" class="btn-pointer btn-sortie">
                        🔴 POINTER LA SORTIE
                        <span class="sub-text">Cliquez pour enregistrer votre départ</span>
                    </button>
                <?php endif; ?>
            </form>

            <!-- Dernier pointage -->
            <div style="margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <p style="margin: 0; font-size: 14px;">
                    📌 Dernier pointage : 
                    <?php if ($dernierPointage): ?>
                        <span class="badge-pointage <?= $dernierType === 'entree' ? 'badge-success' : 'badge-danger' ?>">
                            <?= $dernierType === 'entree' ? '🟢 Entrée' : '🔴 Sortie' ?>
                        </span>
                        à <strong><?= date('H:i:s', strtotime($dernierPointage['date_heure'])) ?></strong>
                    <?php else: ?>
                        <span class="badge-pointage badge-warning">Aucun pointage aujourd'hui</span>
                    <?php endif; ?>
                </p>
                <?php if ($heuresTravail > 0): ?>
                    <p style="margin: 5px 0 0; font-size: 14px;">
                        ⏱️ Heures travaillées : <strong><?= $heuresTravail ?> h</strong>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Historique rapide -->
            <?php if (count($pointagesJour) > 0): ?>
            <div class="historique">
                <h3>📋 Historique d'aujourd'hui</h3>
                <?php foreach (array_slice($pointagesJour, 0, 5) as $p): ?>
                    <div class="historique-item">
                        <span><?= $p['type'] === 'entree' ? '🟢' : '🔴' ?> <?= $p['type'] === 'entree' ? 'Entrée' : 'Sortie' ?></span>
                        <span><?= date('H:i:s', strtotime($p['date_heure'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Lien de déconnexion -->
        <a href="logout_badge.php" class="deconnexion">🚪 Déconnexion</a>

        <div class="footer">
            <?= SITE_NAME ?> - Version 1.0
        </div>
    </div>

    <script>
        // Horloge en temps réel
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('horloge').textContent = hours + ':' + minutes + ':' + seconds;
        }
        setInterval(updateClock, 1000);

        // Confirmation avant pointage
        document.querySelectorAll('.btn-pointer:not(.btn-disabled)').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const type = this.value === 'entree' ? 'ENTRÉE' : 'SORTIE';
                if (!confirm('Confirmer le pointage de ' + type + ' ?')) {
                    e.preventDefault();
                }
            });
        });

        // Auto-refresh après 5 minutes d'inactivité
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>