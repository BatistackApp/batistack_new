# 💶 Module Paie

## 📌 Vue d'ensemble du Module
Le module **Paie** centralise l'édition et le calcul des bulletins de salaire (Fiches de Paie), la gestion des acomptes, des profils de cotisations, ainsi que les exports financiers (SEPA, OD Comptables) et sociaux (DADS / DSN). Il fonctionne en symbiose avec le module RH pour l'intégration des heures et primes.

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/Paie` & `app/Enums/Paie`)
*   **Bulletin & Cotisations** : `Payslip`, `PayslipLine`, `PayrollContributionProfile`, `PayrollContributionRate` (supporte le versionnement historique avec `valid_from` et `valid_to`).
*   **Avances** : `AdvancePayment`.
*   **Enums strictes** : `PayslipStatus`, `AdvancePaymentStatus`, `AdvancePaymentType`, `ContributionBaseFormula`.

### 2. Logique Métier & Services (`app/Services/Paie`)
*   **Moteur de Calcul** : `PayrollCalculationService` s'occupe de générer les lignes du bulletin. Il effectue la réintégration fiscale dynamique (ajout de la part patronale mutuelle/prévoyance à la base CSG/CRDS et au Net imposable). 
*   **Liaison RH automatisée** : Le moteur lit les pointages validés (`TimeEntry`) pour détecter les heures supplémentaires (majoration 25%), les primes de panier (selon les jours d'atelier) et les grands déplacements (Net non soumis).
*   **Exports & Compatibilité** :
    *   `SepaExportService` : Génère le fichier de virement bancaire groupé (`pain.001.001.03`).
    *   `DsnExportService` : Exporte les données au format `.csv` pour la déclaration sociale nominative (DADS/DSN).
    *   `AccountingExportService` : Génère l'OD Comptable (journal de paie en partie double : 641100, 421000, 431000...).
    *   `PayslipPdfService` : Génération du PDF avec cumuls annuels dynamiques.
*   **Clôture** : `PayslipLockService` verrouille les pointages et les bulletins en fin de mois.

### 3. Interface Utilisateur (Filament & Espace Salarié)
*   **Gestion (RH/Admin)** :
    *   Formulaires intelligents (le choix de l'employé pré-remplit les taux depuis son contrat).
    *   Infolists ultra-détaillées avec grille des cotisations et synthèse Brut/Net.
    *   Génération en masse asynchrone des fiches de paie.
*   **Espace Salarié** : Panel `/salarie` où l'employé peut consulter ses fiches validées/payées. Un Job asynchrone notifie l'employé (Push + Email) lorsque la fiche est publiée.

### 4. Tests et Seeders
*   Un seeder ultra-précis (`PayrollContributionProfileSeeder`) implémente un véritable profil **"Bâtiment (ETAM)"** avec les 15 lignes de cotisations officielles (Santé, AT, Retraite, OPP BTP, Congés, etc.).

## 🚧 Ce qu'il reste à faire
*   Le module paie de base est d'ores et déjà entièrement opérationnel, incluant des fonctionnalités comptables avancées.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Gestion des Congés Payés et Absences** : Interfacer encore plus profondément avec le module RH pour déduire automatiquement les jours d'absence (maladie, CP) sur le bulletin.
*   **Simulation de Paie** : Créer un outil Brut -> Net ou Net -> Brut pour prévoir le coût employeur d'une embauche ou d'une augmentation.
*   **Multi-Conventions Collectives** : Le système supportant déjà plusieurs profils, il suffirait de créer (seeder) les profils "Ouvriers" et "Cadres" pour compléter l'offre.
*   **Refonte du Dashboard Gestion Sociale (Widgets Avancés)** : Intégration de `laboiteacode/filament-dashboard-widgets` pour afficher la variance de la masse salariale, la structure des coûts (Composition), l'avancement de la campagne de paie (Goal) et le suivi des acomptes (Detail List).
