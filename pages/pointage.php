<?php
require_once '../config/config.php';
require_once '../includes/session.php';
requireLogin(); // Seul l'admin peut accéder
require_once '../includes/functions.php';
require_once '../classes/Database.php';
require_once '../classes/Employe.php';
require_once '../classes/Pointage.php';

$employe = new Employe();
$pointage = new Pointage();
$employes = $employe->getAll();

$message = '';
$messageType = '';

// Traitement du pointage (Admin valide)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeId = $_POST['employe_id'] ?? '';
    $type = $_POST['type'] ?? '';
    $matricule = $_POST['matricule'] ?? '';
    
    // Si matricule fourni, rechercher l'employé
    if (!empty($matricule) && empty($employeId)) {
        $matricule = strtoupper(trim($matricule));
        $emp = $employe->getByMatricule($matricule);
        if ($emp) {
            $employeId = $emp['id'];
        } else {
            $message = "❌ Matricule '$matricule' non trouvé !";
            $messageType = 'danger';
        }
    }
    
    if (!empty($employeId) && !empty($type)) {
        // Vérifier si l'employé existe
        $emp = $employe->getById($employeId);
        if (!$emp) {
            $message = "❌ Employé non trouvé !";
            $messageType = 'danger';
        } else {
            // Vérifier le dernier pointage pour éviter les doublons
            $dernier = $pointage->getDernierPointage($employeId);
            $dernierType = $dernier['type'] ?? null;
            
            if ($dernierType === $type) {
                $message = "⚠️ " . ucfirst($emp['prenom']) . " " . $emp['nom'] . 
                          " a déjà fait un " . ($type === 'entree' ? "ENTRÉE" : "SORTIE") . " !";
                $messageType = 'warning';
            } else {
                $result = $pointage->enregistrer($employeId, $type);
                if ($result) {
                    $message = "✅ Pointage " . ($type === 'entree' ? "d'entrée" : "de sortie") . 
                              " enregistré pour " . ucfirst($emp['prenom']) . " " . $emp['nom'];
                    $messageType = 'success';
                } else {
                    $message = "❌ Erreur lors de l'enregistrement !";
                    $messageType = 'danger';
                }
            }
        }
    } elseif (empty($employeId) && empty($matricule)) {
        $message = "⚠️ Veuillez sélectionner un employé ou saisir un matricule !";
        $messageType = 'warning';
    }
}

// Récupération des pointages du jour
$pointagesAujourdhui = $pointage->getPointages([
    'date_debut' => date('Y-m-d'),
    'date_fin' => date('Y-m-d')
]);

