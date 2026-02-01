<?php
session_start();

if (!isset($_SESSION['company_id']) || $_SESSION['user_type'] !== 'company') {
    header('Location: login_ultimate.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

$companyId = $_SESSION['company_id'];

// Statistiques
$statsQuery = "
    SELECT 
        COUNT(DISTINCT o.id) as total_offers,
        COUNT(a.id) as total_applications,
        SUM(CASE WHEN a.status = 'accepted' THEN 1 ELSE 0 END) as accepted,
        SUM(CASE WHEN a.status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending,
        AVG(TIMESTAMPDIFF(HOUR, a.sent_at, NOW())) as avg_response_hours
    FROM offers o
    LEFT JOIN applications a ON o.id = a.offer_id
    WHERE o.company_id = ?
    GROUP BY o.company_id
";

$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute([$companyId]);
$stats = $statsStmt->fetch();

if (!$stats) {
    $stats = [
        'total_offers' => 0,
        'total_applications' => 0,
        'accepted' => 0,
        'rejected' => 0,
        'pending' => 0,
        'avg_response_hours' => 0
    ];
}

// Taux de réponse
$responseRate = $stats['total_applications'] > 0 
    ? round(($stats['accepted'] + $stats['rejected']) / $stats['total_applications'] * 100, 1)
    : 0;

// Dernières offres
$recentOffersQuery = "
    SELECT o.*, COUNT(a.id) as applications_count
    FROM offers o
    LEFT JOIN applications a ON o.id = a.offer_id
    WHERE o.company_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 5
";

$recentOffersStmt = $db->prepare($recentOffersQuery);
$recentOffersStmt->execute([$companyId]);
$recentOffers = $recentOffersStmt->fetchAll();

// Applications par mois
$monthlyQuery = "
    SELECT 
        DATE_FORMAT(a.sent_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM applications a
    JOIN offers o ON a.offer_id = o.id
    WHERE o.company_id = ? 
    AND a.sent_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(a.sent_at, '%Y-%m')
    ORDER BY month DESC
";

$monthlyStmt = $db->prepare($monthlyQuery);
$monthlyStmt->execute([$companyId]);
$monthlyData = $monthlyStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques • InternEasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); }
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            height: 100%;
        }
        .stats-card:hover { transform: translateY(-5px); }
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="text-dark"><i class="bi bi-bar-chart"></i> Statistiques</h1>
                <p class="text-muted mb-0">Analysez vos performances</p>
            </div>
            <div>
                <a href="dashboard_company.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Retour au dashboard
                </a>
            </div>
        </div>

        <!-- Cartes statistiques -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-briefcase"></i>
                    </div>
                    <div class="stats-number text-primary"><?= $stats['total_offers'] ?></div>
                    <h6 class="text-muted mb-0">Offres publiées</h6>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stats-number text-success"><?= $stats['total_applications'] ?></div>
                    <h6 class="text-muted mb-0">Candidatures totales</h6>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="stats-number text-warning"><?= $stats['pending'] ?></div>
                    <h6 class="text-muted mb-0">En attente</h6>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-percent"></i>
                    </div>
                    <div class="stats-number text-info"><?= $responseRate ?>%</div>
                    <h6 class="text-muted mb-0">Taux de réponse</h6>
                </div>
            </div>
        </div>

        <!-- Graphiques -->
        <div class="row g-4">
            <!-- Graphique 1 : Répartition des statuts -->
            <div class="col-lg-6">
                <div class="chart-container">
                    <h5 class="mb-3">Répartition des candidatures</h5>
                    <canvas id="statusChart" height="250"></canvas>
                </div>
            </div>
            
            <!-- Graphique 2 : Candidatures par mois -->
            <div class="col-lg-6">
                <div class="chart-container">
                    <h5 class="mb-3">Évolution sur 6 mois</h5>
                    <canvas id="monthlyChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Dernières offres -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="chart-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Dernières offres publiées</h5>
                        <a href="post_offer.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus"></i> Nouvelle offre
                        </a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Poste</th>
                                    <th>Date publication</th>
                                    <th>Candidatures</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentOffers)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="bi bi-briefcase display-6 text-muted"></i>
                                        <p class="mt-2">Aucune offre publiée</p>
                                        <a href="post_offer.php" class="btn btn-primary btn-sm">Publier une offre</a>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($recentOffers as $offer): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($offer['title']) ?></strong><br>
                                        <small class="text-muted"><?= $offer['city'] ?></small>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($offer['created_at'])) ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?= $offer['applications_count'] ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $statusColor = match($offer['status']) {
                                            'active' => 'success',
                                            'inactive' => 'secondary',
                                            'draft' => 'warning',
                                            'closed' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusColor ?>">
                                            <?= $offer['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="post_offer.php?edit=<?= $offer['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Voir
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Graphique 1 : Répartition des statuts
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Acceptées', 'Refusées', 'En attente'],
                datasets: [{
                    data: [
                        <?= $stats['accepted'] ?>,
                        <?= $stats['rejected'] ?>,
                        <?= $stats['pending'] ?>
                    ],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Graphique 2 : Évolution mensuelle
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php 
                    $labels = [];
                    foreach ($monthlyData as $data) {
                        $labels[] = "'" . date('M Y', strtotime($data['month'] . '-01')) . "'";
                    }
                    echo implode(', ', array_reverse($labels));
                    ?>
                ].reverse(),
                datasets: [{
                    label: 'Candidatures',
                    data: [
                        <?php 
                        $values = [];
                        foreach ($monthlyData as $data) {
                            $values[] = $data['count'];
                        }
                        echo implode(', ', array_reverse($values));
                        ?>
                    ].reverse(),
                    borderColor: 'rgb(13, 110, 253)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>