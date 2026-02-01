<?php
// admin_candidatures.php - Gestion des candidatures
session_start();

if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login_ultimate.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

// Récupérer toutes les candidatures avec informations
$applicationsQuery = "SELECT a.*, 
                             s.first_name, 
                             s.last_name, 
                             s.email as student_email,
                             o.title as offer_title,
                             c.name as company_name
                      FROM applications a 
                      JOIN students s ON a.student_id = s.id 
                      JOIN offers o ON a.offer_id = o.id
                      JOIN companies c ON o.company_id = c.id
                      ORDER BY a.sent_at DESC";
$applicationsStmt = $db->prepare($applicationsQuery);
$applicationsStmt->execute();
$applications = $applicationsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Candidatures • InternEasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
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
        .badge-status {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .badge-pending { background-color: #ffc107; color: #000; }
        .badge-accepted { background-color: #198754; color: white; }
        .badge-rejected { background-color: #dc3545; color: white; }
        .badge-reviewed { background-color: #0dcaf0; color: #000; }
        .badge-contacted { background-color: #6f42c1; color: white; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2"><i class="bi bi-file-text"></i> Gestion des Candidatures</h1>
                    <p class="mb-0"><?= count($applications) ?> candidatures soumises</p>
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
                <h5 class="mb-0">Liste des candidatures</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Étudiant</th>
                            <th>Offre</th>
                            <th>Entreprise</th>
                            <th>Date</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?= $app['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($app['student_email']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($app['offer_title']) ?></td>
                            <td><?= htmlspecialchars($app['company_name']) ?></td>
                            <td><?= date('d/m/Y', strtotime($app['sent_at'])) ?></td>
                            <td>
                                <?php
                                $statusClass = 'badge-secondary';
                                $statusText = $app['status'];
                                
                                switch($app['status']) {
                                    case 'pending':
                                        $statusClass = 'badge-pending';
                                        $statusText = 'En attente';
                                        break;
                                    case 'accepted':
                                        $statusClass = 'badge-accepted';
                                        $statusText = 'Acceptée';
                                        break;
                                    case 'rejected':
                                        $statusClass = 'badge-rejected';
                                        $statusText = 'Rejetée';
                                        break;
                                    case 'reviewed':
                                        $statusClass = 'badge-reviewed';
                                        $statusText = 'En cours';
                                        break;
                                    case 'contacted':
                                        $statusClass = 'badge-contacted';
                                        $statusText = 'Contactée';
                                        break;
                                }
                                ?>
                                <span class="badge-status <?= $statusClass ?>"><?= $statusText ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Total: <?= count($applications) ?> candidatures</small>
                    <div>
                        <select class="form-select form-select-sm w-auto d-inline">
                            <option>Tous les statuts</option>
                            <option>En attente</option>
                            <option>Acceptées</option>
                            <option>Rejetées</option>
                        </select>
                        <button class="btn btn-sm btn-danger ms-2">
                            <i class="bi bi-download"></i> Exporter
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>