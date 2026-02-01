<?php
// gerer_candidatures.php - Gestion des candidatures pour entreprises
session_start();

if (!isset($_SESSION['company_id']) || $_SESSION['user_type'] !== 'company') {
    header('Location: ../login_ultimate.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

$companyId = $_SESSION['company_id'];

// Traitement des actions (accepter/refuser)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $appId = $_POST['app_id'] ?? 0;
    $action = $_POST['action']; // 'accept' ou 'reject'
    
    // Vérifier que la candidature appartient à une offre de cette entreprise
    $checkQuery = "SELECT a.id FROM applications a 
                   JOIN offers o ON a.offer_id = o.id 
                   WHERE a.id = ? AND o.company_id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$appId, $companyId]);
    
    if ($checkStmt->fetch()) {
        $newStatus = $action === 'accept' ? 'accepted' : 'rejected';
        
        $updateQuery = "UPDATE applications SET status = ? WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$newStatus, $appId]);
        
        // Message de succès
        $_SESSION['message'] = 'Candidature ' . ($action === 'accept' ? 'acceptée' : 'refusée') . ' avec succès';
        $_SESSION['message_type'] = 'success';
    }
    
    header('Location: gerer_candidatures.php');
    exit();
}

// Récupérer toutes les candidatures pour cette entreprise
$query = "SELECT a.*, 
                 s.first_name, 
                 s.last_name, 
                 s.email,
                 o.title as offer_title, 
                 o.city,
                 o.domain,
                 a.status,
                 a.sent_at,
                 cv.cv_name,
                 cv.file_path as cv_path,
                 a.message
          FROM applications a 
          JOIN students s ON a.student_id = s.id 
          JOIN offers o ON a.offer_id = o.id LEFT JOIN cvs cv ON a.cv_id = cv.id
          WHERE o.company_id = ? 
          ORDER BY a.sent_at DESC";
          
$stmt = $db->prepare($query);
$stmt->execute([$companyId]);
$applications = $stmt->fetchAll();

// Message flash
$message = $_SESSION['message'] ?? '';
$messageType = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer Candidatures • InternEasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .header-card {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .filter-badge {
            cursor: pointer;
            transition: all 0.3s;
        }
        .filter-badge.active {
            transform: scale(1.1);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
        }
        .application-item {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        .application-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .student-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Header -->
        <div class="header-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2">Gérer les Candidatures</h1>
                    <p class="mb-0"><?= count($applications) ?> candidature(s) reçue(s)</p>
                </div>
                <div>
                    <a href="dashboard_company.php" class="btn btn-light">
                        <i class="bi bi-arrow-left"></i> Retour au dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Message flash -->
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Filtres -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Filtrer par statut</h5>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge bg-primary filter-badge active" data-filter="all">
                        Toutes (<?= count($applications) ?>)
                    </span>
                    <span class="badge bg-warning filter-badge" data-filter="pending">
                        En attente (<?= count(array_filter($applications, fn($a) => $a['status'] === 'pending')) ?>)
                    </span>
                    <span class="badge bg-success filter-badge" data-filter="accepted">
                        Acceptées (<?= count(array_filter($applications, fn($a) => $a['status'] === 'accepted')) ?>)
                    </span>
                    <span class="badge bg-danger filter-badge" data-filter="rejected">
                        Refusées (<?= count(array_filter($applications, fn($a) => $a['status'] === 'rejected')) ?>)
                    </span>
                </div>
            </div>
        </div>

        <!-- Liste des candidatures -->
        <div id="applicationsList">
            <?php if (empty($applications)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-people display-1 text-muted"></i>
                    <h3 class="mt-3">Aucune candidature</h3>
                    <p class="text-muted">Vous n'avez pas encore reçu de candidatures</p>
                </div>
            <?php else: ?>
                <?php foreach ($applications as $app): 
                    $statusColor = match($app['status']) {
                        'pending' => 'warning',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'secondary'
                    };
                    $statusText = match($app['status']) {
                        'pending' => 'En attente',
                        'accepted' => 'Acceptée',
                        'rejected' => 'Refusée',
                        default => $app['status']
                    };
                ?>
                <div class="application-item" data-status="<?= $app['status'] ?>">
                    <div class="row align-items-center">
                        <div class="col-md-1 text-center">
                            <div class="student-avatar mx-auto">
                                <?= strtoupper(substr($app['first_name'], 0, 1) . substr($app['last_name'], 0, 1)) ?>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <h5 class="mb-1"><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></h5>
                            <p class="text-muted mb-1">
                                <i class="bi bi-envelope"></i> <?= htmlspecialchars($app['email']) ?><br>
                                <i class="bi bi-briefcase"></i> <?= htmlspecialchars($app['offer_title']) ?><br>
                                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($app['city']) ?> • 
                                <i class="bi bi-tags"></i> <?= htmlspecialchars($app['domain']) ?>
                            </p>
                            <small class="text-muted">
                                <i class="bi bi-clock"></i> Postulé le <?= date('d/m/Y à H:i', strtotime($app['sent_at'])) ?>
                            </small>
                        </div>
                        <div class="col-md-3 text-center">
                            <span class="badge bg-<?= $statusColor ?> py-2 px-3">
                                <?= $statusText ?>
                            </span>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex flex-column gap-2">
                                <?php if ($app['status'] === 'pending'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="btn btn-success btn-sm w-100" 
                                            onclick="return confirm('Accepter cette candidature ?')">
                                        <i class="bi bi-check-circle"></i> Accepter
                                    </button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-danger btn-sm w-100"
                                            onclick="return confirm('Refuser cette candidature ?')">
                                        <i class="bi bi-x-circle"></i> Refuser
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if (!empty($app['cv_url'])): ?>
                                <a href="<?= htmlspecialchars($app['cv_url']) ?>" 
                                   target="_blank" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-file-earmark-pdf"></i> Voir CV
                                </a>
                                <?php endif; ?>
                                
                                <?php if (!empty($app['message'])): ?>
                                <button class="btn btn-outline-info btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#messageModal<?= $app['id'] ?>">
                                    <i class="bi bi-chat-text"></i> Voir message
                                </button>
                                
                                <!-- Modal Message -->
                                <div class="modal fade" id="messageModal<?= $app['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Message de motivation</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><?= nl2br(htmlspecialchars($app['message'])) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filtrage des candidatures
        document.querySelectorAll('.filter-badge').forEach(badge => {
            badge.addEventListener('click', function() {
                // Activer le badge cliqué
                document.querySelectorAll('.filter-badge').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                const items = document.querySelectorAll('.application-item');
                
                items.forEach(item => {
                    if (filter === 'all' || item.getAttribute('data-status') === filter) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>