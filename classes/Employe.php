<?php
require_once 'Database.php';

class Employe {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll() {
        $stmt = $this->db->prepare("SELECT * FROM employes ORDER BY nom, prenom");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM employes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    public function getByMatricule($matricule) {
        $stmt = $this->db->prepare("SELECT * FROM employes WHERE matricule = :matricule");
        $stmt->execute(['matricule' => $matricule]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $sql = "INSERT INTO employes (matricule, nom, prenom, email, telephone, poste, departement, date_embauche, status) 
                VALUES (:matricule, :nom, :prenom, :email, :telephone, :poste, :departement, :date_embauche, :status)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    public function update($id, $data) {
        $sql = "UPDATE employes SET 
                matricule = :matricule, 
                nom = :nom, 
                prenom = :prenom,
                email = :email,
                telephone = :telephone,
                poste = :poste,
                departement = :departement,
                date_embauche = :date_embauche,
                status = :status
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $data['id'] = $id;
        return $stmt->execute($data);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM employes WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    public function getStats() {
        $stmt = $this->db->prepare("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'actif' THEN 1 ELSE 0 END) as actifs,
                SUM(CASE WHEN status = 'inactif' THEN 1 ELSE 0 END) as inactifs,
                SUM(CASE WHEN status = 'en_attente' THEN 1 ELSE 0 END) as en_attente
                FROM employes");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function search($keyword) {
        $sql = "SELECT * FROM employes 
                WHERE nom LIKE :keyword 
                OR prenom LIKE :keyword 
                OR matricule LIKE :keyword 
                OR email LIKE :keyword
                ORDER BY nom, prenom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['keyword' => '%' . $keyword . '%']);
        return $stmt->fetchAll();
    }
}
?>