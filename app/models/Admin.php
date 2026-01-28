<?php
require_once __DIR__ . '/../core/Database.php';

class Admin {

    public static function findByEmail($email) {
        $pdo = Database::getInstance();
        $sql = "SELECT * FROM administrateurs WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function findById($id) {
        $pdo = Database::getInstance();
        $sql = "SELECT * FROM administrateurs WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Récupérer les statistiques
    public static function getStats() {
        $pdo = Database::getInstance();
        
        $stats = [];
        
        // Nombre d'étudiants
        $sql = "SELECT COUNT(*) as total FROM etudiants";
        $stmt = $pdo->query($sql);
        $stats['etudiants'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Nombre d'entreprises
        $sql = "SELECT COUNT(*) as total FROM entreprises";
        $stmt = $pdo->query($sql);
        $stats['entreprises'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Entreprises validées
        $sql = "SELECT COUNT(*) as total FROM entreprises WHERE estValide = 1";
        $stmt = $pdo->query($sql);
        $stats['entreprises_validees'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Entreprises en attente
        $sql = "SELECT COUNT(*) as total FROM entreprises WHERE estValide = 0";
        $stmt = $pdo->query($sql);
        $stats['entreprises_attente'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Nombre d'offres
        $sql = "SELECT COUNT(*) as total FROM offres";
        $stmt = $pdo->query($sql);
        $stats['offres'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Nombre de candidatures
        $sql = "SELECT COUNT(*) as total FROM candidatures";
        $stmt = $pdo->query($sql);
        $stats['candidatures'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Candidatures par statut
        $sql = "SELECT statut, COUNT(*) as count FROM candidatures GROUP BY statut";
        $stmt = $pdo->query($sql);
        $stats['candidatures_statut'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Offres par domaine
        $sql = "SELECT domaine, COUNT(*) as count FROM offres GROUP BY domaine";
        $stmt = $pdo->query($sql);
        $stats['offres_domaine'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }

    // Récupérer les 10 dernières candidatures
    public static function getRecentCandidatures() {
        $pdo = Database::getInstance();
        $sql = "SELECT c.*, 
                CONCAT(e.prenom, ' ', e.nom) as etudiant_nom,
                o.titre as offre_titre,
                ent.nom as entreprise_nom
                FROM candidatures c
                JOIN etudiants e ON c.idEtudiant = e.id
                JOIN offres o ON c.idOffre = o.id
                JOIN entreprises ent ON o.idEntreprise = ent.id
                ORDER BY c.dateEnvoi DESC
                LIMIT 10";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer les 10 dernières offres
    public static function getRecentOffres() {
        $pdo = Database::getInstance();
        $sql = "SELECT o.*, e.nom as entreprise_nom
                FROM offres o
                JOIN entreprises e ON o.idEntreprise = e.id
                ORDER BY o.id DESC
                LIMIT 10";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>