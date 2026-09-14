-- Création de la base de données
CREATE DATABASE IF NOT EXISTS pointage_db;
USE pointage_db;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des employés
CREATE TABLE IF NOT EXISTS employes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricule VARCHAR(20) UNIQUE NOT NULL,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    telephone VARCHAR(20),
    poste VARCHAR(50),
    departement VARCHAR(50),
    date_embauche DATE,
    status ENUM('actif', 'inactif', 'en_attente') DEFAULT 'actif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des pointages
CREATE TABLE IF NOT EXISTS pointages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employe_id INT NOT NULL,
    type ENUM('entree', 'sortie') NOT NULL,
    date_heure DATETIME NOT NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employe_id) REFERENCES employes(id) ON DELETE CASCADE,
    INDEX idx_employe_date (employe_id, date_heure)
);

-- Insertion d'un utilisateur admin par défaut (mot de passe: admin123)
INSERT INTO users (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insertion d'employés de test
INSERT INTO employes (matricule, nom, prenom, email, telephone, poste, departement, date_embauche, status) VALUES
('EMP001', 'Rakoto', 'Jean', 'jean.rakoto@email.com', '0321234567', 'Développeur', 'IT', '2024-01-15', 'actif'),
('EMP002', 'Rabe', 'Marie', 'marie.rabe@email.com', '0339876543', 'Comptable', 'Finance', '2024-02-01', 'actif'),
('EMP003', 'Andrian', 'Pierre', 'pierre.andrian@email.com', '0345678901', 'Manager', 'RH', '2023-11-01', 'actif');