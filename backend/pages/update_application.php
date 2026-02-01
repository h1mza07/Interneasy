<?php
session_start();

if (!isset($_SESSION['company_id'])) {
    die("Non autorisé");
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appId = $_POST['app_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    
    // Vérifier que cette candidature appartient à une offre de cette entreprise
    $checkQuery = "SELECT o.company_id 
                   FROM applications a 
                   JOIN offers o ON a.offer_id = o.id 
                   WHERE a.id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$appId]);
    $app = $checkStmt->fetch();
    
    if (!$app || $app['company_id'] != $_SESSION['company_id']) {
        die("Non autorisé à modifier cette candidature");
    }
    
    // Mettre à jour
    $updateQuery = "UPDATE applications SET status = ? WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    
    if ($updateStmt->execute([$status, $appId])) {
        echo "OK";
    } else {
        echo "Erreur";
    }
}
?>