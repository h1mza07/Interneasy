<?php
require_once __DIR__ . '/../core/Database.php';

class Etudiant {

    // Créer un étudiant (inscription)
    public static function create($prenom, $nom, $email, $motDePasse) {
        $pdo = Database::getInstance();
        $sql = "INSERT INTO etudiants (prenom, nom, email, motDePasse)
                VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$prenom, $nom, $email, $motDePasse]);
    }

    // Trouver un étudiant par email (connexion)
    public static function findByEmail($email) {
        $pdo = Database::getInstance();
        $sql = "SELECT * FROM etudiants WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Trouver un étudiant par id
    public static function findById($id) {
        $pdo = Database::getInstance();
        $sql = "SELECT * FROM etudiants WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Vérifier si l'email existe déjà
    public static function emailExists($email) {
        $pdo = Database::getInstance();
        $sql = "SELECT COUNT(*) as count FROM etudiants WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    // Postuler à une offre
    public static function postuler($etudiantId, $offreId, $cvUrl, $message) {
        $pdo = Database::getInstance();
        
        // Vérifier si déjà postulé
        $checkSql = "SELECT id FROM candidatures WHERE idEtudiant = ? AND idOffre = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$etudiantId, $offreId]);
        
        if ($checkStmt->rowCount() > 0) {
            return false; // Déjà postulé
        }
        
        $sql = "INSERT INTO candidatures (idEtudiant, idOffre, cvURL, message, dateEnvoi, statut) 
                VALUES (?, ?, ?, ?, NOW(), 'en_attente')";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$etudiantId, $offreId, $cvUrl, $message]);
    }

    // Récupérer les candidatures d'un étudiant
    public static function getCandidatures($etudiantId) {
        $pdo = Database::getInstance();
        $sql = "SELECT c.*, o.titre, o.ville, o.domaine, 
                e.nom as entreprise_nom, e.email as entreprise_email
                FROM candidatures c
                JOIN offres o ON c.idOffre = o.id
                JOIN entreprises e ON o.idEntreprise = e.id
                WHERE c.idEtudiant = ?
                ORDER BY c.dateEnvoi DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$etudiantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Mettre à jour le profil étudiant
    public static function updateProfile($id, $prenom, $nom, $email, $telephone) {
        $pdo = Database::getInstance();
        $sql = "UPDATE etudiants SET prenom = ?, nom = ?, email = ?, telephone = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$prenom, $nom, $email, $telephone, $id]);
    }

    // Compter le nombre d'étudiants
    public static function count() {
        $pdo = Database::getInstance();
        $sql = "SELECT COUNT(*) as total FROM etudiants";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
}
?>