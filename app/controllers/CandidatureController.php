<?php
require_once __DIR__ . '/../models/Candidature.php';
require_once __DIR__ . '/../models/Offre.php';

class CandidatureController {

    private function check($role) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
            die("Accès refusé");
        }
    }

    // =========================
    // POSTULER À UNE OFFRE
    // =========================
    public function postuler() {
        $this->check('etudiant');

        $idOffre = $_GET['idOffre'];
        $idEtudiant = $_SESSION['id'];

        // Vérifier que l'offre existe
        $offre = Offre::getById($idOffre);
        if (!$offre) {
            die("Offre inexistante.");
        }

        // Vérifier que l'étudiant n'a pas déjà postulé
        if (Candidature::existeDeja($idEtudiant, $idOffre)) {
            echo "Vous avez déjà postulé à cette offre.";
            return;
        }

        Candidature::postuler($idEtudiant, $idOffre);
        echo "Candidature envoyée avec succès.";
    }

    // =========================
    // HISTORIQUE DES CANDIDATURES (ÉTUDIANT)
    // =========================
    public function mesCandidatures() {
        $this->check('etudiant');

        $cands = Candidature::getByEtudiant($_SESSION['id']);
        if (count($cands) === 0) {
            echo "Aucune candidature envoyée.";
            return;
        }

        foreach ($cands as $c) {
            echo $c['titre'] . " - Statut : " . $c['statut'] . "<br>";
        }
    }

    // =========================
    // CANDIDATURES REÇUES (ENTREPRISE)
    // =========================
    public function candidaturesEntreprise() {
        $this->check('entreprise');

        $cands = Candidature::getByEntreprise($_SESSION['id']);
        if (count($cands) === 0) {
            echo "Aucune candidature reçue.";
            return;
        }

        foreach ($cands as $c) {
            echo $c['nom']." ".$c['prenom']." - ".$c['email']." - ".$c['titre']." - ".$c['statut']." |
            <a href='index.php?controller=Candidature&action=accepter&id=".$c['id']."'>Accepter</a> |
            <a href='index.php?controller=Candidature&action=refuser&id=".$c['id']."'>Refuser</a><br>";
        }
    }

    // =========================
    // ACCEPTER UNE CANDIDATURE
    // =========================
    public function accepter() {
        $this->check('entreprise');

        Candidature::updateStatut($_GET['id'], 'acceptee');
        echo "Candidature acceptée.";
    }

    // =========================
    // REFUSER UNE CANDIDATURE
    // =========================
    public function refuser() {
        $this->check('entreprise');

        Candidature::updateStatut($_GET['id'], 'refusee');
        echo "Candidature refusée.";
    }
}
