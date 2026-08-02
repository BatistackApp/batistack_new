# 🚜 Module Locations

## 📌 Vue d'ensemble du Module
Le module **Locations** permet de gérer l'ensemble des locations de matériel (pelles, grues, échafaudages) auprès de fournisseurs externes. Il assure le suivi des contrats, la refacturation ou l'imputation analytique sur les chantiers, et la facturation récurrente.

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/Locations` & `app/Enums/Locations`)
*   **`RentalContract` & `RentalContractLine`** : Gestion des contrats de location et détail du matériel loué (désignation, quantité, prix).
*   **Enums** : `RentalStatus` (Brouillon, Actif, Terminé) et `RentalBillingPeriod` (Journalier, Hebdomadaire, Mensuel, Annuel).

### 2. Logique Métier & Services (`app/Services/Locations`)
*   **Imputation Analytique** : `RentalCostService` calcule dynamiquement le coût analytique cumulé d'une location. Le `ChantierAnalyticService` a été modifié pour agréger en temps réel ces coûts de location dans le calcul global de la rentabilité d'un chantier.
*   **Facturation Automatisée** : `RentalBillingService` génère automatiquement une facture fournisseur brouillon (`SupplierInvoice`) basée sur la période de facturation. Intégration de la validation automatique du brouillon si le montant TTC est inférieur à 500 €.

### 3. Observers & Événements (`app/Observers/Locations`)
*   **Automatisation (Cron)** : `ProcessRecurringRentalsCommand` analyse journalièrement les contrats échus et lance la facturation périodique automatiquement.
*   **Alertes** : `RentalExpirationAlertJob` notifie de l'échéance d'une location (J-3).
*   **`RentalContractObserver`** : Assure la cohérence des statuts.

### 4. Interface Utilisateur (Filament)
*   **Panel Dédié** : Provider `LocationsPanelProvider`.
*   **Ressource** : `RentalContractResource` incluant Formulaire (avec Repeater), Tableau de bord (avec filtres avancés) et Infolist (vue détaillée) intégrant des actions rapides (Générer facture, Terminer location).
*   **Widgets** : `ActiveRentalsWidget` (KPIs) et `RentalCalendarWidget` (Calendrier interactif via `guava/calendar` pour visualiser la timeline des locations).
*   **Intégration Chantiers** : Création d'un tableau de bord unifié (`DeployedResourcesWidget`) sur la vue détaillée d'un chantier, fusionnant le matériel en propre (Immobilisations) et les locations externes.

### 5. Tests
*   Des tests unitaires/fonctionnels exhaustifs couvrent l'analytique et la génération de factures. Tous passent avec succès.

## 🚧 Ce qu'il reste à faire
*   L'ensemble du module (Backend et Frontend) est totalement fonctionnel. Aucune tâche bloquante.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Suivi Géolocalisé** : Si du gros équipement (ex: pelles, grues) est loué avec des capteurs GPS, pouvoir remonter leur position via une API externe directement sur la fiche d'information du `RentalContract`.
*   **Refonte du Dashboard Matériel Externe (Widgets Avancés)** : Intégration de `laboiteacode/filament-dashboard-widgets` pour afficher la variance des coûts de location, le statut des contrats (Segment Bar), le budget par fournisseur (Composition) et les restitutions imminentes (Detail List).
