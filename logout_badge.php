<?php
session_start();

// Détruire la session employé
unset($_SESSION['employe_id']);
unset($_SESSION['employe_nom']);
unset($_SESSION['employe_prenom']);
unset($_SESSION['employe_matricule']);

session_destroy();

// Rediriger vers le scan
header('Location: scan_badge.php');
exit();
?>