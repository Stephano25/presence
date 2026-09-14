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

$message = '';
$messageType = '';

// Récupération des filtres
$date_debut = $_GET['date_debut'] ?? date('Y-m-01');
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');
$employe_id = $_GET['employe_id'] ?? '';
$type = $_GET['type'] ?? '';

// Récupération des pointages filtrés
$filters = [
    'date_debut' => $date_debut,
    'date_fin' => $date_fin
];
if (!empty($employe_id)) {
    $filters['employe_id'] = $employe_id;
}
if (!empty($type)) {
    $filters['type'] = $type;
}

$pointages = $pointage->getPointages($filters);
$employes = $employe->getAll();

// Gestion de l'export
if (isset($_GET['export']) && !empty($pointages)) {
    $format = $_GET['format'] ?? 'csv';
    $exportData = [];
    
    foreach ($pointages as $p) {
        $exportData[] = [
            'Matricule' => $p['matricule'],
            'Employé' => $p['nom'] . ' ' . $p['prenom'],
            'Type' => ucfirst($p['type']),
            'Date' => date('d/m/Y', strtotime($p['date_heure'])),
            'Heure' => date('H:i:s', strtotime($p['date_heure'])),
            'Latitude' => $p['latitude'] ?? '-',
            'Longitude' => $p['longitude'] ?? '-'
        ];
    }
    
    $filename = 'pointages_' . date('Y-m-d_H-i');
    
    if ($format === 'xls') {
        // Export Excel amélioré
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        header('Cache-Control: max-age=0');
        
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" 
                    xmlns:x="urn:schemas-microsoft-com:office:excel" 
                    xmlns="http://www.w3.org/TR/REC-html40">
              <head>
                <meta charset="UTF-8">
                <!--[if gte mso 9]>
                <xml>
                  <x:ExcelWorkbook>
                    <x:ExcelWorksheets>
                      <x:ExcelWorksheet>
                        <x:Name>Pointages</x:Name>
                        <x:WorksheetOptions>
                          <x:DisplayGridlines/>
                        </x:WorksheetOptions>
                      </x:ExcelWorksheet>
                    </x:ExcelWorksheets>
                  </x:ExcelWorkbook>
                </xml>
                <![endif]-->
                <style>
                  table { border-collapse: collapse; width: 100%; }
                  th { 
                    background-color: #2c3e50; 
                    color: white; 
                    font-weight: bold; 
                    padding: 10px;
                    border: 1px solid #000;
                  }
                  td { 
                    padding: 8px; 
                    border: 1px solid #ddd;
                  }
                  .entree { color: green; }
                  .sortie { color: red; }
                </style>
              </head>
              <body>
                <h2>Liste des pointages</h2>
                <p>Période : ' . date('d/m/Y', strtotime($date_debut)) . ' - ' . date('d/m/Y', strtotime($date_fin)) . '</p>
                <p>Nombre de pointages : ' . count($exportData) . '</p>
                <table>';
        
        // En-têtes
        echo '<tr>';
        foreach (array_keys($exportData[0]) as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr>';
        
        // Données
        foreach ($exportData as $row) {
            echo '<tr>';
            foreach ($row as $key => $value) {
                $class = '';
                if ($key === 'Type') {
                    $class = strtolower($value) === 'Entree' ? 'entree' : 'sortie';
                }
                echo '<td class="' . $class . '">' . htmlspecialchars($value) . '</td>';
            }
            echo '</tr>';
        }
        
        echo '</table>
              <p style="margin-top:20px;color:#95a5a6;font-size:12px;">Exporté le ' . date('d/m/Y H:i:s') . '</p>
              </body></html>';
        exit();
    } else {
        // Export CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Cache-Control: max-age=0');
        
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF"); // BOM pour UTF-8
        
        if (!empty($exportData)) {
            fputcsv($output, array_keys($exportData[0]));
        }
        
        foreach ($exportData as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .export-stats {
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
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .stat-item .label {
            font-size: 12px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
        <nav class="navbar">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand"><?= SITE_NAME ?></a>
            <ul class="nav-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="pointage.php">⏱️ Pointage</a></li>
                <li><a href="employes.php">👥 Employés</a></li>
                <li><a href="generer_badges.php">🎫 Badges</a></li>
                <li><a href="export.php" class="active">📤 Export</a></li>
                <li><a href="admin_logout.php" style="color: #e74c3c;">🚪 Déconnexion</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>📤 Export des données</h1>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Filtres d'export</h2>
            <form method="GET" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Date début</label>
                        <input type="date" name="date_debut" value="<?= $date_debut ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Date fin</label>
                        <input type="date" name="date_fin" value="<?= $date_fin ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Employé</label>
                        <select name="employe_id">
                            <option value="">Tous les employés</option>
                            <?php foreach ($employes as $e): ?>
                                <option value="<?= $e['id'] ?>" <?= ($employe_id == $e['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($e['matricule']) ?> - <?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type">
                            <option value="">Tous les types</option>
                            <option value="entree" <?= ($type == 'entree') ? 'selected' : '' ?>>Entrée</option>
                            <option value="sortie" <?= ($type == 'sortie') ? 'selected' : '' ?>>Sortie</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">📊 Appliquer les filtres</button>
                </div>
            </form>
        </div>
        
        <?php if (!empty($pointages)): ?>
        <!-- Statistiques -->
        <div class="export-stats">
            <div class="stat-item">
                <div class="number"><?= count($pointages) ?></div>
                <div class="label">Total pointages</div>
            </div>
            <div class="stat-item" style="background: #d4edda;">
                <div class="number" style="color: #27ae60;">
                    <?= count(array_filter($pointages, function($p) { return $p['type'] === 'entree'; })) ?>
                </div>
                <div class="label">🟢 Entrées</div>
            </div>
            <div class="stat-item" style="background: #f8d7da;">
                <div class="number" style="color: #e74c3c;">
                    <?= count(array_filter($pointages, function($p) { return $p['type'] === 'sortie'; })) ?>
                </div>
                <div class="label">🔴 Sorties</div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Résultats (<?= count($pointages) ?> pointages)</h2>
                <div class="export-actions">
                    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 1, 'format' => 'csv'])) ?>" 
                       class="btn btn-success">📄 Exporter CSV</a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 1, 'format' => 'xls'])) ?>" 
                       class="btn btn-success">📊 Exporter Excel</a>
                </div>
            </div>
            
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Matricule</th>
                            <th>Employé</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Heure</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pointages as $index => $p): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><strong><?= htmlspecialchars($p['matricule']) ?></strong></td>
                            <td><?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?></td>
                            <td><?= getTypeBadge($p['type']) ?></td>
                            <td><?= date('d/m/Y', strtotime($p['date_heure'])) ?></td>
                            <td><?= date('H:i:s', strtotime($p['date_heure'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            📭 Aucun pointage trouvé pour les filtres sélectionnés.<br>
            <small>Essayez de modifier les dates ou les filtres.</small>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>