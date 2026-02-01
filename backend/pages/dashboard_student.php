<?php
// dashboard_student.php - VERSION BACKEND COMPLÈTE + LIEN CARTE
session_start();

// Vérifier si étudiant connecté
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login_ultimate.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

// 1. RÉCUPÉRER LES INFOS DE L'ÉTUDIANT
$studentQuery = "SELECT * FROM students WHERE id = ?";
$studentStmt = $db->prepare($studentQuery);
$studentStmt->execute([$_SESSION['user_id']]);
$student = $studentStmt->fetch();

if (!$student) {
    session_destroy();
    header('Location: ../login_ultimate.php');
    exit();
}

// 2. STATISTIQUES
$totalAppsQuery = "SELECT COUNT(*) as count FROM applications WHERE student_id = ?";
$totalStmt = $db->prepare($totalAppsQuery);
$totalStmt->execute([$_SESSION['user_id']]);
$totalApplications = $totalStmt->fetch()['count'];

// Initiales de l'étudiant
$initials = strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1));

// École et niveau
$schoolInfo = '';
if (!empty($student['school'])) {
    $schoolInfo .= htmlspecialchars($student['school']);
}
if (!empty($student['education_level'])) {
    $schoolInfo .= ($schoolInfo ? ' • ' : '') . htmlspecialchars($student['education_level']);
}
// Études (domaine)
$studiesInfo = '';
if (!empty($student['field_of_study'])) {
    $studiesInfo .= htmlspecialchars($student['field_of_study']);
}
// Si pas de field_of_study, utiliser education_level comme fallback
if (empty($studiesInfo) && !empty($student['education_level'])) {
    $studiesInfo = $student['education_level'];
}
// Entretiens programmés
$interviewsQuery = "SELECT COUNT(*) as count FROM applications WHERE student_id = ? AND interview_date IS NOT NULL AND interview_date >= CURDATE()";
$interviewsStmt = $db->prepare($interviewsQuery);
$interviewsStmt->execute([$_SESSION['user_id']]);
$interviewsCount = $interviewsStmt->fetch()['count'];

// Jours restants jusqu'au diplôme
$daysRemaining = 'N/A';
if (!empty($student['graduation_date'])) {
    $today = new DateTime();
    $gradDate = new DateTime($student['graduation_date']);
    $interval = $today->diff($gradDate);
    $daysRemaining = $interval->days . ' jours';
}

$pendingQuery = "SELECT COUNT(*) as count FROM applications WHERE student_id = ? AND status = 'pending'";
$pendingStmt = $db->prepare($pendingQuery);
$pendingStmt->execute([$_SESSION['user_id']]);
$pendingApplications = $pendingStmt->fetch()['count'];

$acceptedQuery = "SELECT COUNT(*) as count FROM applications WHERE student_id = ? AND status = 'accepted'";
$acceptedStmt = $db->prepare($acceptedQuery);
$acceptedStmt->execute([$_SESSION['user_id']]);
$acceptedApplications = $acceptedStmt->fetch()['count'];

// 3. COMPTER LES CVs DE L'ÉTUDIANT
$cvsCountQuery = "SELECT COUNT(*) as count FROM cvs WHERE student_id = ?";
$cvsStmt = $db->prepare($cvsCountQuery);
$cvsStmt->execute([$_SESSION['user_id']]);
$cvsCount = $cvsStmt->fetch()['count'];

// 4. CANDIDATURES RÉCENTES (5 dernières)
$recentAppsQuery = "SELECT a.*, o.title, c.name as company_name, o.city, a.status, a.sent_at
                    FROM applications a 
                    JOIN offers o ON a.offer_id = o.id 
                    JOIN companies c ON o.company_id = c.id 
                    WHERE a.student_id = ? 
                    ORDER BY a.sent_at DESC 
                    LIMIT 5";
$recentStmt = $db->prepare($recentAppsQuery);
$recentStmt->execute([$_SESSION['user_id']]);
$recentApplications = $recentStmt->fetchAll();

