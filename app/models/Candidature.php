<?php
require_once __DIR__ . '/../core/Database.php';

class Candidature {

    // Créer une candidature
    public static function create($idEtudiant, $idOffre, $cvURL, $message) {
        $pdo = Database::getInstance();
        
        // Vérifier si déjà candidaté
        $checkSql = "SELECT id FROM candidatures WHERE idEtudiant = ? AND idOffre = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$idEtudiant, $idOffre]);
        
        if ($checkStmt->rowCount() > 0) {
            return false;
        }
        
        $sql = "INSERT INTO candidatures (idEtudiant, idOffre, cvURL, message, dateEnvoi, statut) 
                VALUES (?, ?, ?, ?, NOW(), 'en_attente')";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$idEtudiant, $idOffre, $cvURL, $message]);
    }

    // Trouver une candidature par ID
    public static function findById($id) {
        $pdo = Database::getInstance();
        $sql = "SELECT c.*, 
                CONCAT(e.prenom, ' ', e.nom) as etudiant_nom,
                e.email as etudiant_email,
                o.titre as offre_titre,
                ent.nom as entreprise_nom
                FROM candidatures c
                JOIN etudiants e ON c.idEtudiant = e.id
                JOIN offres o ON c.idOffre = o.id
                JOIN entreprises ent ON o.idEntreprise = ent.id
                WHERE c.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Mettre à jour le statut
    public static function updateStatut($id, $statut) {
        $pdo = Database::getInstance();
        $sql = "UPDATE candidatures SET statut = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$statut, $id]);
    }

    // Récupérer toutes les candidatures
    public static function getAll() {
        $pdo = Database::getInstance();
        $sql = "SELECT c.*, 
                CONCAT(e.prenom, ' ', e.nom) as etudiant_nom,
                o.titre as offre_titre,
                ent.nom as entreprise_nom
                FROM candidatures c
                JOIN etudiants e ON c.idEtudiant = e.id
                JOIN offres o ON c.idOffre = o.id
                JOIN entreprises ent ON o.idEntreprise = ent.id
                ORDER BY c.dateEnvoi DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Compter le nombre de candidatures
    public static function count() {
        $pdo = Database::getInstance();
        $sql = "SELECT COUNT(*) as total FROM candidatures";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Compter par statut
    public static function countByStatut($statut) {
        $pdo = Database::getInstance();
        $sql = "SELECT COUNT(*) as total FROM candidatures WHERE statut = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$statut]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Vérifier si un étudiant a déjà postulé à une offre
    public static function hasApplied($idEtudiant, $idOffre) {
        $pdo = Database::getInstance();
        $sql = "SELECT id FROM candidatures WHERE idEtudiant = ? AND idOffre = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idEtudiant, $idOffre]);
        return $stmt->rowCount() > 0;
    }
}
?>