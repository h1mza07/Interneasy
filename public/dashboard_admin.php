<?php
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/models/Admin.php';
require_once __DIR__ . '/../app/models/Entreprise.php';
require_once __DIR__ . '/../app/models/Etudiant.php';
require_once __DIR__ . '/../app/models/Offre.php';
require_once __DIR__ . '/../app/models/Candidature.php';

Session::start();
Session::requireRole('admin');

// Récupérer les statistiques
$stats = Admin::getStats();
$recentCandidatures = Admin::getRecentCandidatures();
$recentOffres = Admin::getRecentOffres();
$entreprisesEnAttente = Entreprise::getNonValidees();
$toutesEntreprises = Entreprise::getAll();

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['valider_entreprise'])) {
        $entrepriseId = $_POST['entreprise_id'];
        Entreprise::valider($entrepriseId);
        header("Location: dashboard_admin.php?success=validation");
        exit();
    }
    
    if (isset($_POST['suspendre_entreprise'])) {
        $entrepriseId = $_POST['entreprise_id'];
        Entreprise::suspendre($entrepriseId);
        header("Location: dashboard_admin.php?success=suspension");
        exit();
    }
}

$successMessage = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'validation') {
        $successMessage = 'Entreprise validée avec succès !';
    } elseif ($_GET['success'] === 'suspension') {
        $successMessage = 'Entreprise suspendue avec succès !';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - InternEasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-briefcase"></i> InternEasy - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <span class="nav-item nav-link text-white">
                    <i class="bi bi-person-circle"></i> <?php echo Session::getUserName(); ?>
                </span>
                <a class="nav-item nav-link" href="index.php?controller=Auth&action=logout">
                    <i class="bi bi-box-arrow-right"></i> Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $successMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="list-group">
                    <a href="#dashboard" class="list-group-item list-group-item-action active">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="#entreprises" class="list-group-item list-group-item-action">
                        <i class="bi bi-buildings"></i> Entreprises
                        <?php if (count($entreprisesEnAttente) > 0): ?>
                            <span class="badge bg-danger rounded-pill float-end">
                                <?php echo count($entreprisesEnAttente); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="#etudiants" class="list-group-item list-group-item-action">
                        <i class="bi bi-people"></i> Étudiants
                    </a>
                    <a href="#offres" class="list-group-item list-group-item-action">
                        <i class="bi bi-file-text"></i> Offres
                    </a>
                    <a href="#candidatures" class="list-group-item list-group-item-action">
                        <i class="bi bi-envelope"></i> Candidatures
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <!-- Statistiques -->
                <div class="row mb-4" id="dashboard">
                    <h2 class="mb-4">Tableau de bord</h2>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-people"></i> Étudiants
                                </h5>
                                <h2 class="card-text"><?php echo $stats['etudiants']; ?></h2>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-buildings"></i> Entreprises
                                </h5>
                                <h2 class="card-text"><?php echo $stats['entreprises']; ?></h2>
                                <small>Validées: <?php echo $stats['entreprises_validees']; ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-file-text"></i> Offres
                                </h5>
                                <h2 class="card-text"><?php echo $stats['offres']; ?></h2>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-envelope"></i> Candidatures
                                </h5>
                                <h2 class="card-text"><?php echo $stats['candidatures']; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Entreprises en attente -->
                <div class="row mb-4" id="entreprises">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-warning">
                                <h5 class="mb-0">
                                    <i class="bi bi-clock"></i> Entreprises en attente de validation
                                    <span class="badge bg-danger"><?php echo count($entreprisesEnAttente); ?></span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($entreprisesEnAttente) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Nom</th>
                                                    <th>Email</th>
                                                    <th>Date d'inscription</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($entreprisesEnAttente as $entreprise): ?>
                                                    <tr>
                                                        <td><?php echo $entreprise['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($entreprise['nom']); ?></td>
                                                        <td><?php echo htmlspecialchars($entreprise['email']); ?></td>
                                                        <td><?php echo date('d/m/Y', strtotime($entreprise['created_at'] ?? 'now')); ?></td>
                                                        <td>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="entreprise_id" value="<?php echo $entreprise['id']; ?>">
                                                                <button type="submit" name="valider_entreprise" 
                                                                        class="btn btn-success btn-sm">
                                                                    <i class="bi bi-check-circle"></i> Valider
                                                                </button>
                                                            </form>
                                                            <button class="btn btn-danger btn-sm" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#deleteModal<?php echo $entreprise['id']; ?>">
                                                                <i class="bi bi-x-circle"></i> Rejeter
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i> Aucune entreprise en attente de validation.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dernières candidatures -->
                <div class="row" id="candidatures">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-envelope"></i> Dernières candidatures
                                </h5>
                            </div>
                            <div class="card-body">
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
                                            <?php foreach ($recentCandidatures as $candidature): ?>
                                                <tr>
                                                    <td><?php echo $candidature['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($candidature['etudiant_nom']); ?></td>
                                                    <td><?php echo htmlspecialchars($candidature['offre_titre']); ?></td>
                                                    <td><?php echo htmlspecialchars($candidature['entreprise_nom']); ?></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($candidature['dateEnvoi'])); ?></td>
                                                    <td>
                                                        <span class="badge 
                                                            <?php echo $candidature['statut'] === 'acceptée' ? 'bg-success' : 
                                                                   ($candidature['statut'] === 'refusée' ? 'bg-danger' : 'bg-warning'); ?>">
                                                            <?php echo $candidature['statut']; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Scroll vers les sections
        document.querySelectorAll('.list-group-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                    
                    // Mettre à jour l'active class
                    document.querySelectorAll('.list-group-item').forEach(i => {
                        i.classList.remove('active');
                    });
                    this.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>