// Statistiques du jour
$statsJour = $pointage->getStatsParJour(date('Y-m-d'));
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pointage - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .pointage-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .pointage-form .full-width {
            grid-column: 1 / -1;
        }
        .matricule-input {
            font-size: 24px;
            text-align: center;
            letter-spacing: 3px;
            font-weight: bold;
            padding: 15px;
            text-transform: uppercase;
        }
        .matricule-input:focus {
            border-color: #27ae60;
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.2);
        }
        .btn-validate {
            padding: 15px 30px;
            font-size: 18px;
            font-weight: bold;
        }
        .btn-entree {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }
        .btn-entree:hover {
            background: linear-gradient(135deg, #219a52 0%, #27ae60 100%);
        }
        .btn-sortie {
            background: linear-gradient(135deg, #e74c3c 0%, #f39c12 100%);
            color: white;
        }
        .btn-sortie:hover {
            background: linear-gradient(135deg, #c0392b 0%, #e67e22 100%);
        }
        .quick-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 10px 0;
        }
        .quick-btn {
            padding: 8px 15px;
            background: #ecf0f1;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        .quick-btn:hover {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        .stat-rapide {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-item .number {
            font-size: 28px;
            font-weight: bold;
        }
        .stat-item .label {
            font-size: 12px;
            color: #7f8c8d;
        }
        .stat-item .number.entree { color: #27ae60; }
        .stat-item .number.sortie { color: #e74c3c; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand"><?= SITE_NAME ?></a>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="pointage.php" class="active">Pointage</a></li>
                <li><a href="employes.php">Employés</a></li>
                <li><a href="export.php">Export</a></li>
                <li><a href="../scan_badge.php">Scan Badge</a></li>
                <li><a href="admin_logout.php" style="color: #e74c3c;">Déconnexion</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>⏱️ Enregistrement des pointages</h1>
            <p>Validez les pointages des employés</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>
        
        <!-- Statistiques rapides -->
        <div class="stat-rapide">
            <div class="stat-item">
                <div class="number"><?= count($pointagesAujourdhui) ?></div>
                <div class="label">📊 Total pointages aujourd'hui</div>
            </div>
            <div class="stat-item">
                <div class="number entree">
                    <?= count(array_filter($pointagesAujourdhui, function($p) { return $p['type'] === 'entree'; })) ?>
                </div>
                <div class="label">🟢 Entrées</div>
            </div>
            <div class="stat-item">
                <div class="number sortie">
                    <?= count(array_filter($pointagesAujourdhui, function($p) { return $p['type'] === 'sortie'; })) ?>
                </div>
                <div class="label">🔴 Sorties</div>
            </div>
        </div>
        
        <div class="card">
            <h2>📌 Nouveau pointage</h2>
            
            <!-- Formulaire de pointage -->
            <form method="POST">
                <div class="pointage-form">
                    <!-- Sélection par liste -->
                    <div class="form-group">
                        <label>👤 Sélectionner un employé</label>
                        <select name="employe_id" id="employeSelect" style="font-size: 16px; padding: 12px;">
                            <option value="">-- Choisir un employé --</option>
                            <?php foreach ($employes as $e): ?>
                                <option value="<?= $e['id'] ?>">
                                    <?= htmlspecialchars($e['matricule']) ?> - <?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- OU saisie du matricule -->
                    <div class="form-group">
                        <label>🏷️ OU saisir le matricule</label>
                        <input type="text" 
                               name="matricule" 
                               id="matriculeInput" 
                               class="matricule-input" 
                               placeholder="EMP001"
                               style="text-transform: uppercase;">
                    </div>
                    
                    <!-- Type de pointage -->
                    <div class="form-group full-width">
                        <label>📌 Type de pointage</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <button type="submit" name="type" value="entree" class="btn btn-entree btn-validate">
                                🟢 VALIDER L'ENTRÉE
                            </button>
                            <button type="submit" name="type" value="sortie" class="btn btn-sortie btn-validate">
                                🔴 VALIDER LA SORTIE
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Boutons rapides pour les matricules de test -->
            <div style="margin-top: 15px;">
                <label style="font-size: 12px; color: #95a5a6;">⚡ Saisie rapide :</label>
                <div class="quick-actions">
                    <button class="quick-btn" onclick="setMatricule('EMP001')">EMP001 - Jean Rakoto</button>
                    <button class="quick-btn" onclick="setMatricule('EMP002')">EMP002 - Marie Rabe</button>
                    <button class="quick-btn" onclick="setMatricule('EMP003')">EMP003 - Pierre Andrian</button>
                </div>
            </div>
        </div>
        
        <!-- Pointages du jour -->
        <div class="card">
            <div class="card-header">
                <h2>📋 Pointages d'aujourd'hui</h2>
                <span class="badge badge-info"><?= count($pointagesAujourdhui) ?> pointages</span>
            </div>
            
            <?php if (count($pointagesAujourdhui) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Matricule</th>
                            <th>Employé</th>
                            <th>Type</th>
                            <th>Heure</th>
                            <th>Validé par</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pointagesAujourdhui as $index => $p): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><strong><?= htmlspecialchars($p['matricule']) ?></strong></td>
                            <td><?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?></td>
                            <td><?= getTypeBadge($p['type']) ?></td>
                            <td><?= date('H:i:s', strtotime($p['date_heure'])) ?></td>
                            <td><span style="color: #3498db;">Admin</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #95a5a6; padding: 20px;">
                    📭 Aucun pointage enregistré aujourd'hui
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Fonction pour remplir le matricule automatiquement
        function setMatricule(matricule) {
            document.getElementById('matriculeInput').value = matricule;
            document.getElementById('matriculeInput').focus();
            // Désélectionner le select
            document.getElementById('employeSelect').value = '';
        }
        
        // Quand on sélectionne un employé, vider le champ matricule
        document.getElementById('employeSelect').addEventListener('change', function() {
            if (this.value) {
                document.getElementById('matriculeInput').value = '';
            }
        });
        
        // Quand on saisit un matricule, désélectionner le select
        document.getElementById('matriculeInput').addEventListener('input', function() {
            if (this.value) {
                document.getElementById('employeSelect').value = '';
            }
            this.value = this.value.toUpperCase();
        });
        
        // Focus sur le champ matricule par défaut
        document.getElementById('matriculeInput').focus();
        
        // Confirmation avant validation
        document.querySelectorAll('.btn-validate').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const type = this.value === 'entree' ? 'ENTRÉE' : 'SORTIE';
                const matricule = document.getElementById('matriculeInput').value;
                const select = document.getElementById('employeSelect');
                let nom = '';
                
                if (select.value) {
                    nom = select.options[select.selectedIndex].text;
                } else if (matricule) {
                    nom = 'Matricule: ' + matricule;
                } else {
                    e.preventDefault();
                    alert('⚠️ Veuillez sélectionner un employé ou saisir un matricule !');
                    return false;
                }
                
                if (!confirm('Confirmer le pointage de ' + type + ' pour ' + nom + ' ?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>