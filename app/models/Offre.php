<?php
require_once __DIR__ . '/../core/Database.php';

class Offre {

    // Créer une offre
    public static function create($titre, $description, $ville, $domaine, $latitude, $longitude, $idEntreprise) {
        $pdo = Database::getInstance();
        $sql = "INSERT INTO offres (titre, description, ville, domaine, latitude, longitude, idEntreprise) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$titre, $description, $ville, $domaine, $latitude, $longitude, $idEntreprise]);
    }

    // Trouver une offre par ID
    public static function findById($id) {
        $pdo = Database::getInstance();
        $sql = "SELECT o.*, e.nom as entreprise_nom, e.email as entreprise_email
                FROM offres o
                JOIN entreprises e ON o.idEntreprise = e.id
                WHERE o.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Récupérer toutes les offres
    public static function getAll($filters = []) {
        $pdo = Database::getInstance();
        
        $sql = "SELECT o.*, e.nom as entreprise_nom, e.email as entreprise_email
                FROM offres o
                JOIN entreprises e ON o.idEntreprise = e.id
                WHERE e.estValide = 1";
        
        $params = [];
        
        if (!empty($filters['ville'])) {
            $sql .= " AND o.ville LIKE ?";
            $params[] = '%' . $filters['ville'] . '%';
        }
        
        if (!empty($filters['domaine'])) {
            $sql .= " AND o.domaine LIKE ?";
            $params[] = '%' . $filters['domaine'] . '%';
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (o.titre LIKE ? OR o.description LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        
        $sql .= " ORDER BY o.id DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Mettre à jour une offre
    public static function update($id, $titre, $description, $ville, $domaine, $latitude, $longitude) {
        $pdo = Database::getInstance();
        $sql = "UPDATE offres 
                SET titre = ?, description = ?, ville = ?, domaine = ?, latitude = ?, longitude = ?
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$titre, $description, $ville, $domaine, $latitude, $longitude, $id]);
    }

    // Supprimer une offre
    public static function delete($id) {
        $pdo = Database::getInstance();
        $sql = "DELETE FROM offres WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    // Compter le nombre d'offres
    public static function count() {
        $pdo = Database::getInstance();
        $sql = "SELECT COUNT(*) as total FROM offres";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Récupérer les offres par domaine
    public static function getByDomaine($domaine) {
        $pdo = Database::getInstance();
        $sql = "SELECT o.*, e.nom as entreprise_nom
                FROM offres o
                JOIN entreprises e ON o.idEntreprise = e.id
                WHERE o.domaine = ? AND e.estValide = 1
                ORDER BY o.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$domaine]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer les domaines disponibles
    public static function getDomaines() {
        $pdo = Database::getInstance();
        $sql = "SELECT DISTINCT domaine FROM offres ORDER BY domaine";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>