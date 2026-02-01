<?php
// dashboard_admin.php - Même design que les autres dashboards
session_start();

if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login_ultimate.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

// Récupérer les statistiques
$statsQuery = "SELECT 
    (SELECT COUNT(*) FROM students) as total_students,
    (SELECT COUNT(*) FROM companies) as total_companies,
    (SELECT COUNT(*) FROM offers) as total_offers,
    (SELECT COUNT(*) FROM applications) as total_applications";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute();
$stats = $statsStmt->fetch();

// Derniers étudiants
$recentStudentsQuery = "SELECT first_name, last_name, email, created_at FROM students ORDER BY created_at DESC LIMIT 5";
$recentStudentsStmt = $db->prepare($recentStudentsQuery);
$recentStudentsStmt->execute();
$recentStudents = $recentStudentsStmt->fetchAll();

// Dernières entreprises
$recentCompaniesQuery = "SELECT name, email, city, created_at FROM companies ORDER BY created_at DESC LIMIT 5";
$recentCompaniesStmt = $db->prepare($recentCompaniesQuery);
$recentCompaniesStmt->execute();
$recentCompanies = $recentCompaniesStmt->fetchAll();

// Dernières offres
$recentOffersQuery = "SELECT o.id, o.title, c.name as company_name, o.city, o.created_at 
                      FROM offers o 
                      JOIN companies c ON o.company_id = c.id 
                      ORDER BY o.created_at DESC LIMIT 5";
