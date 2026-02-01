<?php
// mes_candidatures.php - Candidatures de l'étudiant
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login_ultimate.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

// Récupérer toutes les candidatures
$query = "SELECT a.*, 
                 o.title, 
                 o.city, 
                 o.domain, 
                 o.duration,
                 o.salary,
                 c.name as company_name, 
                 a.status, 
                 a.sent_at,
                 cv.cv_name,
                 cv.file_path as cv_path,
                 a.message
          FROM applications a 
          JOIN offers o ON a.offer_id = o.id 
          JOIN companies c ON o.company_id = c.id  
          LEFT JOIN cvs cv ON a.cv_id = cv.id
          WHERE a.student_id = ? 
          ORDER BY a.sent_at DESC";
          
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$applications = $stmt->fetchAll();

// Compter par statut
$stats = [
    'total' => count($applications),
    'pending' => 0,
    'accepted' => 0,
    'rejected' => 0
];

foreach ($applications as $app) {
    if (isset($stats[$app['status']])) {
        $stats[$app['status']]++;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Candidatures • InternEasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .header-section {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .application-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            border-left: 4px solid #0d6efd;
        }
        .application-card.accepted {
            border-left-color: #198754;
        }
        .application-card.rejected {
            border-left-color: #dc3545;
        }
        .application-card.pending {
            border-left-color: #ffc107;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header-section">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2">Mes Candidatures</h1>
                    <p class="mb-0">Suivez l'état de toutes vos candidatures</p>
                </div>
                <div>
                    <a href="dashboard_student.php" class="btn btn-light">
                        <i class="bi bi-arrow-left"></i> Retour au dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <h3 class="text-primary"><?= $stats['total'] ?></h3>
                    <p class="text-muted mb-0">Total</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h3 class="text-warning"><?= $stats['pending'] ?></h3>
                    <p class="text-muted mb-0">En attente</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h3 class="text-success"><?= $stats['accepted'] ?></h3>
                    <p class="text-muted mb-0">Acceptées</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h3 class="text-danger"><?= $stats['rejected'] ?></h3>
                    <p class="text-muted mb-0">Refusées</p>
                </div>
            </div>
        </div>

        <!-- Liste des candidatures -->
        <?php if (empty($applications)): ?>
            <div class="text-center py-5">
                <i class="bi bi-briefcase display-1 text-muted"></i>
                <h3 class="mt-3">Aucune candidature</h3>
                <p class="text-muted">Vous n'avez pas encore postulé à des offres</p>
                <a href="offers.php" class="btn btn-primary">
                    <i class="bi bi-search"></i> Chercher des offres
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-12">
                    <h4 class="mb-3">Historique des candidatures</h4>
                    
                    <?php foreach ($applications as $app): 
                        $statusClass = $app['status'];
                        $statusText = match($app['status']) {
                            'pending' => 'En attente',
                            'accepted' => 'Acceptée',
                            'rejected' => 'Refusée',
                            default => $app['status']
                        };
                        $statusColor = match($app['status']) {
                            'pending' => 'warning',
                            'accepted' => 'success',
                            'rejected' => 'danger',
                            default => 'secondary'
                        };
                    ?>
                    <div class="application-card <?= $statusClass ?>">
                        <div class="row">
                            <div class="col-md-8">
                                <h5><?= htmlspecialchars($app['title']) ?></h5>
                                <p class="mb-2">
                                    <strong>Entreprise:</strong> <?= htmlspecialchars($app['company_name']) ?><br>
                                    <strong>Lieu:</strong> <?= htmlspecialchars($app['city']) ?> • 
                                    <strong>Domaine:</strong> <?= htmlspecialchars($app['domain']) ?><br>
                                    <strong>Durée:</strong> <?= htmlspecialchars($app['duration']) ?> mois • 
                                    <strong>Salaire:</strong> <?= htmlspecialchars($app['salary']) ?> MAD
                                </p>
                                <p class="text-muted mb-2">
                                    <small>
                                        <i class="bi bi-clock"></i> Postulé le <?= date('d/m/Y à H:i', strtotime($app['sent_at'])) ?>
                                    </small>
                                </p>
                                <?php if (!empty($app['message'])): ?>
                                <div class="mt-2 p-2 bg-light rounded">
                                    <strong>Votre message:</strong><br>
                                    <?= nl2br(htmlspecialchars(substr($app['message'], 0, 200))) ?>...
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge bg-<?= $statusColor ?> status-badge">
                                    <?= $statusText ?>
                                </span>
                                
                                <div class="mt-3">
                                    <?php if (!empty($app['cv_path'])): ?>
                                    <a href="<?= htmlspecialchars($app['cv_path']) ?>" 
                                       target="_blank" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-file-earmark-pdf"></i> Voir CV envoyé
                                    </a>
                                    <?php endif; ?>
                                    
                                    <!-- CORRECTION ICI : Ajout de l'ID de l'offre dans le lien -->
                                    <a href="offers.php?id=<?= $app['offer_id'] ?>" 
                                       class="btn btn-outline-secondary btn-sm mt-1">
                                        <i class="bi bi-eye"></i> Voir l'offre
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>