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
        // Construire la requête avec uniquement les champs présents
        $sql = "INSERT INTO employes (matricule, nom, prenom, email, telephone, poste, departement, date_embauche, status, photo) 
                VALUES (:matricule, :nom, :prenom, :email, :telephone, :poste, :departement, :date_embauche, :status, :photo)";
        
        $stmt = $this->db->prepare($sql);
        
        // S'assurer que toutes les clés existent
        $params = [
            'matricule' => $data['matricule'] ?? null,
            'nom' => $data['nom'] ?? null,
            'prenom' => $data['prenom'] ?? null,
            'email' => $data['email'] ?? null,
            'telephone' => $data['telephone'] ?? null,
            'poste' => $data['poste'] ?? null,
            'departement' => $data['departement'] ?? null,
            'date_embauche' => $data['date_embauche'] ?? null,
            'status' => $data['status'] ?? 'actif',
            'photo' => $data['photo'] ?? null
        ];
        
        return $stmt->execute($params);
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
                status = :status";
        
        // Ajouter la photo si elle est fournie
        if (isset($data['photo'])) {
            $sql .= ", photo = :photo";
        }
        
        $sql .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        
        $params = [
            'id' => $id,
            'matricule' => $data['matricule'] ?? null,
            'nom' => $data['nom'] ?? null,
            'prenom' => $data['prenom'] ?? null,
            'email' => $data['email'] ?? null,
            'telephone' => $data['telephone'] ?? null,
            'poste' => $data['poste'] ?? null,
            'departement' => $data['departement'] ?? null,
            'date_embauche' => $data['date_embauche'] ?? null,
            'status' => $data['status'] ?? 'actif'
        ];
        
        if (isset($data['photo'])) {
            $params['photo'] = $data['photo'];
        }
        
        return $stmt->execute($params);
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