// 5. OFFRES RECOMMANDÉES (3 offres non postulées) - ANCIENNE VERSION QUI MARCHAIT
$recommendedQuery = "SELECT o.*, c.name as company_name 
                     FROM offers o 
                     JOIN companies c ON o.company_id = c.id 
                     WHERE o.deadline >= CURDATE() 
                     AND o.status = 'active'
                     AND o.id NOT IN (
                         SELECT offer_id FROM applications WHERE student_id = ?
                     )
                     ORDER BY RAND()
                     LIMIT 5";

$recommendedStmt = $db->prepare($recommendedQuery);
$recommendedStmt->execute([$_SESSION['user_id']]);
$allOffers = $recommendedStmt->fetchAll();

// Garder seulement 3 offres pour l'affichage
$recommendedOffers = array_slice($allOffers, 0, 3);

// Score de profil (calcul simple)
$profileScore = 0;
$profileScore += ($cvsCount > 0) ? 30 : 0;
$profileScore += ($totalApplications > 0) ? 20 : 0;
$profileScore += (!empty($student['field_of_study'])) ? 25 : 0;
$profileScore += (!empty($student['school'])) ? 25 : 0;

// DEBUG
echo "<!-- DEBUG: ID étudiant = " . $_SESSION['user_id'] . " -->";
echo "<!-- DEBUG: Nombre d'offres trouvées = " . count($allOffers) . " -->";
echo "<!-- DEBUG: Offres à afficher = " . count($recommendedOffers) . " -->";
echo "<!-- DEBUG: initials = $initials -->";
echo "<!-- DEBUG: pendingApplications = $pendingApplications -->";
echo "<!-- DEBUG: interviewsCount = $interviewsCount -->";
echo "<!-- DEBUG: profileScore = $profileScore -->";
echo "<!-- DEBUG: daysRemaining = $daysRemaining -->";

// 6. CHARGER LE TEMPLATE
$htmlFile = dirname(__DIR__, 2) . '/frontend/dashboard_student.html';
if (!file_exists($htmlFile)) {
    die("Dashboard HTML non trouvé");
}
$html = file_get_contents($htmlFile);

// 7. REMPLACER LE NOM DE L'ÉTUDIANT
$studentName = htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
$html = str_replace('Mohamed Hassan', $studentName, $html);

// 8. CORRECTION DES INITIALES ET INFOS ÉTUDIANT
$html = preg_replace(
    '/<div class="user-avatar mx-auto mb-3">[^<]*<\/div>/',
    '<div class="user-avatar mx-auto mb-3">' . $initials . '</div>',
    $html
);

// Correction des initiales en haut à droite (navbar)
$html = preg_replace(
    '/<div class="user-avatar">[^<]*<\/div>/',
    '<div class="user-avatar">' . $initials . '</div>',
    $html
);

// Remplacer l'info étudiant
$studentInfo = '';
if (!empty($studiesInfo)) {
    $studentInfo = 'Étudiant • ' . htmlspecialchars($studiesInfo);
} elseif (!empty($schoolInfo)) {
    $studentInfo = htmlspecialchars($schoolInfo);
}

if (!empty($studentInfo)) {
    $html = str_replace(
        '<small class="text-white-50">Étudiant • Informatique BAC+5</small>', 
        '<small class="text-white-50">' . $studentInfo . '</small>', 
        $html
    );
}
// 9. CORRECTION COMPLÈTE DES STATISTIQUES

// A. Candidatures actives
$html = preg_replace(
    '/<div class="stats-number">\d+<\/div>\s*<h6 class="text-muted mb-2">Candidatures actives<\/h6>/',
    '<div class="stats-number">' . $pendingApplications . '</div><h6 class="text-muted mb-2">Candidatures actives</h6>',
    $html
);

