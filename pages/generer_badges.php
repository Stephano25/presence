<?php
require_once '../config/config.php';
require_once '../includes/session.php';
requireLogin();
require_once '../includes/functions.php';
require_once '../classes/Database.php';
require_once '../classes/Employe.php';

$employe = new Employe();
$employes = $employe->getAll();

// Récupérer l'employé sélectionné
$employeSelectionne = null;
if (isset($_GET['employe_id']) && !empty($_GET['employe_id'])) {
    $employeSelectionne = $employe->getById($_GET['employe_id']);
}

$format = $_GET['format'] ?? 'qr';
$tous = isset($_GET['tous']) && $_GET['tous'] == 1;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Générer Badges - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../css/style.css">
    
    <!-- Bibliothèques -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    
    <style>
        .badge-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 12px 25px;
            background: #ecf0f1;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            color: #2c3e50;
            text-decoration: none;
        }
        .tab-btn:hover {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        .tab-btn.active {
            background: #2c3e50;
            color: white;
            border-color: #2c3e50;
        }
        
        /* ============ BADGE PROFESSIONNEL ============ */
        .badge-pro {
            width: 340px;
            height: 540px;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            position: relative;
            margin: 20px auto;
            font-family: 'Segoe UI', Arial, sans-serif;
            border: 1px solid #e0e0e0;
        }
        
        /* En-tête du badge */
        .badge-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
        }
        .badge-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #f39c12, #e74c3c, #9b59b6);
        }
        .badge-header .logo {
            font-size: 32px;
        }
        .badge-header .title {
            flex: 1;
        }
        .badge-header .title h2 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .badge-header .title p {
            font-size: 10px;
            opacity: 0.85;
            margin: 2px 0 0;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        
        /* Photo de l'employé */
        .badge-photo-section {
            display: flex;
            justify-content: center;
            padding: 20px 0 10px;
            position: relative;
        }
        .badge-photo {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            border: 5px solid #ffffff;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            overflow: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .badge-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .badge-photo .initials {
            color: white;
            font-size: 48px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        /* Informations */
        .badge-info {
            text-align: center;
            padding: 5px 20px 15px;
        }
        .badge-info .nom-complet {
            font-size: 22px;
            font-weight: 800;
            color: #1e3c72;
            margin: 0 0 5px;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }
        .badge-info .fonction {
            font-size: 14px;
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 0 0 10px;
        }
        .badge-info .departement {
            display: inline-block;
            background: #e8f4fd;
            color: #2980b9;
            padding: 4px 15px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
        }
        
        /* Séparateur */
        .badge-separator {
            height: 1px;
            background: linear-gradient(90deg, transparent, #ddd, transparent);
            margin: 10px 30px;
        }
        
        /* Matricule */
        .badge-matricule {
            text-align: center;
            padding: 5px 20px;
        }
        .badge-matricule .label {
            font-size: 9px;
            color: #95a5a6;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .badge-matricule .value {
            font-size: 24px;
            font-weight: 800;
            color: #e74c3c;
            letter-spacing: 4px;
            font-family: 'Courier New', monospace;
        }
        
        /* Code-barres / QR Code */
        .badge-code {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px 20px;
            min-height: 100px;
        }
        .badge-code svg,
        .badge-code canvas,
        .badge-code img {
            max-width: 100%;
        }
        
        /* Pied du badge */
        .badge-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 8px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 9px;
            letter-spacing: 0.5px;
        }
        .badge-footer .date {
            opacity: 0.9;
        }
        .badge-footer .statut {
            background: #27ae60;
            padding: 2px 10px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 8px;
            letter-spacing: 1px;
        }
        
        /* Grille des badges */
        .grid-badges {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 30px;
            margin: 20px 0;
        }
        
        /* Boutons */
        .btn-generer {
            padding: 15px 30px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            margin: 5px;
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        .btn-qr {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .btn-qr:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-barcode {
            background: linear-gradient(135deg, #f39c12 0%, #e74c3c 100%);
        }
        .btn-barcode:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(243, 156, 18, 0.4);
        }
        .btn-both {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        }
        .btn-both:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(44, 62, 80, 0.4);
        }
        .btn-print {
            background: #2c3e50;
        }
        .btn-print:hover {
            background: #34495e;
        }
        .btn-download {
            background: #27ae60;
        }
        .btn-download:hover {
            background: #229954;
        }
        
        .actions-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        /* Liste des employés */
        .employe-select-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            border-left: 4px solid #3498db;
        }
        .employe-select-card:hover {
            background: #ecf0f1;
        }
        .employe-info-mini {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .employe-info-mini .mini-photo {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #3498db;
        }
        .employe-info-mini .mini-photo-placeholder {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        .employe-info-mini .info-text strong {
            color: #2c3e50;
            font-size: 15px;
            display: block;
        }
        .employe-info-mini .info-text span {
            color: #7f8c8d;
            font-size: 12px;
        }
        .btn-mini {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-mini.qr { background: #667eea; }
        .btn-mini.qr:hover { background: #5568d3; }
        .btn-mini.barcode { background: #f39c12; }
        .btn-mini.barcode:hover { background: #e67e22; }
        .btn-mini.both { background: #2c3e50; }
        .btn-mini.both:hover { background: #34495e; }
        
        /* Info section */
        .info-section {
            background: #d4edda;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #27ae60;
            color: #155724;
            font-size: 14px;
        }
        .info-section a {
            color: #155724;
            font-weight: bold;
        }
        
        /* Badge container pour capture */
        .badge-container {
            display: flex;
            justify-content: center;
            padding: 20px 0;
        }
        
        @media print {
            .navbar, .no-print, .actions-bar, .badge-tabs, .info-section {
                display: none !important;
            }
            body {
                background: white !important;
            }
            .container {
                max-width: 100%;
                padding: 0;
            }
            .grid-badges {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            .badge-pro {
                page-break-inside: avoid;
                box-shadow: none;
                border: 1px solid #000;
                transform: scale(0.9);
            }
        }
    </style>
</head>
<body>
    <nav class="navbar no-print">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand"><?= SITE_NAME ?></a>
            <ul class="nav-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="pointage.php">⏱️ Pointage</a></li>
                <li><a href="employes.php">👥 Employés</a></li>
                <li><a href="generer_badges.php" class="active">🎫 Badges</a></li>
                <li><a href="export.php">📤 Export</a></li>
                <li><a href="admin_logout.php" style="color: #e74c3c;">🚪 Déconnexion</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header no-print">
            <h1>🎫 Génération de Badges Professionnels</h1>
            <p>Badges avec photo, nom, fonction et code QR/barres</p>
        </div>
        
        <!-- Onglets format -->
        <div class="badge-tabs no-print">
            <a href="?format=qr" class="tab-btn <?= ($format === 'qr') ? 'active' : '' ?>">📱 QR Code</a>
            <a href="?format=barcode" class="tab-btn <?= ($format === 'barcode') ? 'active' : '' ?>">📊 Code-barres</a>
            <a href="?format=both" class="tab-btn <?= ($format === 'both') ? 'active' : '' ?>">🎯 Les deux</a>
        </div>
        
        <?php if (!$employeSelectionne && !$tous): ?>
        <!-- Info photo -->
        <div class="info-section no-print">
            📸 <strong>Photos des employés :</strong> Les photos sont gérées depuis la page 
            <a href="employes.php">👥 Employés</a>. 
            Ajoutez ou modifiez une photo en cliquant sur "+ Ajouter un employé".
        </div>
        
        <!-- Sélection employé -->
        <div class="card no-print">
            <h2>👤 Générer pour un employé</h2>
            <form method="GET">
                <input type="hidden" name="format" value="<?= $format ?>">
                <div class="form-group">
                    <label>Sélectionner un employé</label>
                    <select name="employe_id" required style="font-size: 16px; padding: 12px;">
                        <option value="">-- Choisir un employé --</option>
                        <?php foreach ($employes as $e): ?>
                            <option value="<?= $e['id'] ?>">
                                <?= htmlspecialchars($e['matricule']) ?> - <?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="actions-bar">
                    <button type="submit" class="btn-generer btn-qr">📱 QR Code</button>
                    <button type="submit" class="btn-generer btn-barcode" onclick="document.querySelector('[name=format]').value='barcode'">📊 Code-barres</button>
                    <button type="submit" class="btn-generer btn-both" onclick="document.querySelector('[name=format]').value='both'">🎯 Les deux</button>
                </div>
            </form>
        </div>
        
        <!-- Générer pour tous -->
        <div class="card no-print">
            <h2>👥 Générer pour tous les employés</h2>
            <div class="actions-bar">
                <a href="?format=qr&tous=1" class="btn-generer btn-qr">📱 Tous QR</a>
                <a href="?format=barcode&tous=1" class="btn-generer btn-barcode">📊 Tous Code-barres</a>
                <a href="?format=both&tous=1" class="btn-generer btn-both">🎯 Tous Les Deux</a>
            </div>
        </div>
        
        <!-- Liste rapide -->
        <div class="card no-print">
            <h2>⚡ Génération rapide</h2>
            <?php foreach ($employes as $e): 
                $init = strtoupper(substr($e['prenom'], 0, 1) . substr($e['nom'], 0, 1));
            ?>
                <div class="employe-select-card">
                    <div class="employe-info-mini">
                        <?php if (!empty($e['photo']) && file_exists('../' . $e['photo'])): ?>
                            <img src="../<?= htmlspecialchars($e['photo']) ?>" 
                                 alt="Photo" 
                                 class="mini-photo">
                        <?php else: ?>
                            <div class="mini-photo-placeholder"><?= $init ?></div>
                        <?php endif; ?>
                        <div class="info-text">
                            <strong><?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?></strong>
                            <span><?= htmlspecialchars($e['matricule']) ?> | <?= htmlspecialchars($e['poste'] ?? 'Employé') ?></span>
                        </div>
                    </div>
                    <div style="display: flex; gap: 5px;">
                        <a href="?employe_id=<?= $e['id'] ?>&format=qr" class="btn-mini qr">📱 QR</a>
                        <a href="?employe_id=<?= $e['id'] ?>&format=barcode" class="btn-mini barcode">📊 Barres</a>
                        <a href="?employe_id=<?= $e['id'] ?>&format=both" class="btn-mini both">🎯 Les deux</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Badge pour un employé -->
        <?php if ($employeSelectionne): 
            $initiales = strtoupper(substr($employeSelectionne['prenom'], 0, 1) . substr($employeSelectionne['nom'], 0, 1));
        ?>
        <div class="card">
            <div class="card-header no-print">
                <h2>🎫 Badge de <?= htmlspecialchars($employeSelectionne['nom'] . ' ' . $employeSelectionne['prenom']) ?></h2>
                <div class="export-actions">
                    <button class="btn btn-print" onclick="window.print()">🖨️ Imprimer</button>
                    <button class="btn btn-download" onclick="downloadBadge('badge-<?= $employeSelectionne['id'] ?>')">💾 Télécharger PNG</button>
                </div>
            </div>
            
            <div class="badge-container">
                <div class="badge-pro" id="badge-<?= $employeSelectionne['id'] ?>">
                    <!-- En-tête -->
                    <div class="badge-header">
                        <div class="logo">🏢</div>
                        <div class="title">
                            <h2><?= SITE_NAME ?></h2>
                            <p>Carte d'accès employé</p>
                        </div>
                    </div>
                    
                    <!-- Photo -->
                    <div class="badge-photo-section">
                        <div class="badge-photo">
                            <?php if (!empty($employeSelectionne['photo']) && file_exists('../' . $employeSelectionne['photo'])): ?>
                                <img src="../<?= htmlspecialchars($employeSelectionne['photo']) ?>" alt="Photo">
                            <?php else: ?>
                                <span class="initials"><?= $initiales ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Info -->
                    <div class="badge-info">
                        <h3 class="nom-complet">
                            <?= htmlspecialchars(strtoupper($employeSelectionne['nom']) . ' ' . $employeSelectionne['prenom']) ?>
                        </h3>
                        <p class="fonction">
                            <?= htmlspecialchars($employeSelectionne['poste'] ?? 'Employé') ?>
                        </p>
                        <?php if (!empty($employeSelectionne['departement'])): ?>
                            <span class="departement">
                                <?= htmlspecialchars($employeSelectionne['departement']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="badge-separator"></div>
                    
                    <!-- Matricule -->
                    <div class="badge-matricule">
                        <div class="label">Matricule</div>
                        <div class="value"><?= htmlspecialchars($employeSelectionne['matricule']) ?></div>
                    </div>
                    
                    <!-- Code-barres / QR -->
                    <div class="badge-code">
                        <?php if ($format === 'qr'): ?>
                            <div id="qr-<?= $employeSelectionne['id'] ?>"></div>
                        <?php elseif ($format === 'barcode'): ?>
                            <svg id="barcode-<?= $employeSelectionne['id'] ?>"></svg>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 5px;">
                                <div id="qr-<?= $employeSelectionne['id'] ?>"></div>
                                <svg id="barcode-<?= $employeSelectionne['id'] ?>"></svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Footer -->
                    <div class="badge-footer">
                        <span class="date">📅 <?= date('d/m/Y') ?></span>
                        <span class="statut">● ACTIF</span>
                    </div>
                </div>
            </div>
            
            <script>
                // Générer le QR Code
                <?php if ($format === 'qr' || $format === 'both'): ?>
                QRCode.toCanvas(
                    document.createElement('canvas'),
                    '<?= htmlspecialchars($employeSelectionne['matricule']) ?>',
                    {
                        width: 100,
                        margin: 1,
                        color: { dark: '#1e3c72', light: '#ffffff' }
                    },
                    function(error, canvas) {
                        if (!error) {
                            document.getElementById('qr-<?= $employeSelectionne['id'] ?>').appendChild(canvas);
                        }
                    }
                );
                <?php endif; ?>
                
                // Générer le code-barres
                <?php if ($format === 'barcode' || $format === 'both'): ?>
                JsBarcode("#barcode-<?= $employeSelectionne['id'] ?>", "<?= htmlspecialchars($employeSelectionne['matricule']) ?>", {
                    format: "CODE128",
                    width: 1.5,
                    height: 50,
                    displayValue: true,
                    fontSize: 12,
                    font: "monospace",
                    textMargin: 2,
                    background: "#ffffff",
                    lineColor: "#1e3c72"
                });
                <?php endif; ?>
                
                function downloadBadge(id) {
                    const badge = document.getElementById(id);
                    html2canvas(badge, {
                        backgroundColor: '#ffffff',
                        scale: 3,
                        useCORS: true
                    }).then(canvas => {
                        const link = document.createElement('a');
                        link.download = 'badge_<?= htmlspecialchars($employeSelectionne['matricule']) ?>.png';
                        link.href = canvas.toDataURL('image/png');
                        link.click();
                    });
                }
            </script>
        </div>
        
        <?php elseif ($tous): ?>
        <!-- Tous les badges -->
        <div class="card">
            <div class="card-header no-print">
                <h2>🎫 Tous les badges (<?= count($employes) ?> employés)</h2>
                <button class="btn btn-print" onclick="window.print()">🖨️ Imprimer tous</button>
            </div>
            
            <div class="grid-badges">
                <?php foreach ($employes as $e): 
                    $init = strtoupper(substr($e['prenom'], 0, 1) . substr($e['nom'], 0, 1));
                ?>
                    <div class="badge-pro" id="badge-<?= $e['id'] ?>">
                        <div class="badge-header">
                            <div class="logo">🏢</div>
                            <div class="title">
                                <h2><?= SITE_NAME ?></h2>
                                <p>Carte d'accès</p>
                            </div>
                        </div>
                        
                        <div class="badge-photo-section">
                            <div class="badge-photo">
                                <?php if (!empty($e['photo']) && file_exists('../' . $e['photo'])): ?>
                                    <img src="../<?= htmlspecialchars($e['photo']) ?>" alt="Photo">
                                <?php else: ?>
                                    <span class="initials"><?= $init ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="badge-info">
                            <h3 class="nom-complet">
                                <?= htmlspecialchars(strtoupper($e['nom']) . ' ' . $e['prenom']) ?>
                            </h3>
                            <p class="fonction">
                                <?= htmlspecialchars($e['poste'] ?? 'Employé') ?>
                            </p>
                            <?php if (!empty($e['departement'])): ?>
                                <span class="departement"><?= htmlspecialchars($e['departement']) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="badge-separator"></div>
                        
                        <div class="badge-matricule">
                            <div class="label">Matricule</div>
                            <div class="value"><?= htmlspecialchars($e['matricule']) ?></div>
                        </div>
                        
                        <div class="badge-code">
                            <?php if ($format === 'qr'): ?>
                                <div class="qr-container" data-matricule="<?= htmlspecialchars($e['matricule']) ?>"></div>
                            <?php elseif ($format === 'barcode'): ?>
                                <svg class="barcode-container" data-matricule="<?= htmlspecialchars($e['matricule']) ?>"></svg>
                            <?php else: ?>
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 3px;">
                                    <div class="qr-container" data-matricule="<?= htmlspecialchars($e['matricule']) ?>"></div>
                                    <svg class="barcode-container" data-matricule="<?= htmlspecialchars($e['matricule']) ?>"></svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="badge-footer">
                            <span class="date">📅 <?= date('d/m/Y') ?></span>
                            <span class="statut">● ACTIF</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <script>
                // QR Codes
                <?php if ($format === 'qr' || $format === 'both'): ?>
                document.querySelectorAll('.qr-container').forEach(container => {
                    const matricule = container.getAttribute('data-matricule');
                    QRCode.toCanvas(
                        document.createElement('canvas'),
                        matricule,
                        {
                            width: 90,
                            margin: 1,
                            color: { dark: '#1e3c72', light: '#ffffff' }
                        },
                        function(error, canvas) {
                            if (!error) container.appendChild(canvas);
                        }
                    );
                });
                <?php endif; ?>
                
                // Code-barres
                <?php if ($format === 'barcode' || $format === 'both'): ?>
                document.querySelectorAll('.barcode-container').forEach(svg => {
                    const matricule = svg.getAttribute('data-matricule');
                    JsBarcode(svg, matricule, {
                        format: "CODE128",
                        width: 1.2,
                        height: 40,
                        displayValue: true,
                        fontSize: 10,
                        font: "monospace",
                        textMargin: 1,
                        background: "#ffffff",
                        lineColor: "#1e3c72"
                    });
                });
                <?php endif; ?>
            </script>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>