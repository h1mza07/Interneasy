<?php
// admin_entreprises.php - Gestion des entreprises
session_start();

if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login_ultimate.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

// Récupérer toutes les entreprises
$companiesQuery = "SELECT * FROM companies ORDER BY created_at DESC";
$companiesStmt = $db->prepare($companiesQuery);
$companiesStmt->execute();
$companies = $companiesStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Entreprises • InternEasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Même style que admin_etudiants.php */
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.2);
        }
        .table-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2"><i class="bi bi-building"></i> Gestion des Entreprises</h1>
                    <p class="mb-0"><?= count($companies) ?> entreprises inscrites</p>
                </div>
                <div>
                    <a href="dashboard_admin.php" class="btn btn-light">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
        
        <div class="table-card">
            <div class="p-3 border-bottom">
                <h5 class="mb-0">Liste des entreprises</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Ville</th>
                            <th>Secteur</th>
                            <th>Date d'inscription</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($companies as $company): ?>
                        <tr>
                            <td><?= $company['id'] ?></td>
                            <td><?= htmlspecialchars($company['name']) ?></td>
                            <td><?= htmlspecialchars($company['email']) ?></td>
                            <td><?= htmlspecialchars($company['city']) ?></td>
                            <td><?= htmlspecialchars($company['activity_sector'] ?? 'N/A') ?></td>
                            <td><?= date('d/m/Y', strtotime($company['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>