// B. Entretiens programmés
$html = preg_replace(
    '/<h6 class="text-muted mb-2">Entretiens programmés<\/h6>\s*<div class="stats-number">\d+<\/div>/',
    '<h6 class="text-muted mb-2">Entretiens programmés</h6><div class="stats-number">' . $interviewsCount . '</div>',
    $html
);

// C. Score de profil
$html = preg_replace(
    '/<h6 class="text-muted mb-2">Score de profil<\/h6>\s*<div class="stats-number">\d+%<\/div>/',
    '<h6 class="text-muted mb-2">Score de profil</h6><div class="stats-number">' . $profileScore . '%</div>',
    $html
);

// D. Jours restants
$html = preg_replace(
    '/<h6 class="text-muted mb-2">Jours restants<\/h6>\s*<div class="stats-number">\d+<\/div>/',
    '<h6 class="text-muted mb-2">Jours restants</h6><div class="stats-number">' . $daysRemaining . '</div>',
    $html
);

// E. Correction du progress bar
$html = preg_replace(
    '/<div class="progress-bar bg-success" style="width: \d+%"><\/div>/',
    '<div class="progress-bar bg-success" style="width: ' . $profileScore . '%"></div>',
    $html
);

// 10. METTRE À JOUR LE BADGE "Mes candidatures"
$html = preg_replace_callback(
    '/(<a[^>]*>.*?Mes candidatures.*?<span class="badge[^>]*>)(\d+)(<\/span>)/s',
    function($matches) use ($totalApplications) {
        return $matches[1] . $totalApplications . $matches[3];
    },
    $html
);

// 11. METTRE À JOUR LE BADGE "Mon CV" AVEC LE NOMBRE DE CVs
$html = preg_replace_callback(
    '/(<a[^>]*>.*?Mon CV.*?<span class="badge[^>]*>)([^<]*)(<\/span>)/s',
    function($matches) use ($cvsCount) {
        if (strpos($matches[2], 'AI') !== false) {
            return $matches[1] . $cvsCount . ' CV(s)' . $matches[3];
        }
        return $matches[1] . $cvsCount . $matches[3];
    },
    $html
);

// 12. CHANGER LE LIEN "Mon CV" POUR POINTER VERS upload_cv.php
$html = preg_replace_callback(
    '/(<a[^>]*href=")([^"]*)("[^>]*>.*?Mon CV.*?<\/a>)/s',
    function($matches) {
        return $matches[1] . 'upload_cv.php' . $matches[3];
    },
    $html
);

// 13. CHANGER LE LIEN "Mon profil" POUR POINTER VERS mon_profil_etudiant.php
$html = preg_replace_callback(
    '/(<a[^>]*href=")([^"]*)("[^>]*>.*?Mon profil.*?<\/a>)/s',
    function($matches) {
        return $matches[1] . 'mon_profil_etudiant.php' . $matches[3];
    },
    $html
);

// 14. CHANGER LE LIEN "Carte interactive" POUR POINTER VERS map.php
$html = preg_replace_callback(
    '/(<a[^>]*href=")([^"]*)("[^>]*>\s*<i[^>]*bi-map[^>]*>.*?Carte.*?<\/a>)/s',
    function($matches) {
        return $matches[1] . 'map.php' . $matches[3];
    },
    $html
);

// 15. SUPPRIMER LE LIEN "TABLEAU DE BORD" DU MENU (étudiant)
$html = preg_replace_callback(
    '/(\s*<a[^>]*href="[^"]*"[^>]*>\s*<i[^>]*bi-speedometer2[^>]*>.*?Tableau de bord.*?<\/a>\s*)/s',
    function($matches) {
        return '';
    },
    $html
);

// 16. CORRIGER LE BOUTON "DÉCONNEXION" (étudiant)
$html = preg_replace_callback(
    '/(<button[^>]*class="[^"]*btn-outline-light[^"]*"[^>]*>\s*<i[^>]*bi-box-arrow-right[^>]*>.*?Déconnexion.*?<\/button>)/s',
    function($matches) {
        return '<a href="logout.php" class="btn btn-outline-light w-100">' .
               '<i class="bi bi-box-arrow-right me-2"></i>' .
               'Déconnexion' .
               '</a>';
    },
    $html
);

