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
*   **Simulateur** : `PayrollSimulatorService` offre un outil Brut -> Net ou Net -> Brut pour prévoir le coût employeur.

### 3. Interface Utilisateur (Filament & Espace Salarié)
*   **Gestion (RH/Admin)** :
    *   Formulaires intelligents (le choix de l'employé pré-remplit les taux depuis son contrat).
    *   Infolists ultra-détaillées avec grille des cotisations et synthèse Brut/Net.
    *   Génération en masse asynchrone des fiches de paie.
    *   **Dashboard Gestion Sociale (Widgets Avancés)** : Intégration de `laboiteacode/filament-dashboard-widgets` pour afficher la variance de la masse salariale, la structure des coûts (Composition), l'avancement de la campagne de paie (Goal) et le suivi des acomptes (Detail List).
*   **Espace Salarié** : Panel `/salarie` où l'employé peut consulter ses fiches validées/payées. Un Job asynchrone notifie l'employé (Push + Email) lorsque la fiche est publiée.

### 4. Tests et Seeders
*   Un seeder ultra-précis (`PayrollContributionProfileSeeder`) implémente un véritable profil **"Bâtiment (ETAM)"** avec les 15 lignes de cotisations officielles (Santé, AT, Retraite, OPP BTP, Congés, etc.).

## 🚧 Ce qu'il reste à faire
*   Le module paie de base est d'ores et déjà entièrement opérationnel, incluant des fonctionnalités comptables avancées.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Coffre-Fort Numérique Sécurisé (Digiposte / eDoc)** : Intégrer une API pour déposer automatiquement et légalement (pendant 50 ans) les bulletins de salaire dans le coffre-fort numérique (CPA) de chaque employé, remplaçant la simple vue PDF.
*   **Télétransmission DSN Automatique (API Machine-to-Machine)** : Connexion à l'API URSSAF/Net-Entreprises pour télétransmettre la DSN et récupérer les accusés de réception (CRM) directement depuis l'ERP sans manipuler de fichiers CSV.
*   **Gestion de la Subrogation et du Maintien de Salaire (IJSS)** : Intégrer les règles de maintien de salaire de la Convention Collective du BTP et déduire/verser les Indemnités Journalières de Sécurité Sociale (IJSS) en cas d'arrêt longue maladie.
*   **Gestion des Congés Payés et Absences** : Interfacer encore plus profondément avec le module RH pour déduire automatiquement les jours d'absence (maladie, CP) sur le bulletin.
*   **Multi-Conventions Collectives** : Créer (via Seeders) les profils de cotisations officiels pour les "Ouvriers" et "Cadres" afin de compléter l'offre (le système supporte déjà techniquement ces variations).
