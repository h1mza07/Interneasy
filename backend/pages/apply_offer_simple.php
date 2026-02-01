<?php
// apply_offer_simple.php - Version ultra simple pour tester
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();

echo "<!DOCTYPE html><html><head><title>Test Simple</title></head><body>";
echo "<h1>TEST apply_offer.php SIMPLIFIÉ</h1>";

// 1. Vérifier session
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    echo "<p style='color:red;'>ERREUR: Non connecté ou pas étudiant</p>";
    exit();
}

echo "<p>✅ Session OK - User ID: " . $_SESSION['user_id'] . "</p>";

// 2. Vérifier offre ID
$offer_id = $_GET['offer_id'] ?? 0;
echo "<p>Offre ID: " . $offer_id . "</p>";

if (!$offer_id) {
    echo "<p style='color:red;'>ERREUR: Pas d'ID offre</p>";
    exit();
}

// 3. Connexion DB
try {
    include_once __DIR__ . '/../config/config.php';
    include_once __DIR__ . '/../app/core/Database.php';
    
    $database = Database::getInstance();
    $db = $database->getConnection();
    echo "<p>✅ Connexion DB OK</p>";
    
    // 4. Vérifier offre
    $query = "SELECT title FROM offers WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$offer_id]);
    $offer = $stmt->fetch();
    
    if (!$offer) {
        echo "<p style='color:red;'>ERREUR: Offre non trouvée</p>";
        exit();
    }
    
    echo "<p>✅ Offre trouvée: " . htmlspecialchars($offer['title']) . "</p>";
    
    // 5. Afficher formulaire SIMPLE
    echo '<h2>Formulaire de test</h2>';
    echo '<form method="POST">';
    echo '<textarea name="message" rows="4" cols="50" placeholder="Message test"></textarea><br>';
    echo '<button type="submit">Envoyer test</button>';
    echo '</form>';
    
    // 6. Traiter formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo "<p>✅ Formulaire soumis!</p>";
        
        // Essayer d'insérer
        $insertQuery = "INSERT INTO applications (student_id, offer_id, message, sent_at, status) 
                        VALUES (?, ?, ?, NOW(), 'pending')";
        $insertStmt = $db->prepare($insertQuery);
        
        if ($insertStmt->execute([$_SESSION['user_id'], $offer_id, $_POST['message'] ?? 'Test'])) {
            echo "<p style='color:green;'>✅ Candidature insérée! ID: " . $db->lastInsertId() . "</p>";
        } else {
            echo "<p style='color:red;'>❌ Erreur insertion</p>";
            print_r($insertStmt->errorInfo());
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Erreur PDO: " . $e->getMessage() . "</p>";
    echo "<pre>Code: " . $e->getCode() . "</pre>";
}

echo "</body></html>";
?>