// 17. CORRIGER "VOIR TOUT" - VERSION SIMPLE ET PRÉCISE
$html = str_replace(
    '<a href="#" class="btn btn-outline-primary">Voir tout</a>',
    '<a href="mes_candidatures.php" class="btn btn-outline-primary">Voir tout</a>',
    $html
);

// 18. REMPLACER LE TABLEAU DES CANDIDATURES RÉCENTES
if (!empty($recentApplications)) {
    $tableRows = '';
    foreach ($recentApplications as $app) {
        $statusBadge = match($app['status']) {
            'pending' => '<span class="badge bg-warning">En attente</span>',
            'accepted' => '<span class="badge bg-success">Accepté</span>',
            'rejected' => '<span class="badge bg-danger">Refusé</span>',
            default => '<span class="badge bg-secondary">' . $app['status'] . '</span>'
        };
        
        $tableRows .= '
        <tr>
            <td>' . htmlspecialchars($app['title']) . '</td>
            <td>' . htmlspecialchars($app['company_name']) . '</td>
            <td>' . date('d/m/Y', strtotime($app['sent_at'])) . '</td>
            <td>' . $statusBadge . '</td>
            <td>
                <a href="candidature_detail.php?id=' . $app['id'] . '" class="btn btn-sm btn-outline-primary">
                    Détails
                </a>
            </td>
        </tr>';
    }
    
    $html = preg_replace(
        '/(<tbody>).*?(<\/tbody>)/s',
        '$1' . $tableRows . '$2',
        $html,
        1
    );
} else {
    $html = preg_replace(
        '/(<tbody>).*?(<\/tbody>)/s',
        '<tr><td colspan="5" class="text-center">Aucune candidature pour le moment</td></tr>',
        $html,
        1
    );
}

// === DÉBUT : RÉPARATION URGENTE DES OFFRES RECOMMANDÉES ===
// Solution SIMPLE et DIRECTE pour les offres recommandées

// ====== CORRECTION DES OFFRES RECOMMANDÉES ======
// REMPLACEZ TOUTE LA SECTION 19 (offres recommandées) par ceci :

echo "<!-- DEBUG: Section offres recommandées - Début -->";

