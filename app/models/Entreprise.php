<?php
require_once __DIR__ . '/../core/Database.php';

class Entreprise {

    // Inscription entreprise
    public static function create($nom, $email, $motDePasse) {
        $pdo = Database::getInstance();
        $sql = "INSERT INTO entreprises (nom, email, motDePasse) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$nom, $email, $motDePasse]);
    }

    // Connexion : chercher par email
    public static function findByEmail($email) {
        $pdo = Database::getInstance();
        $sql = "SELECT * FROM entreprises WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Trouver par ID
    public static function findById($id) {
        $pdo = Database::getInstance();
        $sql = "SELECT * FROM entreprises WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // VALIDATION PAR L'ADMIN
    public static function valider($id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE entreprises SET estValide = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Suspendre une entreprise
    public static function suspendre($id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE entreprises SET estValide = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Récupérer les entreprises non validées
    public static function getNonValidees() {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM entreprises WHERE estValide = 0");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer toutes les entreprises
    public static function getAll() {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM entreprises ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer les entreprises validées
    public static function getValidees() {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM entreprises WHERE estValide = 1 ORDER BY nom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Vérifier si l'email existe déjà
    public static function emailExists($email) {
        $pdo = Database::getInstance();
        $sql = "SELECT COUNT(*) as count FROM entreprises WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    // Créer une offre
    public static function createOffre($entrepriseId, $titre, $description, $ville, $domaine, $latitude, $longitude) {
        $pdo = Database::getInstance();
        $sql = "INSERT INTO offres (titre, description, ville, domaine, latitude, longitude, idEntreprise) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$titre, $description, $ville, $domaine, $latitude, $longitude, $entrepriseId]);
    }

    // Récupérer les offres d'une entreprise
    public static function getOffres($entrepriseId) {
        $pdo = Database::getInstance();
        $sql = "SELECT o.*, 
                (SELECT COUNT(*) FROM candidatures c WHERE c.idOffre = o.id) as nombre_candidatures
                FROM offres o 
                WHERE o.idEntreprise = ? 
                ORDER BY o.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$entrepriseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer les candidatures pour les offres de l'entreprise
    public static function getCandidatures($entrepriseId) {
        $pdo = Database::getInstance();
        $sql = "SELECT c.*, o.titre as offre_titre, 
                CONCAT(e.prenom, ' ', e.nom) as etudiant_nom,
                e.email as etudiant_email
                FROM candidatures c
                JOIN offres o ON c.idOffre = o.id
                JOIN etudiants e ON c.idEtudiant = e.id
                WHERE o.idEntreprise = ?
                ORDER BY c.dateEnvoi DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$entrepriseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Mettre à jour le statut d'une candidature
    public static function updateCandidatureStatus($candidatureId, $statut) {
        $pdo = Database::getInstance();
        $sql = "UPDATE candidatures SET statut = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$statut, $candidatureId]);
    }
}
?>