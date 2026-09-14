<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'includes/functions.php';

// Si déjà connecté
if (isset($_SESSION['employe_id'])) {
    header('Location: pointage_badge.php');
    exit();
}

$error = '';
$matricule = '';

// Traitement du scan
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['matricule'])) {
    $matricule = $_POST['matricule'] ?? $_GET['matricule'] ?? '';
    $matricule = strtoupper(trim($matricule));
    
    if (!empty($matricule)) {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=pointage_db", 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare("SELECT * FROM employes WHERE matricule = :matricule AND status = 'actif'");
            $stmt->execute(['matricule' => $matricule]);
            $employe = $stmt->fetch();
            
            if ($employe) {
                // Connexion automatique
                $_SESSION['employe_id'] = $employe['id'];
                $_SESSION['employe_nom'] = $employe['nom'];
                $_SESSION['employe_prenom'] = $employe['prenom'];
                $_SESSION['employe_matricule'] = $employe['matricule'];
                
                header('Location: pointage_badge.php');
                exit();
            } else {
                $error = "❌ Matricule invalide ou employé inactif";
            }
        } catch (PDOException $e) {
            $error = "❌ Erreur de connexion";
        }
    } else {
        $error = "❌ Veuillez saisir un matricule";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Badge - <?= SITE_NAME ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
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
            padding: 50px 40px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        .scan-icon {
            font-size: 80px;
            margin-bottom: 20px;
            display: block;
        }
        .container h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .container p {
            color: #7f8c8d;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group input {
            width: 100%;
            padding: 20px;
            font-size: 32px;
            text-align: center;
            letter-spacing: 5px;
            font-weight: bold;
            border: 3px solid #ddd;
            border-radius: 12px;
            transition: all 0.3s;
            text-transform: uppercase;
        }
        .form-group input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
        }
        .form-group input::placeholder {
            font-size: 16px;
            letter-spacing: 1px;
            font-weight: normal;
            color: #ccc;
        }
        .btn {
            width: 100%;
            padding: 18px;
            font-size: 20px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        .btn:active {
            transform: translateY(0);
        }
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin: 15px 0;
            font-weight: 500;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #e74c3c;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #27ae60;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
            font-size: 12px;
            color: #95a5a6;
        }
        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .matricules-exemples {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 13px;
            color: #666;
        }
        .matricules-exemples code {
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: bold;
            color: #2c3e50;
        }
        .auto-scan {
            margin-top: 15px;
            padding: 10px;
            background: #e3f2fd;
            border-radius: 8px;
            font-size: 13px;
            color: #0d47a1;
        }
        .auto-scan input {
            width: 80%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            text-align: center;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <span class="scan-icon">🏷️</span>
        <h1>Scan du Badge</h1>
        <p>Présentez votre badge ou saisissez votre matricule</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group">
                <input type="text" 
                       id="matricule" 
                       name="matricule" 
                       placeholder="Saisir le matricule" 
                       value="<?= htmlspecialchars($matricule) ?>"
                       autofocus
                       required>
            </div>
            <button type="submit" class="btn">
                🔍 Valider le scan
            </button>
        </form>

        <!-- Exemples de matricules -->
        <div class="matricules-exemples">
            <strong>Matricules de test :</strong><br>
            <code>EMP001</code> - Jean Rakoto<br>
            <code>EMP002</code> - Marie Rabe<br>
            <code>EMP003</code> - Pierre Andrian
        </div>

        <!-- Simulation de scan avec lecteur -->
        <div class="auto-scan">
            <label>🔧 Simulation scan :</label><br>
            <input type="text" id="simulateMatricule" placeholder="EMP001" style="text-transform: uppercase;">
            <button onclick="simulerScan()" style="margin-top: 8px; padding: 8px 20px; background: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer;">
                ⚡ Simuler le scan
            </button>
        </div>

        <a href="pages/dashboard.php" class="back-link">← Retour à l'accueil admin</a>

        <div class="footer">
            <?= SITE_NAME ?> - Version 1.0
        </div>
    </div>

    <script>
        // Focus automatique sur le champ
        document.getElementById('matricule').focus();

        // Simulation de scan
        function simulerScan() {
            const input = document.getElementById('simulateMatricule');
            const matricule = input.value.toUpperCase().trim();
            if (matricule) {
                document.getElementById('matricule').value = matricule;
                document.querySelector('form').submit();
            } else {
                alert('Veuillez saisir un matricule à simuler');
            }
        }

        // Enter pour soumettre
        document.getElementById('matricule').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });

        // Auto-uppercase
        document.getElementById('matricule').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });

        // Pour les lecteurs de badge USB
        // Certains lecteurs envoient un code-barres suivi d'un Enter
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const input = document.getElementById('matricule');
                if (input.value.length > 0) {
                    document.querySelector('form').submit();
                }
            }
        });

        // Après 5 secondes d'inactivité, focus sur le champ
        setTimeout(function() {
            document.getElementById('matricule').focus();
        }, 100);
    </script>
</body>
</html>