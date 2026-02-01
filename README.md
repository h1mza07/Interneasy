
# InternEasy — README

## Présentation
**InternEasy** est une application web académique destinée à faciliter la recherche de stages pour les étudiants et la gestion des offres pour les entreprises.  
Le projet est développé par **un binôme**, selon une approche **MVP**, avec PHP/MySQL et une architecture **MVC**.

---

## Objectifs du projet
- Centraliser les offres de stage
- Simplifier la candidature en ligne
- Faciliter la gestion des offres et candidatures
- Mettre en relation étudiants et entreprises

---

## Utilisateurs
- **Étudiant** : profil, recherche d’offres, candidature, CV PDF
- **Entreprise** : publication d’offres, gestion des candidatures
- **Administrateur** : validation des entreprises, gestion globale

---

## Technologies
- **Backend** : PHP 8+
- **Base de données** : MySQL
- **Frontend** : HTML5, CSS3, JavaScript
- **Framework CSS** : Bootstrap ou Tailwind
- **Carte** : Leaflet.js
- **PDF** : DOMPDF / FPDF
- **Serveur local** : XAMPP
- **Outils** : Visual Studio Code, Git, GitHub

---

## Architecture
- Modèle MVC (Model / View / Controller)
- Séparation claire backend / frontend
- Accès sécurisé par rôles

### Structure du projet
```

C:\xampp\htdocs\internEasy\
├── frontend\
├── backend\
├── uploads\
├── .htaccess
├── index.php
├── test_company.php
└── test_post_offer.php

```

---

## Organisation du travail (Binôme)

### Binôme 1: Amina Chetti - Backend
- Base de données
- Connexion PHP/MySQL
- Authentification & rôles
- Modules : Offres, Candidatures, Admin
- Sécurité (hash, sessions, requêtes préparées)

### Binôme 2 — Hamza Layachi - Frontend
- Pages principales
- Intégration Bootstrap
- Dashboards par rôle
- Carte Leaflet
- Générateur de CV PDF
- Amélioration UX/UI

---

## Méthodologie
- Approche **MVP**
- Travail collaboratif via GitHub
- Branches séparées : `backend` / `frontend`
- Tests réguliers

---

## Étapes principales
1. Installation de l’environnement (XAMPP, VS Code, Git)
2. Création du projet local et dépôt GitHub
3. Mise en place de l’architecture MVC
4. Développement Backend & Frontend en parallèle
5. Intégration carte et PDF
6. Tests, corrections et amélioration finale

---

## Sécurité
- Hash des mots de passe (bcrypt)
- Sessions sécurisées
- Validation backend
- Uploads sécurisés (≤ 5 Mo)
- Contrôle d’accès par rôle

---

## Planning (1 mois)
- **Semaine 1** : Setup, BDD, auth
- **Semaine 2** : Module entreprises
- **Semaine 3** : Module étudiants
- **Semaine 4** : Carte, tests, finalisation

---

Projet académique – usage pédagogique uniquement
```
