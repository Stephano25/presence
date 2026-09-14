<?php
require_once '../config/config.php';
require_once '../includes/session.php';
requireLogin();
require_once '../includes/functions.php';
require_once '../classes/Database.php';
require_once '../classes/Employe.php';

$employe = new Employe();
$employes = $employe->getAll();

$message = '';
$messageType = '';

// Traitement de la génération
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $employe_id = $_POST['employe_id'] ?? '';
    $format = $_POST['format'] ?? '';
    
    if ($action === 'generer' && !empty($employe_id) && !empty($format)) {
        $emp = $employe->getById($employe_id);
        if ($emp) {
            // Rediriger vers la page de génération
            header("Location: generer_badges.php?employe_id=$employe_id&format=$format");
            exit();
        }
    }
    
    // Génération pour tous les employés
    if ($action === 'generer_tous' && !empty($format)) {
        header("Location: generer_badges.php?format=$format&tous=1");
        exit();
    }
}

// Récupérer l'employé sélectionné
$employeSelectionne = null;
if (isset($_GET['employe_id']) && !empty($_GET['employe_id'])) {
    $employeSelectionne = $employe->getById($_GET['employe_id']);
}

$format = $_GET['format'] ?? '';
$tous = isset($_GET['tous']) && $_GET['tous'] == 1;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Générer Badges - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../css/style.css">
    
    <!-- Bibliothèques pour QR Code et Code-barres -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    
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
        .badge-preview {
            background: white;
            border: 2px dashed #ddd;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin: 20px 0;
            min-height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .badge-preview canvas,
        .badge-preview svg,
        .badge-preview img {
            max-width: 100%;
            height: auto;
        }
        .badge-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 20px;
            border: 2px solid #2c3e50;
        }
        .badge-card-header {
            background: #2c3e50;
            color: white;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .badge-card-employe {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }
        .badge-card-matricule {
            font-size: 24px;
            font-weight: bold;
            color: #e74c3c;
            letter-spacing: 3px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
        }
        .badge-card-footer {
            font-size: 12px;
            color: #95a5a6;
            margin-top: 15px;
        }
        .grid-badges {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
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
        }
        .employe-select-card:hover {
            background: #ecf0f1;
        }
        .employe-info-mini {
            display: flex;
            flex-direction: column;
        }
        .employe-info-mini strong {
            color: #2c3e50;
            font-size: 16px;
        }
        .employe-info-mini span {
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
        }
        .btn-mini.qr {
            background: #667eea;
        }
        .btn-mini.qr:hover {
            background: #5568d3;
        }
        .btn-mini.barcode {
            background: #f39c12;
        }
        .btn-mini.barcode:hover {
            background: #e67e22;
        }
        @media print {
            .navbar, .no-print, .actions-bar, .badge-tabs {
                display: none !important;
            }
            .container {
                max-width: 100%;
                padding: 0;
            }
            .grid-badges {
                grid-template-columns: repeat(2, 1fr);
            }
            .badge-card {
                page-break-inside: avoid;
                box-shadow: none;
                border: 2px solid #000;
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
            <h1>🎫 Génération de Badges</h1>
            <p>Générez des QR codes ou des codes-barres pour les employés</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>
        
        <!-- Onglets de format -->
        <div class="badge-tabs no-print">
            <button class="tab-btn <?= ($format === 'qr' || empty($format)) ? 'active' : '' ?>" 
                    onclick="window.location.href='?format=qr'">
                📱 QR Code
            </button>
            <button class="tab-btn <?= ($format === 'barcode') ? 'active' : '' ?>" 
                    onclick="window.location.href='?format=barcode'">
                📊 Code-barres
            </button>
            <button class="tab-btn <?= ($format === 'both') ? 'active' : '' ?>" 
                    onclick="window.location.href='?format=both'">
                🎯 Les deux
            </button>
        </div>
        
        <?php if (empty($format)): $format = 'qr'; endif; ?>
        
        <!-- SECTION 1 : Générer pour un employé spécifique -->
        <?php if (!$tous): ?>
        <div class="card no-print">
            <h2>👤 Générer pour un employé</h2>
            
            <form method="POST" style="margin: 20px 0;">
                <input type="hidden" name="action" value="generer">
                <input type="hidden" name="format" value="<?= $format ?>">
                
                <div class="form-group">
                    <label>Sélectionner un employé</label>
                    <select name="employe_id" required style="font-size: 16px; padding: 12px;">
                        <option value="">-- Choisir un employé --</option>
                        <?php foreach ($employes as $e): ?>
                            <option value="<?= $e['id'] ?>" <?= ($employeSelectionne && $employeSelectionne['id'] == $e['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['matricule']) ?> - <?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="actions-bar">
                    <button type="submit" class="btn-generer btn-qr">
                        📱 Générer QR Code
                    </button>
                    <button type="submit" class="btn-generer btn-barcode" onclick="document.querySelector('[name=format]').value='barcode'">
                        📊 Générer Code-barres
                    </button>
                    <button type="submit" class="btn-generer btn-print" onclick="document.querySelector('[name=format]').value='both'">
                        🎯 Générer les deux
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Bouton pour générer pour TOUS -->
        <?php if (!$employeSelectionne && !$tous): ?>
        <div class="card no-print">
            <h2>👥 Générer pour tous les employés</h2>
            <p style="color: #7f8c8d; margin: 10px 0;">Générer des badges pour tous les employés actifs</p>
            
            <div class="actions-bar">
                <a href="?format=qr&tous=1" class="btn-generer btn-qr">
                    📱 Tous les QR Codes
                </a>
                <a href="?format=barcode&tous=1" class="btn-generer btn-barcode">
                    📊 Tous les Code-barres
                </a>
                <a href="?format=both&tous=1" class="btn-generer btn-print">
                    🎯 Tous (QR + Code-barres)
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- SECTION 2 : Affichage des badges -->
        <?php if ($employeSelectionne): ?>
            <!-- UN SEUL EMPLOYÉ -->
            <div class="card">
                <div class="card-header no-print">
                    <h2>🎫 Badge de <?= htmlspecialchars($employeSelectionne['nom'] . ' ' . $employeSelectionne['prenom']) ?></h2>
                    <div class="export-actions">
                        <button class="btn btn-print" onclick="window.print()">🖨️ Imprimer</button>
                        <button class="btn btn-download" onclick="downloadBadge()">💾 Télécharger</button>
                    </div>
                </div>
                
                <div class="badge-preview" id="badgePreview">
                    <div class="badge-card" id="badgeCard">
                        <div class="badge-card-header">
                            🏢 <?= SITE_NAME ?>
                        </div>
                        
                        <?php if ($format === 'qr' || $format === 'both'): ?>
                            <div id="qrcodeContainer" style="margin: 15px 0;"></div>
                        <?php endif; ?>
                        
                        <?php if ($format === 'barcode' || $format === 'both'): ?>
                            <svg id="barcodeContainer" style="margin: 15px 0; width: 100%;"></svg>
                        <?php endif; ?>
                        
                        <div class="badge-card-employe">
                            <?= htmlspecialchars($employeSelectionne['nom'] . ' ' . $employeSelectionne['prenom']) ?>
                        </div>
                        
                        <div class="badge-card-matricule">
                            <?= htmlspecialchars($employeSelectionne['matricule']) ?>
                        </div>
                        
                        <div style="font-size: 13px; color: #34495e; margin: 8px 0;">
                            <?= htmlspecialchars($employeSelectionne['poste'] ?? 'Employé') ?>
                        </div>
                        
                        <div class="badge-card-footer">
                            <?= date('d/m/Y') ?> - Badge d'accès
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
                            width: 200,
                            margin: 2,
                            color: {
                                dark: '#2c3e50',
                                light: '#ffffff'
                            }
                        },
                        function(error, canvas) {
                            if (error) {
                                console.error(error);
                            } else {
                                document.getElementById('qrcodeContainer').appendChild(canvas);
                            }
                        }
                    );
                    <?php endif; ?>
                    
                    // Générer le Code-barres
                    <?php if ($format === 'barcode' || $format === 'both'): ?>
                    JsBarcode("#barcodeContainer", "<?= htmlspecialchars($employeSelectionne['matricule']) ?>", {
                        format: "CODE128",
                        width: 2,
                        height: 70,
                        displayValue: true,
                        fontSize: 16,
                        font: "monospace",
                        textMargin: 5,
                        background: "#ffffff",
                        lineColor: "#2c3e50"
                    });
                    <?php endif; ?>
                    
                    // Fonction de téléchargement
                    function downloadBadge() {
                        const badge = document.getElementById('badgeCard');
                        const employeNom = "<?= htmlspecialchars($employeSelectionne['matricule']) ?>";
                        
                        // Utiliser html2canvas pour capturer le badge
                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
                        script.onload = function() {
                            html2canvas(badge, {
                                backgroundColor: '#ffffff',
                                scale: 2
                            }).then(canvas => {
                                const link = document.createElement('a');
                                link.download = 'badge_' + employeNom + '.png';
                                link.href = canvas.toDataURL('image/png');
                                link.click();
                            });
                        };
                        document.head.appendChild(script);
                    }
                </script>
            </div>
        
        <?php elseif ($tous): ?>
            <!-- TOUS LES EMPLOYÉS -->
            <div class="card">
                <div class="card-header no-print">
                    <h2>🎫 Tous les badges (<?= count($employes) ?> employés)</h2>
                    <div class="export-actions">
                        <button class="btn btn-print" onclick="window.print()">🖨️ Imprimer tous</button>
                    </div>
                </div>
                
                <div class="grid-badges">
                    <?php foreach ($employes as $e): ?>
                        <div class="badge-card">
                            <div class="badge-card-header">
                                🏢 <?= SITE_NAME ?>
                            </div>
                            
                            <?php if ($format === 'qr' || $format === 'both'): ?>
                                <div class="qrcode-container" data-matricule="<?= htmlspecialchars($e['matricule']) ?>" style="margin: 15px 0;"></div>
                            <?php endif; ?>
                            
                            <?php if ($format === 'barcode' || $format === 'both'): ?>
                                <svg class="barcode-container" data-matricule="<?= htmlspecialchars($e['matricule']) ?>" style="margin: 15px 0; width: 100%;"></svg>
                            <?php endif; ?>
                            
                            <div class="badge-card-employe">
                                <?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?>
                            </div>
                            
                            <div class="badge-card-matricule">
                                <?= htmlspecialchars($e['matricule']) ?>
                            </div>
                            
                            <div style="font-size: 13px; color: #34495e; margin: 8px 0;">
                                <?= htmlspecialchars($e['poste'] ?? 'Employé') ?>
                            </div>
                            
                            <div class="badge-card-footer">
                                <?= date('d/m/Y') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <script>
                    // Générer tous les QR Codes
                    <?php if ($format === 'qr' || $format === 'both'): ?>
                    document.querySelectorAll('.qrcode-container').forEach(container => {
                        const matricule = container.getAttribute('data-matricule');
                        QRCode.toCanvas(
                            document.createElement('canvas'),
                            matricule,
                            {
                                width: 150,
                                margin: 2,
                                color: {
                                    dark: '#2c3e50',
                                    light: '#ffffff'
                                }
                            },
                            function(error, canvas) {
                                if (!error) {
                                    container.appendChild(canvas);
                                }
                            }
                        );
                    });
                    <?php endif; ?>
                    
                    // Générer tous les Code-barres
                    <?php if ($format === 'barcode' || $format === 'both'): ?>
                    document.querySelectorAll('.barcode-container').forEach(svg => {
                        const matricule = svg.getAttribute('data-matricule');
                        JsBarcode(svg, matricule, {
                            format: "CODE128",
                            width: 1.5,
                            height: 60,
                            displayValue: true,
                            fontSize: 14,
                            font: "monospace",
                            textMargin: 5,
                            background: "#ffffff",
                            lineColor: "#2c3e50"
                        });
                    });
                    <?php endif; ?>
                </script>
            </div>
        <?php endif; ?>
        
        <!-- Liste rapide des employés -->
        <?php if (!$employeSelectionne && !$tous): ?>
        <div class="card no-print">
            <h2>⚡ Génération rapide</h2>
            <p style="color: #7f8c8d; margin: 10px 0;">Cliquez sur un employé pour générer son badge</p>
            
            <?php foreach ($employes as $e): ?>
                <div class="employe-select-card">
                    <div class="employe-info-mini">
                        <strong><?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?></strong>
                        <span>Matricule: <?= htmlspecialchars($e['matricule']) ?> | <?= htmlspecialchars($e['poste'] ?? 'Employé') ?></span>
                    </div>
                    <div style="display: flex; gap: 5px;">
                        <a href="?employe_id=<?= $e['id'] ?>&format=qr" class="btn-mini qr">
                            📱 QR
                        </a>
                        <a href="?employe_id=<?= $e['id'] ?>&format=barcode" class="btn-mini barcode">
                            📊 Code-barres
                        </a>
                        <a href="?employe_id=<?= $e['id'] ?>&format=both" class="btn-mini qr" style="background: #2c3e50;">
                            🎯 Les deux
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>