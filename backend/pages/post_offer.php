<?php

// backend/pages/post_offer.php - VERSION FINALE CORRIGÉE AVEC STATUT
session_start();

// Vérifier si entreprise connectée
if (!isset($_SESSION['company_id']) || $_SESSION['user_type'] !== 'company') {
    header('Location: /interneasy/frontend/login.html');
    exit();
}

// Définir le chemin de base
define('BASE_PATH', dirname(__DIR__, 2));

// Inclure avec chemins absolus
require_once BASE_PATH . '/backend/config/config.php';
require_once BASE_PATH . '/backend/app/core/Database.php';

// Utiliser getInstance() pour Singleton
$database = Database::getInstance();
$db = $database->getConnection();

$error = '';
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CORRECTION : Utiliser "name" au lieu de "company_name"
        $checkCompany = $db->prepare("SELECT id, name FROM companies WHERE id = ?");
        $checkCompany->execute([$_SESSION['company_id']]);
        
        $company = $checkCompany->fetch();
        
        if (!$company) {
            $error = "Erreur: Entreprise non trouvée (ID: " . $_SESSION['company_id'] . ").";
        } else {
            // Préparer les données
            $title = htmlspecialchars($_POST['title'] ?? '');
            $description = htmlspecialchars($_POST['description'] ?? '');
            $city = htmlspecialchars($_POST['city'] ?? '');
            $domain = htmlspecialchars($_POST['domain'] ?? '');
            $duration = intval($_POST['duration'] ?? 6);
            $salary = !empty($_POST['salary']) ? htmlspecialchars($_POST['salary']) . ' MAD' : 'Non spécifiée';
            $contract_type = htmlspecialchars($_POST['contract_type'] ?? 'Stage');
            $start_date = $_POST['start_date'] ?? '';
            $positions = intval($_POST['positions'] ?? 1);
            $deadline = $_POST['deadline'] ?? '';
            $education_level = htmlspecialchars($_POST['education_level'] ?? '');
            $requirements = htmlspecialchars($_POST['requirements'] ?? '');
            $status = htmlspecialchars($_POST['status'] ?? 'active'); // NOUVEAU : Statut
            
            // Récupérer les avantages
            $benefits = '';
            if (!empty($_POST['benefits']) && is_array($_POST['benefits'])) {
                $benefits = implode(', ', array_map('htmlspecialchars', $_POST['benefits']));
            }
            
            // Validation
            if (strlen($description) < 10) {
                $error = "La description doit contenir au moins 10 caractères.";
            } elseif (empty($title) || empty($city) || empty($domain)) {
                $error = "Veuillez remplir tous les champs obligatoires.";
            } else {
                // Insertion dans la base de données
                $query = "INSERT INTO offers (
                    company_id, 
                    title, 
                    description, 
                    city, 
                    domain, 
                    duration, 
                    salary,
                    contract_type,
                    start_date,
                    positions,
                    deadline,
                    education_level,
                    requirements,
                    benefits,
                    status  
                ) VALUES (
                    :company_id, 
                    :title, 
                    :description, 
                    :city, 
                    :domain, 
                    :duration, 
                    :salary,
                    :contract_type,
                    :start_date,
                    :positions,
                    :deadline,
                    :education_level,
                    :requirements,
                    :benefits,
                    :status  
                )";
                
                $stmt = $db->prepare($query);
                
                $stmt->bindParam(':company_id', $_SESSION['company_id']);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':city', $city);
                $stmt->bindParam(':domain', $domain);
                $stmt->bindParam(':duration', $duration);
                $stmt->bindParam(':salary', $salary);
                $stmt->bindParam(':contract_type', $contract_type);
                $stmt->bindParam(':start_date', $start_date);
                $stmt->bindParam(':positions', $positions);
                $stmt->bindParam(':deadline', $deadline);
                $stmt->bindParam(':education_level', $education_level);
                $stmt->bindParam(':requirements', $requirements);
                $stmt->bindParam(':benefits', $benefits);
                $stmt->bindParam(':status', $status); // NOUVEAU : Lier le statut
                
                if ($stmt->execute()) {
                    $success = '✅ Offre publiée avec succès ! Elle est maintenant visible par les étudiants.';
                } else {
                    $error = '❌ Erreur lors de la publication de l\'offre.';
                }
            }
        }
    } catch (Exception $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}

// Lire le template HTML
$html = file_get_contents(BASE_PATH . '/frontend/post_offer.html');
$html = str_replace(
    'action="../backend/pages/post_offer.php"',
    'action="post_offer.php"',  // Chemin relatif correct
    $html
);

// AJOUT DU CHAMP STATUT DANS LE FORMULAIRE HTML
$html = preg_replace_callback(
    '/(<div class="form-group">\s*<label class="form-label">Nombre de postes \*<\/label>\s*<input type="number" name="positions" class="form-control" value="1" min="1" required>\s*<\/div>)(\s*)(<div class="form-group">)/s',
    function($matches) {
        return $matches[1] . "\n" . 
            '<!-- NOUVEAU : Champ Statut -->' . "\n" .
            '<div class="form-group">' . "\n" .
            '    <label class="form-label">Statut de l\'offre *</label>' . "\n" .
            '    <select name="status" class="form-select" required>' . "\n" .
            '        <option value="active" selected>Active (visible par les étudiants)</option>' . "\n" .
            '        <option value="inactive">Inactive (masquée)</option>' . "\n" .
            '        <option value="draft">Brouillon</option>' . "\n" .
            '        <option value="closed">Clôturée</option>' . "\n" .
            '    </select>' . "\n" .
            '    <small class="text-muted">"Active" pour que l\'offre soit visible sur la carte et par les étudiants</small>' . "\n" .
            '</div>' . "\n" .
            $matches[2] . $matches[3];
    },
    $html
);

// Ajouter les messages au template
if ($error) {
    $html = str_replace(
        '<div id="formMessages"></div>',
        '<div id="formMessages">' .
        '<div class="alert alert-danger alert-dismissible fade show" role="alert">' .
        $error .
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' .
        '</div></div>',
        $html
    );
}

if ($success) {
    $html = str_replace(
        '<div id="formMessages"></div>',
        '<div id="formMessages">' .
        '<div class="alert alert-success alert-dismissible fade show" role="alert">' .
        $success .
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' .
        '</div></div>',
        $html
    );
}

// CORRECTION DES CHEMINS CSS/JS
$html = str_replace('href="css/', 'href="/interneasy/frontend/css/', $html);
$html = str_replace('src="js/', 'src="/interneasy/frontend/js/', $html);
$html = str_replace('href="dashboard_company.html"', 'href="/interneasy/backend/pages/dashboard_company.php"', $html);

echo $html;
?>