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
$employes = $employe->getAll();

$message = '';
$messageType = '';

// Traitement du pointage
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeId = $_POST['employe_id'] ?? '';
    $type = $_POST['type'] ?? '';
    $matricule = $_POST['matricule'] ?? '';
    
    // Si matricule fourni (scan ou saisie manuelle)
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
        $emp = $employe->getById($employeId);
        if (!$emp) {
            $message = "❌ Employé non trouvé !";
            $messageType = 'danger';
        } else {
            // Vérifier le dernier pointage
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
        $message = "⚠️ Veuillez scanner un badge ou saisir un matricule !";
        $messageType = 'warning';
    }
}

// Récupération des pointages du jour
$pointagesAujourdhui = $pointage->getPointages([
    'date_debut' => date('Y-m-d'),
    'date_fin' => date('Y-m-d')
]);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pointage - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .scanner-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .scanner-container {
                grid-template-columns: 1fr;
            }
        }
        .scanner-box {
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #reader {
            width: 100%;
            height: 100%;
            border-radius: 12px;
        }
        #reader video {
            border-radius: 12px;
            width: 100% !important;
            height: 100% !important;
            object-fit: cover;
        }
        .scanner-placeholder {
            color: white;
            text-align: center;
            padding: 40px;
        }
        .scanner-placeholder .icon {
            font-size: 64px;
            display: block;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        .scanner-controls {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .scanner-controls .btn {
            flex: 1;
            min-width: 150px;
        }
        .btn-scan {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-scan:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-scan.active {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        .btn-stop {
            background: #e74c3c;
            color: white;
            padding: 15px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-stop:hover {
            background: #c0392b;
        }
        .manuel-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .manuel-box h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 18px;
            text-align: center;
        }
        .matricule-input {
            font-size: 28px;
            text-align: center;
            letter-spacing: 5px;
            font-weight: bold;
            padding: 20px;
            text-transform: uppercase;
            border: 3px solid #ddd;
            border-radius: 12px;
            transition: all 0.3s;
        }
        .matricule-input:focus {
            border-color: #27ae60;
            outline: none;
            box-shadow: 0 0 0 4px rgba(39, 174, 96, 0.2);
        }
        .scanner-status {
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 14px;
            font-weight: 600;
        }
        .status-idle {
            background: #ecf0f1;
            color: #7f8c8d;
        }
        .status-scanning {
            background: #d4edda;
            color: #155724;
            animation: pulse 1.5s infinite;
        }
        .status-found {
            background: #cce5ff;
            color: #004085;
        }
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.6; }
            100% { opacity: 1; }
        }
        .separator {
            text-align: center;
            color: #95a5a6;
            margin: 20px 0;
            position: relative;
        }
        .separator::before,
        .separator::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #ddd;
        }
        .separator::before { left: 0; }
        .separator::after { right: 0; }
        .type-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }
        .btn-entree {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            padding: 20px;
            font-size: 18px;
            font-weight: bold;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-entree:hover {
            background: linear-gradient(135deg, #219a52 0%, #27ae60 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(39, 174, 96, 0.4);
        }
        .btn-sortie {
            background: linear-gradient(135deg, #e74c3c 0%, #f39c12 100%);
            color: white;
            padding: 20px;
            font-size: 18px;
            font-weight: bold;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-sortie:hover {
            background: linear-gradient(135deg, #c0392b 0%, #e67e22 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(231, 76, 60, 0.4);
        }
        .badge-scan {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        .badge-scan.actif {
            background: #d4edda;
            color: #155724;
        }
        .badge-scan.inactif {
            background: #f8d7da;
            color: #721c24;
        }
        .historique-recent {
            max-height: 200px;
            overflow-y: auto;
        }
        .flash-success {
            animation: flash-success 1s ease;
        }
        @keyframes flash-success {
            0%, 100% { background: #f8f9fa; }
            50% { background: #d4edda; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand"><?= SITE_NAME ?></a>
            <ul class="nav-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="pointage.php" class="active">⏱️ Pointage</a></li>
                <li><a href="employes.php">👥 Employés</a></li>
                 <li><a href="generer_badges.php">🎫 Badges</a></li>
                <li><a href="export.php">📤 Export</a></li>
                <li><a href="admin_logout.php" style="color: #e74c3c;">🚪 Déconnexion</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>⏱️ Enregistrement des pointages</h1>
            <p>
                Scannez le badge ou saisissez le matricule
                <span id="scanStatus" class="badge-scan inactif">Scanner inactif</span>
            </p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?> flash-success"><?= $message ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>📌 Nouveau pointage</h2>
            
            <form method="POST" id="pointageForm">
                <!-- Container principal : Scanner + Saisie manuelle -->
                <div class="scanner-container">
                    
                    <!-- COLONNE 1 : Scanner avec caméra -->
                    <div>
                        <div class="scanner-box">
                            <div id="reader"></div>
                            <div id="scannerPlaceholder" class="scanner-placeholder">
                                <span class="icon">📷</span>
                                <p>Cliquez sur "Démarrer le scanner"<br>pour scanner un badge</p>
                            </div>
                        </div>
                        
                        <div class="scanner-controls">
                            <button type="button" id="btnStartScan" class="btn-scan" onclick="startScanner()">
                                📷 Démarrer le scanner
                            </button>
                            <button type="button" id="btnStopScan" class="btn-stop" onclick="stopScanner()" style="display: none;">
                                ⏹️ Arrêter
                            </button>
                        </div>
                        
                        <div id="scannerStatus" class="scanner-status status-idle">
                            Scanner inactif
                        </div>
                    </div>
                    
                    <!-- COLONNE 2 : Saisie manuelle -->
                    <div class="manuel-box">
                        <h3>⌨️ Saisie manuelle</h3>
                        <p style="text-align: center; color: #7f8c8d; font-size: 13px; margin-bottom: 15px;">
                            Si le scanner ne fonctionne pas,<br>tapez le matricule ci-dessous
                        </p>
                        
                        <input type="text" 
                               name="matricule" 
                               id="matriculeInput" 
                               class="matricule-input" 
                               placeholder="EMP001"
                               autocomplete="off">
                        
                        <p style="text-align: center; color: #95a5a6; font-size: 12px; margin-top: 10px;">
                            Appuyez sur Entrée ou cliquez sur un bouton
                        </p>
                    </div>
                </div>
                
                <!-- Sélection par liste (optionnelle) -->
                <div class="separator">OU</div>
                
                <div class="form-group">
                    <label>👤 Sélectionner un employé dans la liste</label>
                    <select name="employe_id" id="employeSelect" style="font-size: 16px; padding: 12px;">
                        <option value="">-- Choisir un employé --</option>
                        <?php foreach ($employes as $e): ?>
                            <option value="<?= $e['id'] ?>">
                                <?= htmlspecialchars($e['matricule']) ?> - <?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Boutons de validation -->
                <div class="type-buttons">
                    <button type="submit" name="type" value="entree" class="btn-entree">
                        🟢 VALIDER L'ENTRÉE
                    </button>
                    <button type="submit" name="type" value="sortie" class="btn-sortie">
                        🔴 VALIDER LA SORTIE
                    </button>
                </div>
            </form>
            
            <!-- Boutons rapides -->
            <div style="margin-top: 15px;">
                <label style="font-size: 12px; color: #95a5a6;">⚡ Saisie rapide :</label>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 5px;">
                    <button type="button" class="quick-btn" onclick="setMatricule('EMP001')" 
                            style="padding: 8px 15px; background: #ecf0f1; border: 1px solid #ddd; border-radius: 5px; cursor: pointer;">
                        EMP001 - Jean
                    </button>
                    <button type="button" class="quick-btn" onclick="setMatricule('EMP002')"
                            style="padding: 8px 15px; background: #ecf0f1; border: 1px solid #ddd; border-radius: 5px; cursor: pointer;">
                        EMP002 - Marie
                    </button>
                    <button type="button" class="quick-btn" onclick="setMatricule('EMP003')"
                            style="padding: 8px 15px; background: #ecf0f1; border: 1px solid #ddd; border-radius: 5px; cursor: pointer;">
                        EMP003 - Pierre
                    </button>
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
                <div class="historique-recent">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Matricule</th>
                                <th>Employé</th>
                                <th>Type</th>
                                <th>Heure</th>
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
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #95a5a6; padding: 20px;">
                    📭 Aucun pointage enregistré aujourd'hui
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bibliothèque HTML5 QR Code Scanner -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    
    <script>
        let html5QrCode = null;
        let isScanning = false;
        
        // ==================== SCANNER ====================
        
        function startScanner() {
            if (isScanning) return;
            
            document.getElementById('scannerPlaceholder').style.display = 'none';
            
            html5QrCode = new Html5Qrcode("reader");
            
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0,
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.QR_CODE,
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.CODE_39,
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.EAN_8,
                    Html5QrcodeSupportedFormats.UPC_A,
                    Html5QrcodeSupportedFormats.UPC_E,
                    Html5QrcodeSupportedFormats.CODE_93,
                    Html5QrcodeSupportedFormats.ITF,
                    Html5QrcodeSupportedFormats.CODABAR
                ]
            };
            
            html5QrCode.start(
                { facingMode: "environment" },
                config,
                onScanSuccess,
                onScanError
            ).then(() => {
                isScanning = true;
                updateScannerUI(true);
                updateStatus('scanning', '🔍 Scanner actif - Présentez le badge');
            }).catch(err => {
                console.error("Erreur scanner:", err);
                updateStatus('error', '❌ Impossible d\'accéder à la caméra');
                document.getElementById('scannerPlaceholder').style.display = 'block';
                alert('Erreur: Impossible d\'accéder à la caméra.\n\nVérifiez que:\n- La caméra est autorisée\n- Aucune autre application utilise la caméra\n- Le site est en HTTPS ou localhost');
            });
        }
        
        function stopScanner() {
            if (!html5QrCode || !isScanning) return;
            
            html5QrCode.stop().then(() => {
                isScanning = false;
                html5QrCode.clear();
                updateScannerUI(false);
                updateStatus('idle', 'Scanner inactif');
                document.getElementById('scannerPlaceholder').style.display = 'flex';
            }).catch(err => {
                console.error("Erreur arrêt:", err);
            });
        }
        
        function onScanSuccess(decodedText, decodedResult) {
            console.log("Code scanné:", decodedText);
            
            const matricule = decodedText.trim().toUpperCase();
            
            document.getElementById('matriculeInput').value = matricule;
            document.getElementById('employeSelect').value = '';
            
            updateStatus('found', '✅ Badge scanné: ' + matricule);
            playBeep();
            stopScanner();
            
            document.querySelector('.btn-entree').focus();
            
            document.getElementById('matriculeInput').classList.add('flash-success');
            setTimeout(() => {
                document.getElementById('matriculeInput').classList.remove('flash-success');
            }, 1000);
        }
        
        function onScanError(errorMessage) {
            // Erreur silencieuse
        }
        
        function updateScannerUI(scanning) {
            const btnStart = document.getElementById('btnStartScan');
            const btnStop = document.getElementById('btnStopScan');
            
            if (scanning) {
                btnStart.style.display = 'none';
                btnStop.style.display = 'block';
                document.getElementById('scanStatus').textContent = 'Scanner actif';
                document.getElementById('scanStatus').className = 'badge-scan actif';
            } else {
                btnStart.style.display = 'block';
                btnStop.style.display = 'none';
                document.getElementById('scanStatus').textContent = 'Scanner inactif';
                document.getElementById('scanStatus').className = 'badge-scan inactif';
            }
        }
        
        function updateStatus(type, message) {
            const status = document.getElementById('scannerStatus');
            status.textContent = message;
            status.className = 'scanner-status status-' + type;
        }
        
        function playBeep() {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.1);
            } catch (e) {}
        }
        
        // ==================== SAISIE MANUELLE ====================
        
        function setMatricule(matricule) {
            document.getElementById('matriculeInput').value = matricule.toUpperCase();
            document.getElementById('employeSelect').value = '';
            document.getElementById('matriculeInput').focus();
        }
        
        document.getElementById('matriculeInput').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
            if (this.value) {
                document.getElementById('employeSelect').value = '';
            }
        });
        
        document.getElementById('employeSelect').addEventListener('change', function() {
            if (this.value) {
                document.getElementById('matriculeInput').value = '';
            }
        });
        
        // ==================== VALIDATION ====================
        
        document.querySelectorAll('.btn-entree, .btn-sortie').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const matricule = document.getElementById('matriculeInput').value.trim();
                const select = document.getElementById('employeSelect');
                const type = this.value === 'entree' ? 'ENTRÉE' : 'SORTIE';
                
                let nom = '';
                if (select.value) {
                    nom = select.options[select.selectedIndex].text;
                } else if (matricule) {
                    nom = 'Matricule: ' + matricule;
                } else {
                    e.preventDefault();
                    alert('⚠️ Veuillez scanner un badge, saisir un matricule ou sélectionner un employé !');
                    return false;
                }
                
                if (!confirm('Confirmer le pointage de ' + type + ' pour ' + nom + ' ?')) {
                    e.preventDefault();
                }
            });
        });
        
        document.getElementById('matriculeInput').focus();
        
        document.getElementById('matriculeInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.querySelector('.btn-entree').focus();
            }
        });
    </script>
</body>
</html>