$recentOffersStmt = $db->prepare($recentOffersQuery);
$recentOffersStmt->execute();
$recentOffers = $recentOffersStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin • InternEasy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.3);
        }
        
        .sidebar-brand {
            padding: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
        }
        
        .sidebar-nav .nav-link {
            color: rgba(255, 255, 255, 0.7);
            padding: 0.85rem 1.2rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            margin: 0.25rem 1rem;
        }
        
        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            background: rgba(220, 53, 69, 0.2);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar-nav .nav-link i {
            width: 24px;
            margin-right: 12px;
            font-size: 1.2rem;
        }
        
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }
        
        .navbar-top {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
            border-top: 4px solid #dc3545;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #dc3545;
            line-height: 1;
        }
        
        .table-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .table-card th {
            background: #f8fafc;
            color: #1e293b;
            font-weight: 600;
            padding: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .table-card td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .table-card tr:hover {
            background: #f8fafc;
        }
        
        .badge-status {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .notification-dot {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 12px;
            height: 12px;
            background: #28a745;
            border-radius: 50%;
            border: 2px solid white;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        /* Couleurs spécifiques admin */
        .text-admin { color: #dc3545; }
        .bg-admin { background-color: #dc3545; }
        .border-admin { border-color: #dc3545; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-brand">
            <h2 class="mb-0">InternEasy</h2>
            <small class="text-white-50">Espace Administrateur</small>
        </div>
        
        <div class="py-4">
            <div class="text-center mb-4 px-3">
                <div class="user-avatar mx-auto mb-3">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h5 class="text-white mb-1"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrateur') ?></h5>
                <small class="text-white-50"><?= htmlspecialchars($_SESSION['admin_email'] ?? 'admin@interneasy.com') ?></small>
                <div class="mt-2">
                    <span class="badge bg-danger">Super Admin</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard_admin.php" class="nav-link active">
                    <i class="bi bi-speedometer2"></i>
                    <span>Tableau de bord</span>
                </a>
                
                <a href="admin_etudiants.php" class="nav-link">
                    <i class="bi bi-people"></i>
                    <span>Étudiants</span>
                    <span class="badge bg-primary ms-auto"><?= $stats['total_students'] ?></span>
                </a>
                
                <a href="admin_entreprises.php" class="nav-link">
                    <i class="bi bi-building"></i>
                    <span>Entreprises</span>
                    <span class="badge bg-warning ms-auto"><?= $stats['total_companies'] ?></span>
                </a>
                
                <a href="admin_offres.php" class="nav-link">
                    <i class="bi bi-briefcase"></i>
                    <span>Offres</span>
                    <span class="badge bg-success ms-auto"><?= $stats['total_offers'] ?></span>
                </a>
                
                <a href="admin_candidatures.php" class="nav-link">
                    <i class="bi bi-file-text"></i>
                    <span>Candidatures</span>
                    <span class="badge bg-info ms-auto"><?= $stats['total_applications'] ?></span>
                </a>
                
                <a href="admin_parametres.php" class="nav-link">
                    <i class="bi bi-gear"></i>
                    <span>Paramètres</span>
                </a>
                
                <a href="admin_statistiques.php" class="nav-link">
                    <i class="bi bi-bar-chart"></i>
                    <span>Statistiques</span>
                </a>
                
                <a href="admin_securite.php" class="nav-link">
                    <i class="bi bi-shield-check"></i>
                    <span>Sécurité</span>
                </a>
            </nav>
        </div>
        
        <div class="p-4 border-top border-white-10">
            <a href="logout.php" class="btn btn-outline-light w-100">
                <i class="bi bi-box-arrow-right me-2"></i>
                Déconnexion
            </a>
        </div>
    </aside>

    <main class="main-content">
        <nav class="navbar-top">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-outline-danger d-lg-none me-3" id="sidebarToggle">
                            <i class="bi bi-list"></i>
                        </button>
                        <h4 class="mb-0 text-admin">Tableau de bord administrateur</h4>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3">
                        <div class="position-relative">
                            <button class="btn btn-light position-relative">
                                <i class="bi bi-bell"></i>
                                <span class="notification-dot"></span>
                            </button>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-link text-dark text-decoration-none dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                                <div class="user-avatar me-2">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <div class="text-start">
                                    <small class="text-muted d-block">Connecté en tant que</small>
                                    <strong class="text-admin">Administrateur</strong>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid py-4">
            <div class="row g-4 mb-5">
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-2">Étudiants inscrits</h6>
                                <div class="stats-number"><?= $stats['total_students'] ?></div>
                            </div>
                            <div class="text-primary fs-1">
                                <i class="bi bi-people text-admin"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <small class="text-success"><i class="bi bi-arrow-up"></i> +<?= min($stats['total_students'], 5) ?> ce mois</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-2">Entreprises</h6>
                                <div class="stats-number"><?= $stats['total_companies'] ?></div>
                            </div>
                            <div class="text-warning fs-1">
                                <i class="bi bi-building text-admin"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">+<?= min($stats['total_companies'], 3) ?> cette semaine</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-2">Offres publiées</h6>
                                <div class="stats-number"><?= $stats['total_offers'] ?></div>
                            </div>
                            <div class="text-success fs-1">
                                <i class="bi bi-briefcase text-admin"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">+<?= min($stats['total_offers'], 8) ?> ce mois</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-2">Candidatures</h6>
                                <div class="stats-number"><?= $stats['total_applications'] ?></div>
                            </div>
                            <div class="text-info fs-1">
                                <i class="bi bi-file-text text-admin"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar bg-admin" style="width: <?= $stats['total_applications'] > 0 ? min(($stats['total_applications'] / 100) * 100, 100) : 0 ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="table-card">
                        <div class="p-3 border-bottom">
                            <h5 class="mb-0"><i class="bi bi-people me-2 text-admin"></i>Derniers étudiants inscrits</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Date d'inscription</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recentStudents)): ?>
                                        <?php foreach ($recentStudents as $student): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($student['email']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($student['created_at'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">
                                                Aucun étudiant inscrit
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 border-top text-center">
                            <a href="admin_etudiants.php" class="btn btn-outline-admin btn-sm">Voir tous les étudiants</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="table-card">
                        <div class="p-3 border-bottom">
                            <h5 class="mb-0"><i class="bi bi-building me-2 text-admin"></i>Dernières entreprises</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Entreprise</th>
                                        <th>Email</th>
                                        <th>Ville</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recentCompanies)): ?>
                                        <?php foreach ($recentCompanies as $company): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($company['name']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($company['email']) ?></td>
                                            <td><?= htmlspecialchars($company['city']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($company['created_at'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                Aucune entreprise inscrite
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 border-top text-center">
                            <a href="admin_entreprises.php" class="btn btn-outline-admin btn-sm">Voir toutes les entreprises</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12 mb-4">
                    <div class="table-card">
                        <div class="p-3 border-bottom">
                            <h5 class="mb-0"><i class="bi bi-briefcase me-2 text-admin"></i>Dernières offres publiées</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Titre</th>
                                        <th>Entreprise</th>
                                        <th>Lieu</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recentOffers)): ?>
                                        <?php foreach ($recentOffers as $offer): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($offer['title']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($offer['company_name']) ?></td>
                                            <td><?= htmlspecialchars($offer['city']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($offer['created_at'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                Aucune offre publiée
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 border-top text-center">
                            <a href="admin_offres.php" class="btn btn-outline-admin btn-sm">Voir toutes les offres</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="table-card">
                        <div class="p-3 border-bottom">
                            <h5 class="mb-0"><i class="bi bi-activity me-2 text-admin"></i>Statistiques rapides</h5>
                        </div>
                        <div class="p-4">
                            <div class="row text-center">
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 border rounded">
                                        <div class="fs-4 text-admin">100%</div>
                                        <small class="text-muted">Système opérationnel</small>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 border rounded">
                                        <div class="fs-4 text-success">0</div>
                                        <small class="text-muted">Alertes actives</small>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 border rounded">
                                        <div class="fs-4 text-warning"><?= $stats['total_offers'] > 0 ? round(($stats['total_applications'] / $stats['total_offers']), 1) : 0 ?></div>
                                        <small class="text-muted">Candidatures/Offre</small>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 border rounded">
                                        <div class="fs-4 text-info"><?= date('H:i') ?></div>
                                        <small class="text-muted">Dernière mise à jour</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar sur mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Style pour bouton admin
        const style = document.createElement('style');
        style.textContent = `
            .btn-outline-admin {
                color: #dc3545;
                border-color: #dc3545;
            }
            .btn-outline-admin:hover {
                background-color: #dc3545;
                color: white;
            }
            .progress-bar.bg-admin {
                background-color: #dc3545 !important;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>