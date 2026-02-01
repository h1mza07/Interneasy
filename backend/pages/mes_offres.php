<?php
// mes_offres.php - Afficher les offres de l'entreprise
session_start();

if (!isset($_SESSION['company_id']) || $_SESSION['user_type'] !== 'company') {
    header('Location: login_ultimate.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

// Récupérer les offres de l'entreprise
$query = "SELECT o.*, 
                 COUNT(a.id) as candidature_count,
                 SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                 SUM(CASE WHEN a.status = 'accepted' THEN 1 ELSE 0 END) as accepted_count
          FROM offers o 
          LEFT JOIN applications a ON o.id = a.offer_id 
          WHERE o.company_id = ? 
          GROUP BY o.id 
          ORDER BY o.created_at DESC";
          
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['company_id']]);
$offers = $stmt->fetchAll();

// Compter les offres par statut
$stats = [
    'total' => count($offers),
    'active' => 0,
    'closed' => 0,
    'draft' => 0
];

foreach ($offers as $offer) {
    if ($offer['status'] === 'active') $stats['active']++;
    elseif ($offer['status'] === 'closed') $stats['closed']++;
    elseif ($offer['status'] === 'draft') $stats['draft']++;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Offres • InternEasy</title>
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
        .offer-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            border-left: 4px solid #0d6efd;
        }
        .offer-card.active {
            border-left-color: #198754;
        }
        .offer-card.closed {
            border-left-color: #6c757d;
        }
        .offer-card.draft {
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
                    <h1 class="mb-2">Mes Offres de Stage</h1>
                    <p class="mb-0">Gérez toutes vos offres publiées</p>
                </div>
                <div>
                    <a href="dashboard_company.php" class="btn btn-light">
                        <i class="bi bi-arrow-left"></i> Retour au dashboard
                    </a>
                    <a href="post_offer.php" class="btn btn-primary ms-2">
                        <i class="bi bi-plus-circle"></i> Publier une nouvelle offre
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <h3 class="text-primary"><?= $stats['total'] ?></h3>
                    <p class="text-muted mb-0">Total offres</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h3 class="text-success"><?= $stats['active'] ?></h3>
                    <p class="text-muted mb-0">Actives</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h3 class="text-warning"><?= $stats['draft'] ?></h3>
                    <p class="text-muted mb-0">Brouillons</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h3 class="text-secondary"><?= $stats['closed'] ?></h3>
                    <p class="text-muted mb-0">Clôturées</p>
                </div>
            </div>
        </div>

        <!-- Liste des offres -->
        <?php if (empty($offers)): ?>
            <div class="text-center py-5">
                <i class="bi bi-briefcase display-1 text-muted"></i>
                <h3 class="mt-3">Aucune offre publiée</h3>
                <p class="text-muted">Vous n'avez pas encore publié d'offres de stage</p>
                <a href="post_offer.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Publier votre première offre
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4>Liste de vos offres</h4>
                        <a href="post_offer.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle"></i> Nouvelle offre
                        </a>
                    </div>
                    
                    <?php foreach ($offers as $offer): 
                        $statusClass = $offer['status'];
                        $statusText = match($offer['status']) {
                            'active' => 'Active',
                            'closed' => 'Clôturée',
                            'draft' => 'Brouillon',
                            default => $offer['status']
                        };
                        $statusColor = match($offer['status']) {
                            'active' => 'success',
                            'closed' => 'secondary',
                            'draft' => 'warning',
                            default => 'secondary'
                        };
                    ?>
                    <div class="offer-card <?= $statusClass ?>">
                        <div class="row">
                            <div class="col-md-8">
                                <h5><?= htmlspecialchars($offer['title']) ?></h5>
                                <p class="mb-2">
                                    <strong>Lieu:</strong> <?= htmlspecialchars($offer['city']) ?> • 
                                    <strong>Domaine:</strong> <?= htmlspecialchars($offer['domain']) ?><br>
                                    <strong>Durée:</strong> <?= htmlspecialchars($offer['duration']) ?> mois • 
                                    <strong>Salaire:</strong> <?= htmlspecialchars($offer['salary']) ?> MAD
                                </p>
                                <p class="text-muted mb-2">
                                    <small>
                                        <i class="bi bi-clock"></i> Publiée le <?= date('d/m/Y', strtotime($offer['created_at'])) ?>
                                        • Date limite: <?= date('d/m/Y', strtotime($offer['deadline'])) ?>
                                    </small>
                                </p>
                                <p class="mb-0"><?= nl2br(htmlspecialchars(substr($offer['description'], 0, 200))) ?>...</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge bg-<?= $statusColor ?> status-badge">
                                    <?= $statusText ?>
                                </span>
                                
                                <div class="mt-3">
                                    <div class="mb-2">
                                        <small class="text-muted">Candidatures:</small><br>
                                        <span class="badge bg-info"><?= $offer['candidature_count'] ?> total</span>
                                        <?php if ($offer['pending_count'] > 0): ?>
                                        <span class="badge bg-warning"><?= $offer['pending_count'] ?> en attente</span>
                                        <?php endif; ?>
                                        <?php if ($offer['accepted_count'] > 0): ?>
                                        <span class="badge bg-success"><?= $offer['accepted_count'] ?> acceptées</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="gerer_candidatures.php?offer_id=<?= $offer['id'] ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-people"></i> Voir candidatures
                                        </a>
                                        <a href="post_offer.php?edit=<?= $offer['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-pencil"></i> Modifier
                                        </a>
                                    </div>
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