if (!empty($recommendedOffers)) {
    $recommendedSection = '';
    $counter = 0;
    
    foreach ($recommendedOffers as $offer) {
        $counter++;
        
        // Déterminer le badge selon la position
        $badgeType = '';
        $badgeText = '';
        if ($counter === 1) {
            $badgeType = 'badge-new';
            $badgeText = 'Nouveau';
        } elseif ($counter === 2) {
            $badgeType = 'badge-urgent';
            $badgeText = 'Urgent';
        } elseif ($counter === 3) {
            $badgeType = 'badge-remote';
            $badgeText = 'Remote';
        }
        
        $recommendedSection .= '
                <div class="col-xl-4 col-md-6">
                    <div class="offer-card">
                        <div class="offer-card-header">
                            <div class="d-flex justify-content-between align-items-start">
                                ' . ($badgeText ? '<span class="' . $badgeType . ' badge">' . $badgeText . '</span>' : '') . '
                                <i class="bi bi-bookmark fs-4 text-white-50"></i>
                            </div>
                            <h4 class="text-white mt-3">' . htmlspecialchars($offer['title']) . '</h4>
                            <p class="text-white-50 mb-0">' . htmlspecialchars($offer['company_name']) . ' • ' . htmlspecialchars($offer['city']) . '</p>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">
                                ' . htmlspecialchars(substr($offer['description'], 0, 120)) . '...
                            </p>
                            <div class="d-flex gap-2 mb-3">
                                <span class="badge bg-light text-dark">' . $offer['duration'] . ' mois</span>
                                <span class="badge bg-light text-dark">' . htmlspecialchars($offer['salary']) . ' MAD</span>
                                <span class="badge bg-light text-dark">' . htmlspecialchars($offer['contract_type'] ?? 'Stage') . '</span>
                            </div>
                            <a href="offers.php?id=' . $offer['id'] . '" class="btn btn-primary w-100">
                                Voir les détails
                            </a>
                        </div>
                    </div>
                </div>';
    }
    
    echo "<!-- DEBUG: Offres construites, count = " . $counter . " -->";
    
    // CORRECTION CRITIQUE : On doit cibler la DEUXIÈME occurrence de .row.g-4.mb-5
    // qui vient APRÈS "Offres recommandées"
    
    // Méthode 1 : Trouver la position exacte
    $startMarker = '<h3 class="section-title mb-4">Offres recommandées</h3>';
    $startPos = strpos($html, $startMarker);
    
    if ($startPos !== false) {
        echo "<!-- DEBUG: Start marker trouvé à position: " . $startPos . " -->";
        
        // Trouver le prochain <div class="row g-4 mb-5"> APRÈS le titre
        $rowMarker = '<div class="row g-4 mb-5">';
        $rowStartPos = strpos($html, $rowMarker, $startPos);
        
        if ($rowStartPos !== false) {
            echo "<!-- DEBUG: Row marker trouvé à position: " . $rowStartPos . " -->";
            
            // Trouver la fin de cette section row
            // On cherche la fermeture qui correspond
            $tempHtml = substr($html, $rowStartPos);
            $openDivs = 0;
            $closePos = 0;
            
            for ($i = 0; $i < strlen($tempHtml); $i++) {
                if (substr($tempHtml, $i, 6) === '<div c') {
                    $openDivs++;
                } elseif (substr($tempHtml, $i, 6) === '</div>') {
                    $openDivs--;
                    if ($openDivs === 0) {
                        $closePos = $i + 6; // Inclure </div>
                        break;
                    }
                }
            }
            
            if ($closePos > 0) {
                $endPos = $rowStartPos + $closePos;
                $oldSection = substr($html, $rowStartPos, $endPos - $rowStartPos);
                $newSection = $rowMarker . $recommendedSection . '</div>';
                
                $html = substr_replace($html, $newSection, $rowStartPos, strlen($oldSection));
                echo "<!-- DEBUG: Section remplacée avec succès -->";
            } else {
                echo "<!-- DEBUG: Impossible de trouver la fin de la section -->";
                // Fallback : méthode simple
                $html = preg_replace(
                    '/(<h3 class="section-title mb-4">Offres recommandées<\/h3>\s*<div class="row g-4 mb-5">).*?(<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>)/s',
                    '$1' . $recommendedSection . '$2',
                    $html,
                    1
                );
            }
        }
    }
    
} else {
    echo "<!-- DEBUG: Aucune offre recommandée -->";
    // Même logique pour le cas "aucune offre"
    $noOffersSection = '
                <div class="col-12">
                    <div class="alert alert-info">Aucune offre recommandée pour le moment.</div>
                </div>';
    
    $html = preg_replace(
        '/(<h3 class="section-title mb-4">Offres recommandées<\/h3>\s*<div class="row g-4 mb-5">).*?(<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>)/s',
        '$1' . $noOffersSection . '$2',
        $html,
        1
    );
}

echo "<!-- DEBUG: Section offres recommandées - Fin -->";
// 20. CORRIGER TOUS LES LIENS
$html = str_replace('href="../backend/pages/offers.php"', 'href="offers.php"', $html);
$html = str_replace('href="../backend/pages/mes_candidatures.php"', 'href="mes_candidatures.php"', $html);
$html = str_replace('href="dashboard_student.html"', 'href="dashboard_student.php"', $html);

