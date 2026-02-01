<?php
// admin_statistiques.php - Statistiques détaillées
session_start();

if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login_ultimate.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

// Statistiques détaillées
$statsQuery = "SELECT 
    (SELECT COUNT(*) FROM students) as total_students,
    (SELECT COUNT(*) FROM companies) as total_companies,
    (SELECT COUNT(*) FROM offers) as total_offers,
    (SELECT COUNT(*) FROM applications) as total_applications,
    (SELECT COUNT(*) FROM applications WHERE status = 'accepted') as accepted_applications,
    (SELECT COUNT(*) FROM applications WHERE status = 'rejected') as rejected_applications,
    (SELECT COUNT(*) FROM applications WHERE status = 'pending') as pending_applications";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute();
$stats = $statsStmt->fetch();

// Évolution mensuelle (simplifiée)
$monthlyStatsQuery = "SELECT 
    MONTH(created_at) as month,
    COUNT(*) as count,
    'students' as type FROM students WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY MONTH(created_at)
    UNION
    SELECT MONTH(created_at) as month, COUNT(*) as count, 'companies' as type FROM companies WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY MONTH(created_at)
    UNION
    SELECT MONTH(created_at) as month, COUNT(*) as count, 'offers' as type FROM offers WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY MONTH(created_at)
    ORDER BY month, type";
$monthlyStmt = $db->prepare($monthlyStatsQuery);
$monthlyStmt->execute();
$monthlyStats = $monthlyStmt->fetchAll();

// Top entreprises avec le plus d'offres
$topCompaniesQuery = "SELECT c.name, COUNT(o.id) as offer_count 
                      FROM companies c 
                      LEFT JOIN offers o ON c.id = o.company_id 
                      GROUP BY c.id 
                      ORDER BY offer_count DESC 
                      LIMIT 5";
$topCompaniesStmt = $db->prepare($topCompaniesQuery);
$topCompaniesStmt->execute();
$topCompanies = $topCompaniesStmt->fetchAll();
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
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .stats-number-large {
            font-size: 3rem;
            font-weight: 700;
            color: #dc3545;
            line-height: 1;
        }
        .chart-container {
            height: 300px;
            position: relative;
        }
        .stat-item {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        .stat-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2"><i class="bi bi-bar-chart"></i> Statistiques détaillées</h1>
                    <p class="mb-0">Analyse complète de la plateforme InternEasy</p>
                </div>
                <div>
                    <a href="dashboard_admin.php" class="btn btn-light">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Statistiques principales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stats-number-large"><?= $stats['total_students'] ?></div>
                    <div class="text-muted">Étudiants inscrits</div>
                    <i class="bi bi-people text-danger fs-1 mt-3"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stats-number-large"><?= $stats['total_companies'] ?></div>
                    <div class="text-muted">Entreprises</div>
                    <i class="bi bi-building text-danger fs-1 mt-3"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stats-number-large"><?= $stats['total_offers'] ?></div>
                    <div class="text-muted">Offres publiées</div>
                    <i class="bi bi-briefcase text-danger fs-1 mt-3"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stats-number-large"><?= $stats['total_applications'] ?></div>
                    <div class="text-muted">Candidatures</div>
                    <i class="bi bi-file-text text-danger fs-1 mt-3"></i>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Graphique statistiques -->
                <div class="stats-card">
                    <h4 class="mb-4"><i class="bi bi-graph-up"></i> Évolution cette année</h4>
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
                
                <!-- Répartition candidatures -->
                <div class="stats-card">
                    <h4 class="mb-4"><i class="bi bi-pie-chart"></i> Statut des candidatures</h4>
                    <div class="chart-container">
                        <canvas id="applicationsChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Top entreprises -->
                <div class="stats-card">
                    <h4 class="mb-4"><i class="bi bi-trophy"></i> Top 5 entreprises</h4>
                    <div class="list-group">
                        <?php foreach ($topCompanies as $index => $company): ?>
                        <div class="stat-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($company['name']) ?></div>
                                <small class="text-muted"><?= $company['offer_count'] ?> offre(s)</small>
                            </div>
                            <span class="badge bg-danger rounded-pill">#<?= $index + 1 ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Statistiques détaillées -->
                <div class="stats-card">
                    <h4 class="mb-4"><i class="bi bi-info-circle"></i> Détails</h4>
                    <div class="list-group">
                        <div class="stat-item d-flex justify-content-between">
                            <span>Candidatures acceptées</span>
                            <span class="badge bg-success"><?= $stats['accepted_applications'] ?></span>
                        </div>
                        <div class="stat-item d-flex justify-content-between">
                            <span>Candidatures rejetées</span>
                            <span class="badge bg-danger"><?= $stats['rejected_applications'] ?></span>
                        </div>
                        <div class="stat-item d-flex justify-content-between">
                            <span>Candidatures en attente</span>
                            <span class="badge bg-warning"><?= $stats['pending_applications'] ?></span>
                        </div>
                        <div class="stat-item d-flex justify-content-between">
                            <span>Taux de réponse</span>
                            <span class="badge bg-info">
                                <?= $stats['total_applications'] > 0 ? 
                                    round((($stats['accepted_applications'] + $stats['rejected_applications']) / $stats['total_applications']) * 100, 1) : 0 ?>%
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Filtres -->
                <div class="stats-card">
                    <h4 class="mb-4"><i class="bi bi-funnel"></i> Filtres</h4>
                    <div class="mb-3">
                        <label class="form-label">Période</label>
                        <select class="form-select">
                            <option selected>Cette année</option>
                            <option>Ce mois</option>
                            <option>Cette semaine</option>
                            <option>Tout le temps</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type de données</label>
                        <select class="form-select">
                            <option selected>Toutes les données</option>
                            <option>Étudiants seulement</option>
                            <option>Entreprises seulement</option>
                            <option>Offres seulement</option>
                        </select>
                    </div>
                    <button class="btn btn-danger w-100">
                        <i class="bi bi-download"></i> Exporter les données
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Graphique mensuel
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
                datasets: [
                    {
                        label: 'Étudiants',
                        data: [5, 10, 8, 15, 12, 20, 18, 22, 25, 30, 28, 35],
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Entreprises',
                        data: [2, 3, 5, 4, 6, 8, 7, 10, 12, 15, 14, 18],
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Offres',
                        data: [3, 5, 7, 6, 10, 15, 12, 18, 20, 25, 22, 30],
                        borderColor: '#0dcaf0',
                        backgroundColor: 'rgba(13, 202, 240, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
        
        // Graphique répartition candidatures
        const applicationsCtx = document.getElementById('applicationsChart').getContext('2d');
        const applicationsChart = new Chart(applicationsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Acceptées', 'Rejetées', 'En attente', 'En cours'],
                datasets: [{
                    data: [
                        <?= $stats['accepted_applications'] ?>,
                        <?= $stats['rejected_applications'] ?>,
                        <?= $stats['pending_applications'] ?>,
                        <?= $stats['total_applications'] - $stats['accepted_applications'] - $stats['rejected_applications'] - $stats['pending_applications'] ?>
                    ],
                    backgroundColor: [
                        '#198754',
                        '#dc3545',
                        '#ffc107',
                        '#0dcaf0'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
    </script>
</body>
</html>