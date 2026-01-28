<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/Entreprise.php';

class AdminController {

    // =========================
    // DASHBOARD ADMIN
    // =========================
    public function dashboard() {
        // Vérifier si l'utilisateur est connecté comme admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header("Location: index.php?controller=Auth&action=login");
            exit();
        }

        echo "<!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <title>Dashboard Admin - InternEasy</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
                .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
                h1 { color: #333; }
                .menu { background: #007bff; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .menu a { color: white; text-decoration: none; margin-right: 20px; padding: 10px; }
                .menu a:hover { background: #0056b3; border-radius: 3px; }
                .welcome { background: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 20px; }
                .stat-box { background: #f8f9fa; padding: 20px; border-radius: 5px; border-left: 4px solid #007bff; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='menu'>
                    <a href='index.php?controller=Admin&action=dashboard'>Accueil</a>
                    <a href='index.php?controller=Admin&action=entreprisesEnAttente'>Valider entreprises</a>
                    <a href='dashboard_admin.php'>Dashboard complet</a>
                    <a href='index.php?controller=Auth&action=logout'>Déconnexion</a>
                </div>
                
                <div class='welcome'>
                    <h1>Dashboard Administrateur</h1>
                    <p>Bienvenue <strong>" . $_SESSION['email'] . "</strong> | Rôle: <strong>" . $_SESSION['role'] . "</strong></p>
                </div>
                
                <h2>Actions rapides</h2>
                <div class='stats'>
                    <div class='stat-box'>
                        <h3>🔍 Valider entreprises</h3>
                        <p>Vérifier et valider les nouvelles inscriptions entreprises</p>
                        <a href='index.php?controller=Admin&action=entreprisesEnAttente'>Accéder →</a>
                    </div>
                    
                    <div class='stat-box'>
                        <h3>📊 Dashboard complet</h3>
                        <p>Accéder à toutes les fonctionnalités administratives</p>
                        <a href='dashboard_admin.php'>Accéder →</a>
                    </div>
                    
                    <div class='stat-box'>
                        <h3>👥 Gérer utilisateurs</h3>
                        <p>Voir tous les étudiants et entreprises inscrits</p>
                        <a href='#'>Bientôt disponible</a>
                    </div>
                </div>
                
                <p style='margin-top: 30px; color: #666;'>
                    <i>Version: 1.0 | Date: " . date('d/m/Y') . "</i>
                </p>
            </div>
        </body>
        </html>";
    }

    // =========================
    // LISTE DES ENTREPRISES NON VALIDÉES
    // =========================
    public function entreprisesEnAttente() {
        // Vérifier si l'utilisateur est connecté comme admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header("Location: index.php?controller=Auth&action=login");
            exit();
        }

        $entreprises = Entreprise::getNonValidees();

        echo "<!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <title>Validation entreprises - InternEasy</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                h2 { color: #333; }
                table { border-collapse: collapse; width: 100%; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                th { background-color: #007bff; color: white; }
                tr:nth-child(even) { background-color: #f2f2f2; }
                .btn { background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; }
                .btn:hover { background: #218838; }
                .back-link { display: inline-block; margin-top: 20px; padding: 10px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <h2>Entreprises en attente de validation</h2>";

        if (count($entreprises) === 0) {
            echo "<p>Aucune entreprise en attente de validation.</p>";
        } else {
            echo "<table>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Date d'inscription</th>
                    <th>Action</th>
                </tr>";

            foreach ($entreprises as $e) {
                echo "<tr>
                    <td>" . $e['id'] . "</td>
                    <td>" . $e['nom'] . "</td>
                    <td>" . $e['email'] . "</td>
                    <td>" . date('d/m/Y', strtotime($e['date_inscription'] ?? 'now')) . "</td>
                    <td>
                        <a class='btn' href='index.php?controller=Admin&action=valider&id=" . $e['id'] . "'>Valider</a>
                    </td>
                </tr>";
            }

            echo "</table>";
        }

        echo "<a class='back-link' href='index.php?controller=Admin&action=dashboard'>← Retour au dashboard</a>
        </body>
        </html>";
    }

    // =========================
    // VALIDER UNE ENTREPRISE
    // =========================
    public function valider() {
        // Vérifier si l'utilisateur est connecté comme admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header("Location: index.php?controller=Auth&action=login");
            exit();
        }

        $id = $_GET['id'] ?? 0;
        
        if ($id > 0) {
            Entreprise::valider($id);
            echo "<!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial; padding: 40px; text-align: center; }
                    .success { color: #28a745; font-size: 24px; margin: 20px 0; }
                    .back-link { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
                </style>
            </head>
            <body>
                <div class='success'>✅ Entreprise validée avec succès !</div>
                <p>L'entreprise peut maintenant se connecter et publier des offres.</p>
                <a class='back-link' href='index.php?controller=Admin&action=entreprisesEnAttente'>← Voir les autres entreprises</a>
                <br>
                <a class='back-link' href='index.php?controller=Admin&action=dashboard'>Retour au dashboard</a>
            </body>
            </html>";
        } else {
            echo "ID entreprise invalide.";
        }
    }

    // =========================
    // DÉCONNEXION ADMIN (optionnel)
    // =========================
    public function logout() {
        session_destroy();
        header("Location: index.php?controller=Auth&action=login");
        exit();
    }
}
