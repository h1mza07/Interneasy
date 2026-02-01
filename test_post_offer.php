<?php
// test_post_offer.php à la racine de Interneasy
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Post Offer</h1>";

// Vérifier session
echo "<h2>Session :</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Vérifier POST
echo "<h2>Données POST :</h2>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

// Test connexion DB
echo "<h2>Test Connexion DB :</h2>";
try {
    include_once 'backend/config/config.php';
    include_once 'backend/app/core/Database.php';
    
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    echo "✅ Connexion DB réussie<br>";
    
    // Tester l'insertion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['title'])) {
        $testData = [
            'company_id' => $_SESSION['company_id'] ?? 1,
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? 'Test',
            'city' => $_POST['city'] ?? 'Test',
            'domain' => $_POST['domain'] ?? 'Test'
        ];
        
        $sql = "INSERT INTO offers (company_id, title, description, city, domain, duration) 
                VALUES (:company_id, :title, :description, :city, :domain, 6)";
        
        $stmt = $db->prepare($sql);
        
        if ($stmt->execute($testData)) {
            echo "✅ Insertion TEST réussie !<br>";
        } else {
            echo "❌ Erreur insertion<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erreur DB: " . $e->getMessage();
}

// Formulaire de test
echo '<h2>Formulaire test :</h2>';
echo '<form method="POST">
        <input type="text" name="title" placeholder="Titre" required><br>
        <textarea name="description" placeholder="Description" required></textarea><br>
        <input type="text" name="city" placeholder="Ville" required><br>
        <input type="text" name="domain" placeholder="Domaine" required><br>
        <button type="submit">Tester</button>
      </form>';
?>