---
title: DEV NOTE
icon: heroicon-o-document-text
order: 99
---

# 💶 Module Paie

## 📌 Vue d'ensemble du Module
Le module **Paie** centralise l'édition et le calcul des bulletins de salaire (Fiches de Paie), la gestion des acomptes, des profils de cotisations, ainsi que les exports financiers (SEPA, OD Comptables) et sociaux (DADS / DSN). Il fonctionne en symbiose avec le module RH pour l'intégration des heures et primes.

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/Paie` & `app/Enums/Paie`)
*   **Bulletin & Cotisations** : `Payslip`, `PayslipLine`, `PayrollContributionProfile`, `PayrollContributionRate` (supporte le versionnement historique avec `valid_from` et `valid_to`).
*   **Avances** : `AdvancePayment`.
*   **Suivi DSN** : `DsnSubmission`, `DsnSubmissionLine` (traçabilité complète des exportations DSN).
*   **Enums strictes** : `PayslipStatus`, `AdvancePaymentStatus`, `AdvancePaymentType`, `ContributionBaseFormula`, `DsnStatus` (ready/exported/submitted/accepted/rejected), `DsnSubmissionStatus` (draft/exported/submitted/partial/accepted/rejected).

### 2. Logique Métier & Services (`app/Services/Paie`)
*   **Moteur de Calcul** : `PayrollCalculationService` s'occupe de générer les lignes du bulletin. Il effectue la réintégration fiscale dynamique (ajout de la part patronale mutuelle/prévoyance à la base CSG/CRDS et au Net imposable). 
*   **Liaison RH automatisée** : Le moteur lit les pointages validés (`TimeEntry`) pour détecter les heures supplémentaires (majoration 25%), les primes de panier (selon les jours d'atelier) et les grands déplacements (Net non soumis). Il s'interface également profondément avec le module RH pour déduire automatiquement les jours d'absence (maladie, Congés Payés) sur le bulletin.
    *   **Gestion des Absences & Subrogation (CC Bâtiment)** : Gère automatiquement les délais de carence spécifiques (ex: 3j pour Ouvrier, 0j pour AT ou ETAM/Cadres avec ancienneté) pour calculer le Maintien de Salaire Conventionnel. Gère l'intégration des IJSS Brutes soumises uniquement à la CSG/CRDS en cas de subrogation.
*   **Exports & Compatibilité** :
    *   `SepaExportService` : Génère le fichier de virement bancaire groupé (`pain.001.001.03`).
    *   `DsnExportService` : Exporte les données au format `.csv` pour la déclaration sociale nominative (DADS/DSN). Enrichi avec colonnes supplémentaires (Date_Naissance, SIRET). Intègre un suivi complet via `DsnSubmission` (statut, dates, totaux). Méthode `generateForAccountant()` qui crée l'enregistrement de suivi. L'appelant (actions Filament) envoie la notification après l'appel.
    *   `AccountingExportService` : Génère l'OD Comptable (journal de paie en partie double : 641100, 421000, 431000...).
    *   `PayslipPdfService` : Génération du PDF avec cumuls annuels dynamiques.
*   **Coffre-Fort Numérique** : `DigiposteService` intégré via l'API Okapi (La Poste). Création automatique du coffre-fort (CPA) à la fin de l'onboarding et dépôt légal des bulletins (job asynchrone).
*   **Clôture** : `PayslipLockService` verrouille les pointages et les bulletins en fin de mois.
*   **Simulateur** : `PayrollSimulatorService` offre un outil Brut -> Net ou Net -> Brut pour prévoir le coût employeur.

### 3. Interface Utilisateur (Filament & Espace Salarié)
*   **Gestion (RH/Admin)** :
    *   Formulaires intelligents (le choix de l'employé pré-remplit les taux depuis son contrat).
    *   Infolists ultra-détaillées avec grille des cotisations et synthèse Brut/Net.
    *   Génération en masse asynchrone des fiches de paie.
    *   **Suivi des Exports DSN** : Ressource `DsnSubmissionResource` avec tableau des soumissions DSN (statut, date, totaux, fichier). Actions bulk pour exporter la paie et marquer comme prête pour DSN.
    *   **Dashboard Gestion Sociale (Widgets Avancés)** : Intégration de `laboiteacode/filament-dashboard-widgets` pour afficher la variance de la masse salariale, la structure des coûts (Composition), l'avancement de la campagne de paie (Goal) et le suivi des acomptes (Detail List).
*   **Espace Salarié** : Panel `/salarie` où l'employé peut consulter ses fiches validées/payées. Un Job asynchrone notifie l'employé (Push + Email) lorsque la fiche est publiée.

### 4. Tests et Seeders
*   Un seeder ultra-précis (`PayrollContributionProfileSeeder`) implémente de véritables profils multi-conventions collectives : **"Bâtiment (ETAM)"**, **"Bâtiment (Ouvriers)"** et **"Cadres"** avec les lignes de cotisations officielles (Santé, AT, Retraite, OPP BTP, Congés, etc.) pour compléter l'offre.

## 🚧 Ce qu'il reste à faire
*   Le module paie de base est d'ores et déjà entièrement opérationnel, incluant des fonctionnalités comptables avancées et le suivi DSN.

*   **Télétransmission DSN Automatique (API Machine-to-Machine)** : Connexion à l'API URSSAF/Net-Entreprises pour télétransmettre la DSN et récupérer les accusés de réception (CRM) directement depuis l'ERP sans manipuler de fichiers CSV. *(Futur livrable — nécessite accès M2M URSSAF)*
