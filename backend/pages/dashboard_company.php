<?php
// dashboard_company.php - VERSION AVEC LIEN CARTE AJOUTÉ
session_start();

if (!isset($_SESSION['company_id']) || $_SESSION['user_type'] !== 'company') {
    header('Location: login_ultimate.php');
    exit();
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../app/core/Database.php';

$database = Database::getInstance();
$db = $database->getConnection();

$yourCompanyId = $_SESSION['company_id'];
$companyName = $_SESSION['company_name'] ?? 'Entreprise';

// Statistiques - CORRIGÉ : utiliser $totalApplications au lieu de $candidatureCount
$countQuery = "SELECT COUNT(*) as count FROM applications a JOIN offers o ON a.offer_id = o.id WHERE o.company_id = ?";
$countStmt = $db->prepare($countQuery);
$countStmt->execute([$yourCompanyId]);
$candidatureCount = $countStmt->fetch()['count'];

// === AJOUTEZ CES LIGNES ICI ===

// Récupérer info complète entreprise
$companyQuery = "SELECT * FROM companies WHERE id = ?";
$companyStmt = $db->prepare($companyQuery);
$companyStmt->execute([$_SESSION['company_id']]);
$company = $companyStmt->fetch();

// Initiales entreprise
$companyInitials = strtoupper(substr($company['name'], 0, 2));

// Info secteur/ville
$companyInfo = '';
if (!empty($company['activity_sector'])) {
    $companyInfo .= htmlspecialchars($company['activity_sector']);
}
if (!empty($company['city'])) {
    $companyInfo .= ($companyInfo ? ' • ' : '') . htmlspecialchars($company['city']);
}

// Statistiques dynamiques
// 1. Offres actives
$activeOffersQuery = "SELECT COUNT(*) as count FROM offers WHERE company_id = ? AND status = 'active'";
$activeStmt = $db->prepare($activeOffersQuery);
$activeStmt->execute([$yourCompanyId]);
$activeOffers = $activeStmt->fetch()['count'];

// 2. Candidatures totales POUR CETTE ENTREPRISE
$totalAppsQuery = "SELECT COUNT(*) as count FROM applications a JOIN offers o ON a.offer_id = o.id WHERE o.company_id = ?";
$totalStmt = $db->prepare($totalAppsQuery);
$totalStmt->execute([$yourCompanyId]);
$totalApplications = $totalStmt->fetch()['count'];

// 3. Entretiens programmés
$interviewsQuery = "SELECT COUNT(*) as count FROM applications a JOIN offers o ON a.offer_id = o.id WHERE o.company_id = ? AND a.interview_date IS NOT NULL AND a.interview_date >= CURDATE()";
$interviewsStmt = $db->prepare($interviewsQuery);
$interviewsStmt->execute([$yourCompanyId]);
$interviewsCount = $interviewsStmt->fetch()['count'];

// 4. Taux de réponse
$respondedQuery = "SELECT COUNT(*) as count FROM applications a JOIN offers o ON a.offer_id = o.id WHERE o.company_id = ? AND a.status IN ('accepted', 'rejected')";
$respondedStmt = $db->prepare($respondedQuery);
$respondedStmt->execute([$yourCompanyId]);
$respondedCount = $respondedStmt->fetch()['count'];

$responseRate = $totalApplications > 0 ? round(($respondedCount / $totalApplications) * 100, 1) : 0;

// 5. Liste réelle des offres
$offersListQuery = "SELECT o.*, COUNT(a.id) as application_count FROM offers o LEFT JOIN applications a ON o.id = a.offer_id WHERE o.company_id = ? GROUP BY o.id ORDER BY o.created_at DESC LIMIT 5";
$offersListStmt = $db->prepare($offersListQuery);
$offersListStmt->execute([$yourCompanyId]);
$offersList = $offersListStmt->fetchAll();

// 6. Candidatures récentes
$recentAppsQuery = "SELECT a.*, s.first_name, s.last_name, s.email, o.title FROM applications a JOIN students s ON a.student_id = s.id JOIN offers o ON a.offer_id = o.id WHERE o.company_id = ? ORDER BY a.sent_at DESC LIMIT 3";
$recentAppsStmt = $db->prepare($recentAppsQuery);
$recentAppsStmt->execute([$yourCompanyId]);
$recentApplications = $recentAppsStmt->fetchAll();
// === REQUÊTES POUR LES STATS MENSUELLES ===

// Offres créées ce mois
$monthlyOffersQuery = "SELECT COUNT(*) as count FROM offers WHERE company_id = ? AND status = 'active' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
$monthlyOffersStmt = $db->prepare($monthlyOffersQuery);
$monthlyOffersStmt->execute([$yourCompanyId]);
$monthlyOffers = $monthlyOffersStmt->fetch()['count'];

// Candidatures cette semaine
$weeklyAppsQuery = "SELECT COUNT(*) as count FROM applications a JOIN offers o ON a.offer_id = o.id WHERE o.company_id = ? AND WEEK(a.sent_at) = WEEK(CURDATE()) AND YEAR(a.sent_at) = YEAR(CURDATE())";
$weeklyAppsStmt = $db->prepare($weeklyAppsQuery);
$weeklyAppsStmt->execute([$yourCompanyId]);
$weeklyApps = $weeklyAppsStmt->fetch()['count'];

// Entretiens cette semaine
$weeklyInterviewsQuery = "SELECT COUNT(*) as count FROM applications a JOIN offers o ON a.offer_id = o.id WHERE o.company_id = ? AND a.interview_date IS NOT NULL AND WEEK(a.interview_date) = WEEK(CURDATE()) AND YEAR(a.interview_date) = YEAR(CURDATE())";
$weeklyInterviewsStmt = $db->prepare($weeklyInterviewsQuery);
$weeklyInterviewsStmt->execute([$yourCompanyId]);
$weeklyInterviews = $weeklyInterviewsStmt->fetch()['count'];

// === REQUÊTES POUR STATISTIQUES GÉNÉRALES ===

// Temps de réponse moyen (en jours) - Version simplifiée
$avgResponseTimeQuery = "SELECT 
    AVG(DATEDIFF(CURDATE(), a.sent_at)) as avg_days 
    FROM applications a 
    JOIN offers o ON a.offer_id = o.id 
    WHERE o.company_id = ? 
    AND a.status IN ('accepted', 'rejected')";

$avgResponseStmt = $db->prepare($avgResponseTimeQuery);
$avgResponseStmt->execute([$yourCompanyId]);
$avgResponseResult = $avgResponseStmt->fetch();
$avgResponseTime = $avgResponseResult['avg_days'] ?? null;
$avgResponseTimeFormatted = ($avgResponseTime && $avgResponseTime > 0) ? round($avgResponseTime, 1) . 'j' : 'N/A';

// Taux de conversion (candidatures acceptées / total)
$conversionQuery = "SELECT 
    (SELECT COUNT(*) FROM applications a JOIN offers o ON a.offer_id = o.id WHERE o.company_id = ? AND a.status = 'accepted') as accepted,
    (SELECT COUNT(*) FROM applications a JOIN offers o ON a.offer_id = o.id WHERE o.company_id = ?) as total";
$conversionStmt = $db->prepare($conversionQuery);
$conversionStmt->execute([$yourCompanyId, $yourCompanyId]);
$conversionData = $conversionStmt->fetch();
$conversionRate = $conversionData['total'] > 0 ? round(($conversionData['accepted'] / $conversionData['total']) * 100) : 0;

// Note moyenne (vérifier si la table existe d'abord)
try {
    $avgRatingQuery = "SELECT AVG(r.rating) as avg_rating FROM company_ratings r WHERE r.company_id = ?";
    $avgRatingStmt = $db->prepare($avgRatingQuery);
    $avgRatingStmt->execute([$yourCompanyId]);
    $avgRatingResult = $avgRatingStmt->fetch();
    $avgRating = $avgRatingResult['avg_rating'] ?? null;
    $avgRatingFormatted = $avgRating ? round($avgRating, 1) : 'N/A';
} catch (Exception $e) {
    // Si la table n'existe pas, utiliser une valeur par défaut
    $avgRatingFormatted = 'N/A';
}

// === FIN DE L'AJOUT ===


// Charger le template
$htmlFile = dirname(__DIR__, 2) . '/frontend/dashboard_company.html';
$html = file_get_contents($htmlFile);

// ========== CORRECTIONS UNIQUES POUR RENDRE DYNAMIQUE ==========

// 1. Remplacer le nom de l'entreprise "TechMaroc SARL" par le vrai nom
$realCompanyName = htmlspecialchars($company['name'] ?? $companyName);
$html = str_replace('TechMaroc SARL', $realCompanyName, $html);
$html = str_replace('Tech Innovations SARL', $realCompanyName, $html);

// 2. Remplacer "Technologie • Casablanca" par les vraies données
if (!empty($companyInfo)) {
    $html = str_replace('Technologie • Casablanca', $companyInfo, $html);
    $html = str_replace('Commerce • Casablanca', $companyInfo, $html);
}

// 3. Remplacer le badge "5" (offres) par le vrai nombre
$html = preg_replace_callback(
    '/(Mes offres.*?<span[^>]*class="[^"]*badge[^"]*"[^>]*>)\d+(<\/span>)/i',
    function($matches) use ($activeOffers) {
        return $matches[1] . $activeOffers . $matches[2];
    },
    $html
);

// 4. Remplacer le badge "12" (candidatures) par le VRAI nombre POUR CETTE ENTREPRISE
$html = preg_replace_callback(
    '/(Candidatures.*?<span[^>]*class="[^"]*badge[^"]*"[^>]*>)\d+(<\/span>)/i',
    function($matches) use ($totalApplications) {
        return $matches[1] . $totalApplications . $matches[2];
    },
    $html
);

// 5. Remplacer l'avatar icône par les initiales
$html = preg_replace(
    '/<div class="user-avatar mx-auto mb-3">\s*<i class="bi bi-building"><\/i>\s*<\/div>/',
    '<div class="user-avatar mx-auto mb-3">' . $companyInitials . '</div>',
    $html
);

// 6. Remplacer aussi l'avatar en haut à droite
$html = preg_replace(
    '/<div class="user-avatar">\s*<i class="bi bi-building"><\/i>\s*<\/div>/',
    '<div class="user-avatar">' . $companyInitials . '</div>',
    $html
);

// ========== FIN DES CORRECTIONS DYNAMIQUES ==========
// === APPROCHE SIMPLE POUR LES 4 STATISTIQUES ===

// 1. Offres actives
$html = str_replace(
    '<div class="stats-number">5</div>',
    '<div class="stats-number">' . $activeOffers . '</div>',
    $html
);

// 2. Candidatures totales
$html = str_replace(
    '<div class="stats-number">47</div>',
    '<div class="stats-number">' . $totalApplications . '</div>',
    $html
);

// 3. Entretiens programmés
$html = str_replace(
    '<div class="stats-number">8</div>',
    '<div class="stats-number">' . $interviewsCount . '</div>',
    $html
);

// 4. Taux de réponse
$html = str_replace(
    '<div class="stats-number">92%</div>',
    '<div class="stats-number">' . $responseRate . '%</div>',
    $html
);

// 5. Barre de progression
$html = str_replace(
    'style="width: 92%"',
    'style="width: ' . $responseRate . '%"',
    $html
);

// === RENDRE DYNAMIQUE LA TABLE DES OFFRES ===

// Créer le HTML dynamique pour les offres
$dynamicOffersHTML = '';

if (!empty($offersList)) {
    foreach ($offersList as $offer) {
        // Déterminer le statut
        $statusClass = '';
        $statusText = '';
        
        if ($offer['status'] === 'active') {
            $statusClass = 'badge-active';
            $statusText = 'Active';
        } elseif ($offer['status'] === 'pending') {
            $statusClass = 'badge-pending';
            $statusText = 'En attente';
        } else {
            $statusClass = 'badge-closed';
            $statusText = 'Expirée';
        }
        
        // Formater la date
        $createdDate = date('d/m/Y', strtotime($offer['created_at']));
        
        // Déterminer le type (stage ou alternance)
        $type = $offer['contract_type'] ?? 'Stage';
        $duration = $offer['duration'] ?? '6 mois';
        
        $dynamicOffersHTML .= '
        <tr>
            <td>
                <strong>' . htmlspecialchars($offer['title']) . '</strong><br>
                <small class="text-muted">Publié le ' . $createdDate . '</small>
            </td>
            <td>' . htmlspecialchars($type) . ' • ' . htmlspecialchars($duration) . '</td>
            <td>
                <span class="badge bg-primary">' . $offer['application_count'] . '</span>
            </td>
            <td>
                <span class="badge-status ' . $statusClass . '">' . $statusText . '</span>
            </td>
            <td>
                <div class="d-flex gap-2">
                    <a href="/interneasy/backend/pages/offer_detail.php?id=' . $offer['id'] . '"  class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="post_offer.php?edit=' . $offer['id'] . '" class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-pencil"></i>
                    </a>
                </div>
            </td>
        </tr>';
    }
} else {
    $dynamicOffersHTML = '
    <tr>
        <td colspan="5" class="text-center py-4">
            <div class="text-muted">
                <i class="bi bi-briefcase" style="font-size: 2rem;"></i>
                <p class="mt-2">Aucune offre publiée</p>
                <a href="post_offer.php" class="btn btn-primary btn-sm mt-2">Créer une offre</a>
            </div>
        </td>
    </tr>';
}

// Remplacer le contenu statique du tableau par le contenu dynamique
$html = preg_replace_callback(
    '/<tbody>\s*(.*?)\s*<\/tbody>/s',
    function($matches) use ($dynamicOffersHTML) {
        return '<tbody>' . $dynamicOffersHTML . '</tbody>';
    },
    $html
);

// === RENDRE DYNAMIQUE LA SECTION PERFORMANCE DES OFFRES ===

$dynamicPerformanceHTML = '';
$maxApplications = !empty($offersList) ? max(array_column($offersList, 'application_count')) : 0;

if (!empty($offersList)) {
    foreach ($offersList as $offer) {
        $percentage = $maxApplications > 0 ? round(($offer['application_count'] / $maxApplications) * 100) : 0;
        
        $dynamicPerformanceHTML .= '
        <div>
            <div class="d-flex justify-content-between mb-1">
                <span>' . htmlspecialchars($offer['title']) . '</span>
                <span>' . $offer['application_count'] . ' candidature' . ($offer['application_count'] > 1 ? 's' : '') . '</span>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-primary" style="width: ' . $percentage . '%"></div>
            </div>
        </div>';
    }
} else {
    $dynamicPerformanceHTML = '
    <div class="text-center py-3 text-muted">
        <i class="bi bi-graph-up" style="font-size: 2rem;"></i>
        <p class="mt-2">Aucune donnée de performance</p>
    </div>';
}

// Remplacer la section "Performance des offres"
$html = preg_replace_callback(
    '/<h6>Performance des offres<\/h6>\s*<div class="d-flex flex-column gap-3 mt-3">\s*(.*?)\s*<\/div>/s',
    function($matches) use ($dynamicPerformanceHTML) {
        return '<h6>Performance des offres</h6>
                <div class="d-flex flex-column gap-3 mt-3">' . $dynamicPerformanceHTML . '</div>';
    },
    $html
);

// === RENDRE DYNAMIQUE LES CANDIDATURES RÉCENTES ===

$dynamicRecentAppsHTML = '';

if (!empty($recentApplications)) {
    foreach ($recentApplications as $app) {
        // Initiales de l'étudiant
        $initials = strtoupper(substr($app['first_name'], 0, 1) . substr($app['last_name'], 0, 1));
        
        // Formatage date
        $sentDate = date('d/m/Y', strtotime($app['sent_at']));
        $timeAgo = '';
        
        // Calcul "Il y a X temps"
        $now = new DateTime();
        $sentTime = new DateTime($app['sent_at']);
        $interval = $now->diff($sentTime);
        
        if ($interval->days == 0) {
            if ($interval->h == 0) {
                $timeAgo = 'Il y a ' . $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');
            } else {
                $timeAgo = 'Il y a ' . $interval->h . ' heure' . ($interval->h > 1 ? 's' : '');
            }
        } elseif ($interval->days == 1) {
            $timeAgo = 'Hier';
        } else {
            $timeAgo = 'Il y a ' . $interval->days . ' jour' . ($interval->days > 1 ? 's' : '');
        }
        
        // Déterminer le badge de statut
        $statusBadge = '';
        switch ($app['status']) {
            case 'pending':
                $statusBadge = '<span class="badge bg-warning">Nouveau</span>';
                break;
            case 'reviewed':
                $statusBadge = '<span class="badge bg-info">En cours</span>';
                break;
            case 'accepted':
                $statusBadge = '<span class="badge bg-success">Accepté</span>';
                break;
            case 'rejected':
                $statusBadge = '<span class="badge bg-danger">Rejeté</span>';
                break;
            case 'contacted':
                $statusBadge = '<span class="badge bg-success">Contacté</span>';
                break;
            default:
                $statusBadge = '<span class="badge bg-secondary">' . $app['status'] . '</span>';
        }
        
        $dynamicRecentAppsHTML .= '
        <div class="application-card">
            <div class="d-flex align-items-start gap-3">
                <div class="user-avatar">
                    ' . $initials . '
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-1">' . htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) . '</h6>
                    <p class="text-muted mb-2">' . htmlspecialchars($app['title']) . '</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">' . $timeAgo . '</small>
                        ' . $statusBadge . '
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <a href="/interneasy/backend/pages/candidature_detail_company.php?id=' . $app['id'] . '" class="btn btn-sm btn-outline-primary flex-fill">
                    <i class="bi bi-eye me-1"></i>Voir détails
                </a>
                <button class="btn btn-sm btn-success flex-fill" onclick="contactCandidate(' . $app['student_id'] . ')">
                    <i class="bi bi-envelope me-1"></i>Contacter
                </button>
            </div>
        </div>';
    }
} else {
    $dynamicRecentAppsHTML = '
    <div class="text-center py-4 text-muted">
        <i class="bi bi-people" style="font-size: 2rem;"></i>
        <p class="mt-2">Aucune candidature récente</p>
    </div>';
}

// Remplacer la section des candidatures récentes
$html = preg_replace_callback(
    '/<div class="d-flex flex-column gap-3">\s*(.*?)\s*<\/div>\s*<div class="text-center mt-3">/s',
    function($matches) use ($dynamicRecentAppsHTML) {
        return '<div class="d-flex flex-column gap-3">' . $dynamicRecentAppsHTML . '</div>
                <div class="text-center mt-3">';
    },
    $html
);

// === RENDRE DYNAMIQUE LES STATISTIQUES GÉNÉRALES ===

$html = str_replace(
    '<div class="fs-4 text-primary">92%</div>',
    '<div class="fs-4 text-primary">' . $responseRate . '%</div>',
    $html
);

$html = str_replace(
    '<div class="fs-4 text-success">3.2j</div>',
    '<div class="fs-4 text-success">' . $avgResponseTimeFormatted . '</div>',
    $html
);

$html = str_replace(
    '<div class="fs-4 text-warning">35%</div>',
    '<div class="fs-4 text-warning">' . $conversionRate . '%</div>',
    $html
);

$html = str_replace(
    '<div class="fs-4 text-info">4.8</div>',
    '<div class="fs-4 text-info">' . $avgRatingFormatted . '</div>',
    $html
);

// REMPLACEMENTS SIMPLES - CORRIGÉ : utiliser $totalApplications (candidatures pour cette entreprise)
// au lieu de $candidatureCount (toutes les candidatures)
$html = str_replace('>12</span>', '>' . $totalApplications . '</span>', $html);

// VERSION PRÉCISE - cible exactement les badges dans les liens de navigation
$html = preg_replace_callback(
    '/(<a[^>]*href="[^"]*"[^>]*>.*?Mes offres.*?<span class="badge bg-primary ms-auto">)\d+(<\/span>.*?<\/a>)/s',
    function($matches) use ($activeOffers) {
        return $matches[1] . $activeOffers . $matches[2];
    },
    $html
);

$html = preg_replace_callback(
    '/(<a[^>]*href="[^"]*"[^>]*>.*?Candidatures.*?<span class="badge bg-primary ms-auto">)\d+(<\/span>.*?<\/a>)/s',
    function($matches) use ($totalApplications) {
        return $matches[1] . $totalApplications . $matches[2];
    },
    $html
);

// SUPPRIMER LE LIEN "TABLEAU DE BORD" DU MENU (entreprise)
$html = preg_replace_callback(
    '/(\s*<a[^>]*href="[^"]*"[^>]*>\s*<i[^>]*bi-speedometer2[^>]*>.*?Tableau de bord.*?<\/a>\s*)/s',
    function($matches) {
        // Retourne une chaîne vide pour supprimer complètement l'élément
        return '';
    },
    $html
);

// CORRECTION SPÉCIFIQUE - NE PAS UTILISER str_replace GÉNÉRAL

// CORRECTION UNIQUEMENT du lien "Candidatures" dans le menu
$html = preg_replace_callback(
    '/(<a href="#"[^>]*>\s*<i class="bi bi-people"><\/i>\s*<span>Candidatures<\/span>)/s',
    function($matches) {
        return str_replace('href="#"', 'href="gerer_candidatures.php"', $matches[1]);
    },
    $html
);

$html = preg_replace_callback(
    '/(<a href="#"[^>]*>\s*<i class="bi bi-briefcase"><\/i>\s*<span>Mes offres<\/span>)/s',
    function($matches) {
        return str_replace('href="#"', 'href="mes_offres.php"', $matches[1]);
    },
    $html
);

// CORRECTION UNIQUEMENT du lien "Paramètres" dans le menu
$html = preg_replace_callback(
    '/(<a href="#"[^>]*>\s*<i class="bi bi-gear"><\/i>\s*<span>Paramètres<\/span>)/s',
    function($matches) {
        return str_replace('href="#"', 'href="parametres_company.php"', $matches[1]);
    },
    $html
);

// CORRECTION UNIQUEMENT du lien "Statistiques" dans le menu
$html = preg_replace_callback(
    '/(<a href="#"[^>]*>\s*<i class="bi bi-bar-chart"><\/i>\s*<span>Statistiques<\/span>)/s',
    function($matches) {
        return str_replace('href="#"', 'href="stats_company.php"', $matches[1]);
    },
    $html
);

// CORRECTION UNIQUEMENT du bouton "Voir toutes les candidatures" dans le dashboard
$html = preg_replace_callback(
    '/(<a href="#"[^>]*class="btn btn-outline-primary w-100"[^>]*>\s*Voir toutes les candidatures\s*<\/a>)/s',
    function($matches) {
        return str_replace('href="#"', 'href="gerer_candidatures.php"', $matches[1]);
    },
    $html
);

// AJOUTER LE LIEN "Mon profil" MANUELLEMENT
$menu_pattern = '/<nav[^>]*id="sidebar"[^>]*>.*?<ul[^>]*class="[^"]*nav[^"]*"[^>]*>(.*?)<\/ul>.*?<\/nav>/s';
if (preg_match($menu_pattern, $html, $matches)) {
    $menu_content = $matches[1];
    
    $new_menu_content = preg_replace(
        '/(<\/li>\s*)(<\/ul>)/s',
        '$1' . 
        '<li class="nav-item">' .
        '<a href="mon_profil_entreprise.php" class="nav-link">' .
        '<i class="bi bi-person"></i>' .
        '<span>Mon profil</span>' .
        '</a>' .
        '</li>' .
        '$2',
        $menu_content
    );
    
    $html = str_replace($menu_content, $new_menu_content, $html);
}

// Corriger chemins
$html = str_replace('href="css/', 'href="/interneasy/frontend/css/', $html);
$html = str_replace('src="js/', 'src="/interneasy/frontend/js/', $html);

// CORRIGER DÉCONNEXION COMPANY - VERSION SIMPLE
$html = str_replace(
    '<button class="btn btn-outline-light w-100">',
    '<a href="logout.php" class="btn btn-outline-light w-100">',
    $html
);
// Mettre à jour "+2 ce mois"
$html = str_replace(
    '<small class="text-success"><i class="bi bi-arrow-up"></i> +2 ce mois</small>',
    '<small class="text-success"><i class="bi bi-arrow-up"></i> +' . $monthlyOffers . ' ce mois</small>',
    $html
);

// Mettre à jour "+12 cette semaine"
$html = str_replace(
    '<small class="text-muted">+12 cette semaine</small>',
    '<small class="text-muted">+' . $weeklyApps . ' cette semaine</small>',
    $html
);

// Mettre à jour "3 cette semaine"
$html = str_replace(
    '<small class="text-muted">3 cette semaine</small>',
    '<small class="text-muted">' . $weeklyInterviews . ' cette semaine</small>',
    $html
);

// Remplacer seulement le PREMIER </button> (celui de la déconnexion)
$pos = strpos($html, '</button>');
if ($pos !== false) {
    $html = substr_replace($html, '</a>', $pos, 9); // 9 = longueur de '</button>'
}

// ========== SUPPRIMEZ CE BLOC (lignes 93-100) ==========
// VÉRIFIER/CRÉER le lien "Carte des entreprises"
if (strpos($html, 'Carte des entreprises') === false) {
    // Chercher où ajouter dans le menu (après "Candidatures")
    $html = preg_replace_callback(
        '/(<a[^>]*href="gerer_candidatures.php"[^>]*>.*?Candidatures.*?<\/a>.*?<\/li>)/s',
        function($matches) {
            return $matches[0] . "\n" . 
                '<li class="nav-item">' .
                '<a href="map.php" class="nav-link">' .
                '<i class="bi bi-geo-alt"></i>' .
                '<span>Carte des entreprises</span>' .
                '</a>' .
                '</li>';
        },
        $html
    );
}
// ========== FIN DE SUPPRESSION ==========

// DEBUG - MONTRE LES VRAIES DONNÉES
echo "<!-- DEBUG: Nom entreprise réel = " . $realCompanyName . " -->";
echo "<!-- DEBUG: Info secteur/ville = " . $companyInfo . " -->";
echo "<!-- DEBUG: Initiales = " . $companyInitials . " -->";
echo "<!-- DEBUG: Offres actives = " . $activeOffers . " -->";
echo "<!-- DEBUG: Candidatures pour CETTE entreprise = " . $totalApplications . " -->";
echo "<!-- DEBUG: Toutes candidatures (global) = " . $candidatureCount . " -->";
echo "<!-- DEBUG: Nombre d'offres = " . count($offersList) . " -->";
echo "<!-- DEBUG: Nombre candidatures récentes = " . count($recentApplications) . " -->";
echo "<!-- DEBUG: Temps réponse moyen = " . $avgResponseTimeFormatted . " -->";
echo "<!-- DEBUG: Taux conversion = " . $conversionRate . "% -->";
echo "<!-- DEBUG: Note moyenne = " . $avgRatingFormatted . " -->";

echo $html;
?>