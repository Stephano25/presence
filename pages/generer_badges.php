<?php
require_once '../config/config.php';
require_once '../includes/session.php';
requireLogin();
require_once '../includes/functions.php';
require_once '../classes/Database.php';
require_once '../classes/Employe.php';

$employe = new Employe();
$employes = $employe->getAll();

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        /* ========== STYLES GÉNÉRAUX ========== */
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
        
        /* ========== BADGE STANDARD ID-1 VERTICAL ==========
           Format réel : 54 mm × 85.6 mm
           Taille web : 380 × 602 pixels
           ================================================= */
        .badge-standard {
            width: 380px;
            height: 602px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 15px 45px rgba(0,0,0,0.25);
            overflow: hidden;
            position: relative;
            margin: 20px auto;
            font-family: 'Segoe UI', Arial, sans-serif;
            border: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
        }
        
        /* En-tête */
        .badge-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            flex-shrink: 0;
        }
        .badge-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #f39c12, #e74c3c, #9b59b6);
        }
        .badge-header .logo {
            font-size: 22px;
        }
        .badge-header .title {
            flex: 1;
        }
        .badge-header .title h2 {
            font-size: 13px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.3px;
        }
        .badge-header .title p {
            font-size: 8px;
            opacity: 0.85;
            margin: 1px 0 0;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }
        
        /* Photo - AGRANDIE */
        .badge-photo-section {
            display: flex;
            justify-content: center;
            padding: 12px 0 0;
            flex-shrink: 0;
        }
        .badge-photo {
            width: 115px;
            height: 115px;
            border-radius: 50%;
            border: 4px solid #ffffff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.18);
            overflow: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .badge-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .badge-photo .initials {
            color: white;
            font-size: 42px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Informations - DÉCALÉES VERS LE BAS */
        .badge-info {
            text-align: center;
            padding: 18px 15px 8px;
            margin-top: 20px;
            flex-shrink: 0;
        }
        .badge-info .nom-complet {
            font-size: 16px;
            font-weight: 800;
            color: #1e3c72;
            margin: 0 0 3px;
            letter-spacing: 0.3px;
            line-height: 1.15;
        }
        .badge-info .fonction {
            font-size: 10px;
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin: 0 0 5px;
        }
        .badge-info .departement {
            display: inline-block;
            background: #e8f4fd;
            color: #2980b9;
            padding: 3px 12px;
            border-radius: 15px;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.8px;
        }
        
        /* Séparateur */
        .badge-separator {
            height: 1px;
            background: linear-gradient(90deg, transparent, #ddd, transparent);
            margin: 4px 20px;
            flex-shrink: 0;
        }
        
        /* Matricule */
        .badge-matricule {
            text-align: center;
            padding: 4px 15px;
            flex-shrink: 0;
        }
        .badge-matricule .label {
            font-size: 7px;
            color: #95a5a6;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .badge-matricule .value {
            font-size: 18px;
            font-weight: 800;
            color: #e74c3c;
            letter-spacing: 3px;
            font-family: 'Courier New', monospace;
        }
        
        /* Zone code QR / code-barres - RÉDUITE */
        .badge-code {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 4px 15px;
            flex-direction: column;
            gap: 4px;
            background: #ffffff;
            min-height: 0;
            overflow: hidden;
        }
        .badge-code > div {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .badge-code svg,
        .badge-code canvas,
        .badge-code img {
            max-width: 100%;
            height: auto;
            display: block;
        }
        
        /* Pied du badge */
        .badge-footer {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 6px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 8px;
            letter-spacing: 0.3px;
            flex-shrink: 0;
        }
        .badge-footer .date {
            opacity: 0.9;
        }
        .badge-footer .statut {
            background: #27ae60;
            padding: 2px 8px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 7px;
            letter-spacing: 0.8px;
        }
        
        /* Grille des badges */
        .grid-badges {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
            margin: 20px 0;
            justify-items: center;
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
        
        /* Liste employés */
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
        
        .badge-container {
            display: flex;
            justify-content: center;
            padding: 20px 0;
        }
        
        /* Impression */
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
                grid-template-columns: repeat(2, 380px);
                gap: 15px;
            }
            .badge-standard {
                page-break-inside: avoid;
                box-shadow: none;
                border: 1px solid #000;
                margin: 5px;
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
            <h1>🎫 Génération de Badges Standards</h1>
            <p>Format ID-1 (54 × 85.6 mm) - Format réel des badges d'accès d'entreprise</p>
        </div>
        
        <!-- Onglets format -->
        <div class="badge-tabs no-print">
            <a href="?format=qr<?= $employeSelectionne ? '&employe_id=' . $employeSelectionne['id'] : '' ?><?= $tous ? '&tous=1' : '' ?>" 
               class="tab-btn <?= ($format === 'qr') ? 'active' : '' ?>">📱 QR Code uniquement</a>
            <a href="?format=barcode<?= $employeSelectionne ? '&employe_id=' . $employeSelectionne['id'] : '' ?><?= $tous ? '&tous=1' : '' ?>" 
               class="tab-btn <?= ($format === 'barcode') ? 'active' : '' ?>">📊 Code-barres uniquement</a>
            <a href="?format=both<?= $employeSelectionne ? '&employe_id=' . $employeSelectionne['id'] : '' ?><?= $tous ? '&tous=1' : '' ?>" 
               class="tab-btn <?= ($format === 'both') ? 'active' : '' ?>">🎯 Les deux</a>
        </div>
        
        <?php if (!$employeSelectionne && !$tous): ?>
        <div class="info-section no-print">
            📸 <strong>Photos des employés :</strong> Les photos sont gérées depuis la page 
            <a href="employes.php">👥 Employés</a>. 
            Ajoutez ou modifiez une photo en cliquant sur "+ Ajouter un employé".
        </div>
        
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
                    <button type="submit" class="btn-generer btn-qr" onclick="document.querySelector('[name=format]').value='qr'">📱 QR Code</button>
                    <button type="submit" class="btn-generer btn-barcode" onclick="document.querySelector('[name=format]').value='barcode'">📊 Code-barres</button>
                    <button type="submit" class="btn-generer btn-both" onclick="document.querySelector('[name=format]').value='both'">🎯 Les deux</button>
                </div>
            </form>
        </div>
        
        <div class="card no-print">
            <h2>👥 Générer pour tous les employés</h2>
            <div class="actions-bar">
                <a href="?format=qr&tous=1" class="btn-generer btn-qr">📱 Tous QR</a>
                <a href="?format=barcode&tous=1" class="btn-generer btn-barcode">📊 Tous Code-barres</a>
                <a href="?format=both&tous=1" class="btn-generer btn-both">🎯 Tous Les Deux</a>
            </div>
        </div>
        
        <div class="card no-print">
            <h2>⚡ Génération rapide</h2>
            <?php foreach ($employes as $e): 
                $init = strtoupper(substr($e['prenom'], 0, 1) . substr($e['nom'], 0, 1));
            ?>
                <div class="employe-select-card">
                    <div class="employe-info-mini">
                        <?php if (!empty($e['photo']) && file_exists('../' . $e['photo'])): ?>
                            <img src="../<?= htmlspecialchars($e['photo']) ?>" alt="Photo" class="mini-photo">
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
            $formatLabel = [
                'qr' => '📱 QR Code',
                'barcode' => '📊 Code-barres',
                'both' => '🎯 QR Code + Code-barres'
            ][$format] ?? '📱 QR Code';
        ?>
        <div class="card">
            <div class="card-header no-print">
                <h2>🎫 Badge de <?= htmlspecialchars($employeSelectionne['nom'] . ' ' . $employeSelectionne['prenom']) ?> 
                    <small style="color: #7f8c8d; font-size: 14px;">- <?= $formatLabel ?></small>
                </h2>
                <div class="export-actions">
                    <button class="btn btn-print" onclick="window.print()">🖨️ Imprimer</button>
                    <button class="btn btn-download" onclick="downloadBadge('badge-<?= $employeSelectionne['id'] ?>')">💾 Télécharger PNG</button>
                </div>
            </div>
            
            <div class="badge-container">
                <div class="badge-standard" id="badge-<?= $employeSelectionne['id'] ?>">
                    <div class="badge-header">
                        <div class="logo">🏢</div>
                        <div class="title">
                            <h2><?= SITE_NAME ?></h2>
                            <p>Carte d'accès employé</p>
                        </div>
                    </div>
                    
                    <div class="badge-photo-section">
                        <div class="badge-photo">
                            <?php if (!empty($employeSelectionne['photo']) && file_exists('../' . $employeSelectionne['photo'])): ?>
                                <img src="../<?= htmlspecialchars($employeSelectionne['photo']) ?>" alt="Photo">
                            <?php else: ?>
                                <span class="initials"><?= $initiales ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
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
                    
                    <div class="badge-matricule">
                        <div class="label">Matricule</div>
                        <div class="value"><?= htmlspecialchars($employeSelectionne['matricule']) ?></div>
                    </div>
                    
                    <div class="badge-code" id="code-<?= $employeSelectionne['id'] ?>">
                        <!-- Le QR/Code-barres sera inséré ici par JavaScript -->
                    </div>
                    
                    <div class="badge-footer">
                        <span class="date">📅 <?= date('d/m/Y') ?></span>
                        <span class="statut">● ACTIF</span>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
            window.addEventListener('load', function() {
                console.log('=== Badge standard ID-1 ===');
                console.log('Photo: 115x115 | QR: 110x110 | Format:', '<?= $format ?>');
                
                const matricule = '<?= htmlspecialchars($employeSelectionne['matricule']) ?>';
                const codeContainer = document.getElementById('code-<?= $employeSelectionne['id'] ?>');
                const format = '<?= $format ?>';
                
                codeContainer.style.display = 'flex';
                codeContainer.style.flexDirection = 'column';
                codeContainer.style.alignItems = 'center';
                codeContainer.style.justifyContent = 'center';
                codeContainer.style.gap = '5px';
                
                // ==================== QR CODE UNIQUEMENT ====================
                if (format === 'qr') {
                    console.log('→ QR Code uniquement');
                    const qrDiv = document.createElement('div');
                    qrDiv.style.display = 'flex';
                    qrDiv.style.justifyContent = 'center';
                    qrDiv.style.alignItems = 'center';
                    codeContainer.appendChild(qrDiv);
                    
                    if (typeof QRCode === 'function') {
                        try {
                            new QRCode(qrDiv, {
                                text: matricule,
                                width: 110,
                                height: 110,
                                colorDark: '#1e3c72',
                                colorLight: '#ffffff',
                                correctLevel: QRCode.CorrectLevel.M
                            });
                            console.log('✅ QR généré (110x110)');
                        } catch (e) {
                            console.error('Erreur QR:', e);
                            qrDiv.innerHTML = '<span style="color:red;font-size:11px;">Erreur QR</span>';
                        }
                    } else {
                        qrDiv.innerHTML = '<span style="color:red;font-size:11px;">QRCode non chargé</span>';
                    }
                }
                
                // ==================== CODE-BARRES UNIQUEMENT ====================
                else if (format === 'barcode') {
                    console.log('→ Code-barres uniquement');
                    const barcodeDiv = document.createElement('div');
                    barcodeDiv.style.width = '100%';
                    barcodeDiv.style.display = 'flex';
                    barcodeDiv.style.justifyContent = 'center';
                    barcodeDiv.style.alignItems = 'center';
                    codeContainer.appendChild(barcodeDiv);
                    
                    const barcodeSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                    barcodeDiv.appendChild(barcodeSvg);
                    
                    if (typeof JsBarcode !== 'undefined') {
                        try {
                            JsBarcode(barcodeSvg, matricule, {
                                format: "CODE128",
                                width: 1.5,
                                height: 55,
                                displayValue: true,
                                fontSize: 11,
                                font: "monospace",
                                textMargin: 2,
                                background: "#ffffff",
                                lineColor: "#1e3c72",
                                margin: 5
                            });
                            console.log('✅ Code-barres généré');
                        } catch (e) {
                            console.error('Erreur code-barres:', e);
                            barcodeDiv.innerHTML = '<span style="color:red;font-size:11px;">Erreur code-barres</span>';
                        }
                    } else {
                        codeContainer.innerHTML = '<span style="color:red;font-size:11px;">JsBarcode non chargé</span>';
                    }
                }
                
                // ==================== LES DEUX ====================
                else if (format === 'both') {
                    console.log('→ QR Code + Code-barres');
                    
                    // QR CODE (réduit)
                    const qrDiv = document.createElement('div');
                    qrDiv.style.display = 'flex';
                    qrDiv.style.justifyContent = 'center';
                    qrDiv.style.alignItems = 'center';
                    qrDiv.style.marginBottom = '3px';
                    codeContainer.appendChild(qrDiv);
                    
                    if (typeof QRCode === 'function') {
                        try {
                            new QRCode(qrDiv, {
                                text: matricule,
                                width: 70,
                                height: 70,
                                colorDark: '#1e3c72',
                                colorLight: '#ffffff',
                                correctLevel: QRCode.CorrectLevel.M
                            });
                            console.log('✅ QR généré (70x70)');
                        } catch (e) {
                            console.error('Erreur QR:', e);
                            qrDiv.innerHTML = '<span style="color:red;font-size:11px;">Erreur QR</span>';
                        }
                    } else {
                        qrDiv.innerHTML = '<span style="color:red;font-size:11px;">QRCode non chargé</span>';
                    }
                    
                    // CODE-BARRES
                    const barcodeDiv = document.createElement('div');
                    barcodeDiv.style.width = '100%';
                    barcodeDiv.style.display = 'flex';
                    barcodeDiv.style.justifyContent = 'center';
                    barcodeDiv.style.alignItems = 'center';
                    codeContainer.appendChild(barcodeDiv);
                    
                    const barcodeSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                    barcodeDiv.appendChild(barcodeSvg);
                    
                    if (typeof JsBarcode !== 'undefined') {
                        try {
                            JsBarcode(barcodeSvg, matricule, {
                                format: "CODE128",
                                width: 1.2,
                                height: 40,
                                displayValue: true,
                                fontSize: 9,
                                font: "monospace",
                                textMargin: 2,
                                background: "#ffffff",
                                lineColor: "#1e3c72",
                                margin: 5
                            });
                            console.log('✅ Code-barres généré');
                        } catch (e) {
                            console.error('Erreur code-barres:', e);
                            barcodeDiv.innerHTML = '<span style="color:red;font-size:11px;">Erreur code-barres</span>';
                        }
                    }
                }
            });
            
            function downloadBadge(id) {
                const badge = document.getElementById(id);
                if (typeof html2canvas === 'undefined') {
                    alert('Veuillez patienter...');
                    return;
                }
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
        
        <?php elseif ($tous): 
            $formatLabel = [
                'qr' => '📱 QR Code',
                'barcode' => '📊 Code-barres',
                'both' => '🎯 QR Code + Code-barres'
            ][$format] ?? '📱 QR Code';
        ?>
        <!-- Tous les badges -->
        <div class="card">
            <div class="card-header no-print">
                <h2>🎫 Tous les badges (<?= count($employes) ?> employés) - <?= $formatLabel ?></h2>
                <button class="btn btn-print" onclick="window.print()">🖨️ Imprimer tous</button>
            </div>
            
            <div class="grid-badges">
                <?php foreach ($employes as $e): 
                    $init = strtoupper(substr($e['prenom'], 0, 1) . substr($e['nom'], 0, 1));
                ?>
                    <div class="badge-standard" id="badge-<?= $e['id'] ?>">
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
                        
                        <div class="badge-code" data-matricule="<?= htmlspecialchars($e['matricule']) ?>">
                            <!-- QR/Barcode inséré par JS -->
                        </div>
                        
                        <div class="badge-footer">
                            <span class="date">📅 <?= date('d/m/Y') ?></span>
                            <span class="statut">● ACTIF</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <script>
            window.addEventListener('load', function() {
                console.log('=== Tous les badges standards ===');
                const format = '<?= $format ?>';
                
                document.querySelectorAll('.badge-code').forEach(container => {
                    const matricule = container.getAttribute('data-matricule');
                    
                    container.style.display = 'flex';
                    container.style.flexDirection = 'column';
                    container.style.alignItems = 'center';
                    container.style.justifyContent = 'center';
                    container.style.gap = '5px';
                    
                    // QR UNIQUEMENT
                    if (format === 'qr') {
                        const qrDiv = document.createElement('div');
                        qrDiv.style.display = 'flex';
                        qrDiv.style.justifyContent = 'center';
                        qrDiv.style.alignItems = 'center';
                        container.appendChild(qrDiv);
                        
                        if (typeof QRCode === 'function') {
                            try {
                                new QRCode(qrDiv, {
                                    text: matricule,
                                    width: 110,
                                    height: 110,
                                    colorDark: '#1e3c72',
                                    colorLight: '#ffffff',
                                    correctLevel: QRCode.CorrectLevel.M
                                });
                            } catch (e) {
                                console.error('Erreur QR ' + matricule, e);
                            }
                        }
                    }
                    
                    // CODE-BARRES UNIQUEMENT
                    else if (format === 'barcode') {
                        const barcodeDiv = document.createElement('div');
                        barcodeDiv.style.width = '100%';
                        barcodeDiv.style.display = 'flex';
                        barcodeDiv.style.justifyContent = 'center';
                        barcodeDiv.style.alignItems = 'center';
                        container.appendChild(barcodeDiv);
                        
                        const barcodeSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                        barcodeDiv.appendChild(barcodeSvg);
                        
                        if (typeof JsBarcode !== 'undefined') {
                            try {
                                JsBarcode(barcodeSvg, matricule, {
                                    format: "CODE128",
                                    width: 1.5,
                                    height: 55,
                                    displayValue: true,
                                    fontSize: 11,
                                    font: "monospace",
                                    textMargin: 2,
                                    background: "#ffffff",
                                    lineColor: "#1e3c72",
                                    margin: 5
                                });
                            } catch (e) {
                                console.error('Erreur code-barres ' + matricule, e);
                            }
                        }
                    }
                    
                    // LES DEUX
                    else if (format === 'both') {
                        // QR
                        const qrDiv = document.createElement('div');
                        qrDiv.style.display = 'flex';
                        qrDiv.style.justifyContent = 'center';
                        qrDiv.style.alignItems = 'center';
                        qrDiv.style.marginBottom = '3px';
                        container.appendChild(qrDiv);
                        
                        if (typeof QRCode === 'function') {
                            try {
                                new QRCode(qrDiv, {
                                    text: matricule,
                                    width: 70,
                                    height: 70,
                                    colorDark: '#1e3c72',
                                    colorLight: '#ffffff',
                                    correctLevel: QRCode.CorrectLevel.M
                                });
                            } catch (e) {
                                console.error('Erreur QR ' + matricule, e);
                            }
                        }
                        
                        // Code-barres
                        const barcodeDiv = document.createElement('div');
                        barcodeDiv.style.width = '100%';
                        barcodeDiv.style.display = 'flex';
                        barcodeDiv.style.justifyContent = 'center';
                        barcodeDiv.style.alignItems = 'center';
                        container.appendChild(barcodeDiv);
                        
                        const barcodeSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                        barcodeDiv.appendChild(barcodeSvg);
                        
                        if (typeof JsBarcode !== 'undefined') {
                            try {
                                JsBarcode(barcodeSvg, matricule, {
                                    format: "CODE128",
                                    width: 1.2,
                                    height: 40,
                                    displayValue: true,
                                    fontSize: 9,
                                    font: "monospace",
                                    textMargin: 2,
                                    background: "#ffffff",
                                    lineColor: "#1e3c72",
                                    margin: 5
                                });
                            } catch (e) {
                                console.error('Erreur code-barres ' + matricule, e);
                            }
                        }
                    }
                });
                
                console.log('✅ Tous les badges générés !');
            });
        </script>
        <?php endif; ?>
    </div>
</body>
</html>