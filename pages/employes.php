<?php
require_once '../config/config.php';
require_once '../includes/session.php';
requireLogin();
require_once '../includes/functions.php';  // ← AJOUT OBLIGATOIRE
require_once '../classes/Database.php';
require_once '../classes/Employe.php';

$employe = new Employe();
$employes = $employe->getAll();

$message = '';
$messageType = '';

// Suppression
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($employe->delete($id)) {
        $message = "Employé supprimé avec succès !";
        $messageType = 'success';
        $employes = $employe->getAll();
    } else {
        $message = "Erreur lors de la suppression !";
        $messageType = 'danger';
    }
}

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $data = [
            'matricule' => $_POST['matricule'],
            'nom' => $_POST['nom'],
            'prenom' => $_POST['prenom'],
            'email' => $_POST['email'] ?? null,
            'telephone' => $_POST['telephone'] ?? null,
            'poste' => $_POST['poste'] ?? null,
            'departement' => $_POST['departement'] ?? null,
            'date_embauche' => $_POST['date_embauche'] ?? null,
            'status' => $_POST['status'] ?? 'actif'
        ];
        
        // Vérification du matricule unique
        $existing = $employe->getByMatricule($data['matricule']);
        if ($existing) {
            $message = "Ce matricule existe déjà !";
            $messageType = 'danger';
        } else {
            if ($employe->create($data)) {
                $message = "Employé ajouté avec succès !";
                $messageType = 'success';
                $employes = $employe->getAll();
            } else {
                $message = "Erreur lors de l'ajout !";
                $messageType = 'danger';
            }
        }
    }
    
    // Modification
    if ($_POST['action'] === 'edit') {
        $id = intval($_POST['id']);
        $data = [
            'matricule' => $_POST['matricule'],
            'nom' => $_POST['nom'],
            'prenom' => $_POST['prenom'],
            'email' => $_POST['email'] ?? null,
            'telephone' => $_POST['telephone'] ?? null,
            'poste' => $_POST['poste'] ?? null,
            'departement' => $_POST['departement'] ?? null,
            'date_embauche' => $_POST['date_embauche'] ?? null,
            'status' => $_POST['status'] ?? 'actif'
        ];
        
        if ($employe->update($id, $data)) {
            $message = "Employé modifié avec succès !";
            $messageType = 'success';
            $employes = $employe->getAll();
        } else {
            $message = "Erreur lors de la modification !";
            $messageType = 'danger';
        }
    }
}

// Recherche
$searchResults = null;
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchResults = $employe->search($_GET['search']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employés - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand"><?= SITE_NAME ?></a>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="pointage.php">Pointage</a></li>
                <li><a href="employes.php" class="active">Employés</a></li>
                <li><a href="export.php">Export</a></li>
                <li><a href="../scan_badge.php">Scan</a></li>
                <li><a href="../index.php?logout=1">Déconnexion</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>Gestion des employés</h1>
            <button onclick="toggleForm()" class="btn btn-success">+ Ajouter un employé</button>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>
        
        <!-- Formulaire de recherche -->
        <div class="search-form">
            <form method="GET" action="">
                <div class="form-group" style="display: flex; gap: 10px;">
                    <input type="text" name="search" placeholder="Rechercher un employé..." value="<?= $_GET['search'] ?? '' ?>" style="flex: 1;">
                    <button type="submit" class="btn">Rechercher</button>
                    <?php if (isset($_GET['search'])): ?>
                        <a href="employes.php" class="btn btn-secondary">Effacer</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Formulaire d'ajout -->
        <div id="addForm" style="display: none; margin-top: 20px;">
            <div class="card">
                <h2>Nouvel employé</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Matricule *</label>
                            <input type="text" name="matricule" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Nom *</label>
                            <input type="text" name="nom" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Prénom *</label>
                            <input type="text" name="prenom" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email">
                        </div>
                        
                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="text" name="telephone">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Poste</label>
                            <input type="text" name="poste">
                        </div>
                        
                        <div class="form-group">
                            <label>Département</label>
                            <input type="text" name="departement">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date d'embauche</label>
                            <input type="date" name="date_embauche">
                        </div>
                        
                        <div class="form-group">
                            <label>Statut</label>
                            <select name="status">
                                <option value="actif">Actif</option>
                                <option value="inactif">Inactif</option>
                                <option value="en_attente">En attente</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <button type="button" onclick="toggleForm()" class="btn btn-secondary">Fermer</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Liste des employés -->
        <div class="employes-list">
            <h2>Liste des employés</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Matricule</th>
                        <th>Nom & Prénom</th>
                        <th>Email</th>
                        <th>Poste</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $displayList = $searchResults !== null ? $searchResults : $employes;
                    if (count($displayList) > 0): 
                    ?>
                        <?php foreach ($displayList as $index => $e): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><strong><?= htmlspecialchars($e['matricule']) ?></strong></td>
                            <td><?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?></td>
                            <td><?= htmlspecialchars($e['email'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($e['poste'] ?? '-') ?></td>
                            <td><?= getStatusBadge($e['status']) ?></td>
                            <td>
                                <a href="employes.php?delete=<?= $e['id'] ?>" 
                                   onclick="return confirm('Supprimer cet employé ?')"
                                   class="btn-delete">🗑️</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">Aucun employé trouvé</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function toggleForm() {
            var form = document.getElementById('addForm');
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
                form.scrollIntoView({ behavior: 'smooth' });
            } else {
                form.style.display = 'none';
            }
        }
    </script>
</body>
</html>