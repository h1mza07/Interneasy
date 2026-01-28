<?php
// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté comme étudiant
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'etudiant') {
    // Rediriger vers la page de connexion
    header("Location: index.php?controller=Auth&action=login&error=unauthorized");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Étudiant - InternEasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            padding: 20px;
        }
        .dashboard-header {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .student-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <h1>Dashboard Étudiant</h1>
            <p>Bienvenue, <?php echo htmlspecialchars($_SESSION['nom'] ?? $_SESSION['email']); ?></p>
            <a href="index.php?controller=Auth&action=logout" class="btn btn-light">Déconnexion</a>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="student-card">
                    <h4>Rechercher des offres</h4>
                    <p>Explorez les offres de stage disponibles</p>
                    <a href="#" class="btn btn-primary">Voir les offres</a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="student-card">
                    <h4>Mes candidatures</h4>
                    <p>Suivez vos candidatures envoyées</p>
                    <a href="#" class="btn btn-success">Voir mes candidatures</a>
                </div>
            </div>
        </div>
        
        <div class="student-card">
            <h4>Mon profil</h4>
            <p><strong>Email :</strong> <?php echo $_SESSION['email']; ?></p>
            <p><strong>Rôle :</strong> <?php echo $_SESSION['role']; ?></p>
            <p><strong>ID :</strong> <?php echo $_SESSION['id']; ?></p>
        </div>
    </div>
</body>
</html>