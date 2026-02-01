<?php
// admin_offres.php - Gestion des offres
session_start();

if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login_ultimate.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

// Récupérer toutes les offres avec info entreprise
$offersQuery = "SELECT o.*, c.name as company_name 
                FROM offers o 
                JOIN companies c ON o.company_id = c.id 
                ORDER BY o.created_at DESC";
$offersStmt = $db->prepare($offersQuery);
$offersStmt->execute();
$offers = $offersStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Offres • InternEasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Même style que précédemment */
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
                    <h1 class="mb-2"><i class="bi bi-briefcase"></i> Gestion des Offres</h1>
                    <p class="mb-0"><?= count($offers) ?> offres publiées</p>
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
                <h5 class="mb-0">Liste des offres</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre</th>
                            <th>Entreprise</th>
                            <th>Lieu</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($offers as $offer): ?>
                        <tr>
                            <td><?= $offer['id'] ?></td>
                            <td><?= htmlspecialchars($offer['title']) ?></td>
                            <td><?= htmlspecialchars($offer['company_name']) ?></td>
                            <td><?= htmlspecialchars($offer['city']) ?></td>
                            <td><?= htmlspecialchars($offer['contract_type']) ?></td>
                            <td>
                                <?php
                                $statusClass = match($offer['status']) {
                                    'active' => 'success',
                                    'pending' => 'warning',
                                    'closed' => 'secondary',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?= $statusClass ?>"><?= $offer['status'] ?></span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($offer['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>