// Chemins CSS/JS
$html = str_replace('href="css/', 'href="/interneasy/frontend/css/', $html);
$html = str_replace('src="js/', 'src="/interneasy/frontend/js/', $html);



// 22. RENDRE LA BARRE DE RECHERCHE FONCTIONNELLE - VERSION CORRIGÉE
$searchBarForm = '
<form method="GET" action="offers.php" class="search-form">
    <div class="row g-3">
        <div class="col-lg-3 col-md-6">
            <input type="text" 
                   name="search" 
                   class="form-control" 
                   placeholder="Métier, compétence ou entreprise...">
        </div>
        <div class="col-lg-2 col-md-6">
            <select name="city" class="form-control">
                <option value="">Toutes les villes</option>
                <option value="Casablanca">Casablanca</option>
                <option value="Rabat">Rabat</option>
                <option value="Marrakech">Marrakech</option>
                <option value="Agadir">Agadir</option>
                <option value="Tanger">Tanger</option>
            </select>
        </div>
        <div class="col-lg-2 col-md-6">
            <select name="domain" class="form-control">
                <option value="">Tous les domaines</option>
                <option value="Tech & IT">Tech & IT</option>
                <option value="Marketing">Marketing</option>
                <option value="Finance">Finance</option>
                <option value="Design">Design</option>
                <option value="Commerce">Commerce</option>
            </select>
        </div>
        <div class="col-lg-2 col-md-6">
            <select name="duration" class="form-control">
                <option value="">Toutes durées</option>
                <option value="1-3">1-3 mois</option>
                <option value="3-6">3-6 mois</option>
                <option value="6+">6+ mois</option>
            </select>
        </div>
        <div class="col-lg-3 col-md-12">
            <button type="submit" class="search-btn w-100">
                <i class="bi bi-search me-2"></i>
                Rechercher
            </button>
        </div>
    </div>
</form>';

// SOLUTION SIMPLE : Remplacer toute la div search-bar
$searchBarStart = '<div class="search-bar mb-5">';
$searchBarEnd = '</div>';

// Trouver la position de la barre de recherche
$startPos = strpos($html, $searchBarStart);
if ($startPos !== false) {
    // Trouver la fin de la div
    $tempHtml = substr($html, $startPos);
    $divDepth = 0;
    $endPos = 0;
    
    for ($i = 0; $i < strlen($tempHtml); $i++) {
        if (substr($tempHtml, $i, 5) === '<div ') {
            $divDepth++;
        } elseif (substr($tempHtml, $i, 6) === '</div>') {
            $divDepth--;
            if ($divDepth === 0) {
                $endPos = $i + 6;
                break;
            }
        }
    }
    
    if ($endPos > 0) {
        // Extraire l'ancienne barre de recherche
        $oldSearchBar = substr($html, $startPos, $endPos);
        
        // Créer la nouvelle barre de recherche
        $newSearchBar = '<div class="search-bar mb-5">
            <h5 class="mb-4">Rechercher un stage</h5>' . $searchBarForm . '
        </div>';
        
        // Remplacer
        $html = str_replace($oldSearchBar, $newSearchBar, $html);
        echo "<!-- SUCCESS: Barre de recherche remplacée -->";
    } else {
        echo "<!-- ERROR: Impossible de trouver la fin de la barre de recherche -->";
        // Fallback : méthode regex simple
        $html = preg_replace(
            '/(<div class="search-bar mb-5">.*?<\/div>.*?<\/div>.*?<\/div>.*?<\/div>.*?<\/div>.*?<\/div>.*?<\/div>)/s',
            '<div class="search-bar mb-5">
                <h5 class="mb-4">Rechercher un stage</h5>' . $searchBarForm . '
            </div>',
            $html,
            1
        );
    }
} else {
    echo "<!-- ERROR: Barre de recherche non trouvée -->";
}

// TEST
if (strpos($html, $initials) !== false) {
    echo "<!-- SUCCESS: Initiales remplacées -->";
}

// Afficher le HTML final
echo $html;
?>