<?php
require_once 'Database.php';

class Pointage {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function enregistrer($employeId, $type, $latitude = null, $longitude = null) {
        $sql = "INSERT INTO pointages (employe_id, type, date_heure, latitude, longitude) 
                VALUES (:employe_id, :type, NOW(), :latitude, :longitude)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'employe_id' => $employeId,
            'type' => $type,
            'latitude' => $latitude,
            'longitude' => $longitude
        ]);
    }
    
    public function getPointages($filters = []) {
        $sql = "SELECT p.*, e.nom, e.prenom, e.matricule 
                FROM pointages p 
                JOIN employes e ON p.employe_id = e.id 
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['employe_id'])) {
            $sql .= " AND p.employe_id = :employe_id";
            $params['employe_id'] = $filters['employe_id'];
        }
        
        if (!empty($filters['date_debut'])) {
            $sql .= " AND DATE(p.date_heure) >= :date_debut";
            $params['date_debut'] = $filters['date_debut'];
        }
        
        if (!empty($filters['date_fin'])) {
            $sql .= " AND DATE(p.date_heure) <= :date_fin";
            $params['date_fin'] = $filters['date_fin'];
        }
        
        if (!empty($filters['type'])) {
            $sql .= " AND p.type = :type";
            $params['type'] = $filters['type'];
        }
        
        // CORRECTION : Ajouter ORDER BY et LIMIT correctement
        $sql .= " ORDER BY p.date_heure DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getPointageJour($employeId) {
        $sql = "SELECT * FROM pointages 
                WHERE employe_id = :employe_id 
                AND DATE(date_heure) = CURDATE() 
                ORDER BY date_heure DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['employe_id' => $employeId]);
        return $stmt->fetchAll();
    }
    
    public function getDernierPointage($employeId) {
        $sql = "SELECT * FROM pointages 
                WHERE employe_id = :employe_id 
                ORDER BY date_heure DESC 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['employe_id' => $employeId]);
        return $stmt->fetch();
    }
    
    public function getStatsParJour($date = null) {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        $sql = "SELECT 
                    p.type,
                    COUNT(*) as total,
                    e.nom,
                    e.prenom,
                    e.matricule
                FROM pointages p
                JOIN employes e ON p.employe_id = e.id
                WHERE DATE(p.date_heure) = :date
                GROUP BY p.employe_id, p.type
                ORDER BY e.nom, e.prenom";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['date' => $date]);
        return $stmt->fetchAll();
    }
}
?>