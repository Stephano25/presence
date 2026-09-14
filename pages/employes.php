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

// Suppression
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Récupérer l'employé pour supprimer sa photo
    $empToDelete = $employe->getById($id);
    if ($empToDelete && !empty($empToDelete['photo'])) {
        $photoPath = '../' . $empToDelete['photo'];
        if (file_exists($photoPath)) {
            unlink($photoPath);
        }
    }
    
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
            'status' => $_POST['status'] ?? 'actif',
            'photo' => null
        ];
        
        // Upload de la photo
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/photos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($extension, $allowed)) {
                $filename = $data['matricule'] . '.' . $extension;
                $photoPath = 'uploads/photos/' . $filename;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], '../' . $photoPath)) {
                    $data['photo'] = $photoPath;
                }
            }
        }
        
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
        
        // Upload de la photo si nouvelle
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/photos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($extension, $allowed)) {
                $filename = $data['matricule'] . '.' . $extension;
                $photoPath = 'uploads/photos/' . $filename;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], '../' . $photoPath)) {
                    $data['photo'] = $photoPath;
                }
            }
        }
        
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
    <style>
        .photo-preview-mini {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #3498db;
        }
        .photo-placeholder-mini {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            margin: 0 auto;
        }
        .photo-preview-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3498db;
            margin: 10px 0;
            display: block;
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
                <li><a href="employes.php" class="active">👥 Employés</a></li>
                <li><a href="generer_badges.php">🎫 Badges</a></li>
                <li><a href="export.php">📤 Export</a></li>
                <li><a href="admin_logout.php" style="color: #e74c3c;">🚪 Déconnexion</a></li>
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
        
        <div id="addForm" style="display: none; margin-top: 20px;">
            <div class="card">
                <h2>Nouvel employé</h2>
                <form method="POST" enctype="multipart/form-data">
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
                    
                    <div class="form-group">
                        <label>📸 Photo de l'employé</label>
                        <input type="file" name="photo" accept="image/*" onchange="previewImage(event)">
                        <small style="color: #95a5a6;">Formats acceptés : JPG, PNG, GIF, WEBP (max 5 Mo)</small>
                        <div id="photoPreviewContainer" style="margin-top: 10px;"></div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <button type="button" onclick="toggleForm()" class="btn btn-secondary">Fermer</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="employes-list">
            <h2>Liste des employés (<?= count($employes) ?>)</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Photo</th>
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
                            <td>
                                <?php if (!empty($e['photo']) && file_exists('../' . $e['photo'])): ?>
                                    <img src="../<?= htmlspecialchars($e['photo']) ?>" 
                                         alt="Photo" 
                                         class="photo-preview-mini">
                                <?php else: ?>
                                    <div class="photo-placeholder-mini">
                                        <?= strtoupper(substr($e['prenom'], 0, 1) . substr($e['nom'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
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
                            <td colspan="8" style="text-align: center;">Aucun employé trouvé</td>
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
        
        function previewImage(event) {
            const file = event.target.files[0];
            const container = document.getElementById('photoPreviewContainer');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    container.innerHTML = '<img src="' + e.target.result + '" class="photo-preview-large" alt="Aperçu">';
                };
                reader.readAsDataURL(file);
            } else {
                container.innerHTML = '';
            }
        }
    </script>
</body>
</html>