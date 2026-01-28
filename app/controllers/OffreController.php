<?php
require_once __DIR__ . '/../models/Offre.php';

class OffreController {

    private function startSessionAndCheckEntreprise() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'entreprise') {
            die("Accès refusé : vous devez être connecté comme entreprise.");
        }
    }

    // =========================
    // CRÉATION D'OFFRE
    // =========================
    public function create() {
        $this->startSessionAndCheckEntreprise();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Offre::create(
                $_POST['titre'],
                $_POST['description'],
                $_POST['ville'],
                $_POST['domaine'],
                $_SESSION['id']
            );
            echo "Offre créée avec succès";
        }
    }

    // =========================
    // LISTE DES OFFRES DE L'ENTREPRISE
    // =========================
    public function mesOffres() {
        $this->startSessionAndCheckEntreprise();

        $offres = Offre::getByEntreprise($_SESSION['id']);

        if (count($offres) === 0) {
            echo "Aucune offre publiée.";
            return;
        }

        foreach ($offres as $o) {
            echo $o['titre'] . " - " . $o['ville'] . " |
            <a href='index.php?controller=Offre&action=edit&id={$o['id']}'>Modifier</a> |
            <a href='index.php?controller=Offre&action=delete&id={$o['id']}'>Supprimer</a><br>";
        }
    }

    // =========================
    // MODIFICATION D'OFFRE
    // =========================
    public function edit() {
        $this->startSessionAndCheckEntreprise();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Offre::update(
                $_POST['id'],
                $_POST['titre'],
                $_POST['description'],
                $_POST['ville'],
                $_POST['domaine']
            );
            echo "Offre modifiée avec succès";
        }
    }

    // =========================
    // SUPPRESSION D'OFFRE
    // =========================
    public function delete() {
        $this->startSessionAndCheckEntreprise();

        Offre::delete($_GET['id']);
        echo "Offre supprimée avec succès";
    }
}
