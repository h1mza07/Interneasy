<?php
// test_company.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'backend/app/core/Database.php';
require_once 'backend/app/models/Company.php';

echo "<h2>🧪 Test Inscription Entreprise</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company = new Company();
    
    $data = [
        'name' => $_POST['name'],
        'email' => $_POST['email'],
        'password' => $_POST['password'],
        'activity_sector' => $_POST['activity_sector'],
        'company_size' => $_POST['company_size']
    ];
    
    $result = $company->register($data);
    
    echo "<h3>Résultat :</h3>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if ($result['success']) {
        echo "<h3 style='color:green'>✅ INSCRIPTION ENTREPRISE RÉUSSIE !</h3>";
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Entreprise</title>
</head>
<body style="padding:20px;">
    <form method="POST">
        Nom entreprise: <input type="text" name="name" value="Entreprise Test" required><br><br>
        Email: <input type="email" name="email" value="entreprise<?php echo time(); ?>@test.com" required><br><br>
        Mot de passe: <input type="password" name="password" value="Test1234" required><br><br>
        Secteur: <input type="text" name="activity_sector" value="Technologie"><br><br>
        Taille: <input type="text" name="company_size" value="10-50 employés"><br><br>
        <button type="submit">TESTER INSCRIPTION ENTREPRISE</button>
    </form>
</body>
</html>