<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    die('Unauthorized');
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cv_id'])) {
    $cvId = $_POST['cv_id'];
    
    // Vérifier que le CV appartient à l'étudiant
    $checkQuery = "SELECT file_path FROM cvs WHERE id = ? AND student_id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$cvId, $_SESSION['user_id']]);
    $cv = $checkStmt->fetch();
    
    if ($cv) {
        // Supprimer le fichier physique
        $filePath = dirname(__DIR__, 2) . $cv['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Supprimer de la base
        $deleteQuery = "DELETE FROM cvs WHERE id = ?";
        $deleteStmt = $db->prepare($deleteQuery);
        
        if ($deleteStmt->execute([$cvId])) {
            echo 'success';
        } else {
            echo 'Erreur lors de la suppression';
        }
    } else {
        echo 'CV non trouvé ou non autorisé';
    }
}
?>