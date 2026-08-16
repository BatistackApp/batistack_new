---
title: DEV NOTE
icon: heroicon-o-document-text
order: 99
---

# 🔧 Module Interventions (SAV et Dépannages)

## 📌 Vue d'ensemble du Module
Le module **Interventions** permet la gestion complète du service après-vente (SAV), des dépannages et de la maintenance chez les clients. Il offre un espace dédié pour les techniciens sur le terrain et se synchronise parfaitement avec les stocks et la facturation.

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/Interventions` & `app/Enums/Interventions`)
*   **Modèles** : `Intervention`, `InterventionWorker` (techniciens assignés), `InterventionMaterial` (pièces détachées et consommables utilisés), `ClientEquipment` (matériel client pour le suivi prédictif).
*   **Enums** : `InterventionStatus` (Planifiée, En cours, Terminée, etc.) et `InterventionType` (Régie, Forfait).

### 2. Logique Métier & Services (`app/Services/Interventions`)
*   **Costing & Rentabilité (`InterventionCostingService`)** : Calcul précis de la rentabilité prenant en charge les interventions "Forfaitaires" (prix fixe) et en "Régie" (facturation selon le matériel utilisé et le temps passé).
*   **Gestion des Stocks (`InterventionStockService`)** : Déstockage automatique des pièces détachées (`StockMouvementService`) relié à l'entrepôt ou au camion du technicien lorsque l'intervention bascule au statut "Terminée".
*   **Facturation (`InterventionBillingService`)** : Génération automatique d'une facture client brouillon (`CustomerInvoice`) détaillée (TVA standard, responsable assigné) à la clôture de l'intervention.
*   **Documents & Signatures (`InterventionPdfService`)** : Génération d'un "Bon de Travail" PDF (sans prix) avec apposition automatique du certificat de signature cryptographique via Puppeteer/Browsershot.
*   **Maintenance Prédictive (`PredictiveMaintenanceService`)** : Analyse de la fréquence des pannes (MTBF) sur le matériel client (`ClientEquipment`) et proposition proactive de contrats de maintenance sous forme de devis brouillon (`CustomerQuote`).

### 3. Observers & Événements (`app/Observers/Interventions`)
*   **`InterventionObserver`** : Génération automatique des références (ex: INT-YYYY-001) et correction de l'assignation automatique de la `company_id`.
*   **Notifications** : Alertes programmées (`InterventionScheduledNotification`) via Database et WebPush pour avertir les techniciens et les clients.

### 4. Interface Utilisateur (Filament)
*   **Espace Administrateur** : Panel complet pour la gestion, l'édition, le suivi du déstockage et la génération de factures (via PanelSwitch).
*   **Espace Technicien SAV** : Panel dédié et sécurisé (`/technicien`) protégé par le middleware `EnsureUserIsTechnician`. L'interface est simplifiée, restreinte aux interventions assignées ("Planifiée" ou supérieur). Les données financières y sont masquées en lecture seule.
*   **Signature Client** : Intégration du composant de signature électronique (`filament-autograph`) directement en action sur la table. Toute modification ultérieure invalide automatiquement la signature (génération d'un Token UUID pour scellement cryptographique).
*   **QR Code Matériel** : Intégration d'un scanner permettant d'ajouter rapidement des pièces détachées directement depuis le camion via un smartphone.
*   **Dashboard SAV (Widgets Avancés)** : Intégration de `laboiteacode/filament-dashboard-widgets` pour afficher la rentabilité du SAV (Variance), le respect des SLA (Goal), l'entonnoir des interventions (Funnel) et les alertes urgentes (Detail List).

### 5. Portail Client SAV (Espace Client)
*   **Sécurisation** : Accès restreint via `EnsureUserIsCustomer` et scope strict `auth()->user()->contact` pour s'assurer que le client ne voit que ses données.
*   **Mes Équipements (`ClientEquipmentResource`)** : Affichage en lecture seule du parc matériel (marque, numéro de série).
*   **Déclaration de panne** : Action intégrée au tableau de bord permettant au client de déclarer une panne directement, créant instantanément une `Intervention` (`SOUMIS`, `REGIE`) sans avoir à contacter le standard.
*   **Suivi des demandes (`InterventionResource`)** : Espace permettant au client de suivre le statut et l'avancement de ses tickets SAV signalés.

### 6. Formulaires d'Intervention Dynamiques (Check-lists sur-mesure)
*   **Modèle** : `InterventionReportTemplate` (`app/Models/Interventions/InterventionReportTemplate.php`) — `name`, `description`, `intervention_type` (enum `InterventionType`), `schema` (JSON : liste de blocs `{type, data}`), `is_active`, relation `interventions()`. Table `intervention_report_templates` (migration `2026_08_16_000000`).
*   **Schéma** : blocs de champs — `text_input`, `textarea`, `number` (min/max), `checkbox`, `select` (options séparées par saut de ligne), `date`, `file_upload` (disque `public`, dossier `interventions/reports`). Nom technique validé par regex `^[a-z_][a-z0-9_]*$` ; drapeau `required` par bloc.
*   **Colonnes `interventions`** : `report_template_id` (FK `nullOnDelete`) et `report_data` (JSON, cast `array`) — migration `2026_08_16_000001`. Relation `reportTemplate()` + helper `applicableReportTemplate()` : renvoie le modèle lié s'il est actif, sinon le plus récent modèle **actif** du type de l'intervention, sinon `null`.
*   **Ressource back-office** : `InterventionReportTemplateResource` (groupe « Configuration », `app/Filament/Interventions/Resources/InterventionReportTemplates/`) — `Schemas/InterventionReportTemplateForm.php` (Builder de blocs), `Tables/InterventionReportTemplatesTable.php` (badge type, compteur de questions, filtre type), pages List/Create/Edit. Policy `InterventionReportTemplatePolicy` (permissions `*:InterventionReportTemplate`, ajoutées au `super_admin` dans `ShieldSeeder`).
*   **Saisie technicien** : `FillInterventionReportPage` (`app/Filament/Technicien/Pages/`, `$shouldRegisterNavigation = false`, trait `InteractsWithForms`, `statePath('data')`) rendu dynamique des blocs, prefill depuis `report_data`, `submit()` persiste `report_template_id` + `report_data`. Vue `resources/views/filament/technicien/pages/fill-intervention-report-page.blade.php`. Action table « Remplir le rapport » (`InterventionsTable`, visible si un modèle actif existe pour le type, redirect via query-param `intervention_id`).
*   **Validation à la clôture** : `InterventionManagementService::assertReportComplete()` — vérifie que tous les champs `required` du modèle applicable sont remplis (`report_data`), sinon lance `\DomainException` listant les libellés manquants ; appelée en tête de `completeIntervention()` (le catch de l'action bulk `change_status` affiche le message). `isEmptyValue()` traite `null`/`''`/`[]`/`false`. Sans modèle actif → aucune contrainte.
*   **Tests** (`tests/Feature/Modules/Interventions/`) : `InterventionReportTemplateTest` (casts, `applicableReportTemplate` — type/lié/inactif/aucun, clôture bloquée/acceptée, checkbox non cochée, bypass inactif/sans modèle) et `FillInterventionReportPageTest` (rendu des 7 blocs, submit persist + lien modèle, rejet champ obligatoire manquant).

### 7. Contrats d'Entretien Récurrents (Maintenance Préventive)
*   **Modèles** : `MaintenanceContract` (référence `MC-AAAA-NNNN`, fréquence, prix forfaitaire, échéances, soft deletes) et `MaintenanceContractReminder` (journal de déduplication avec contrainte unique `contract_id + due_date + days_before`).
*   **Enums** : `MaintenanceContractFrequency` (Mensuelle / Trimestrielle / Semestrielle / Annuelle) et `MaintenanceContractStatus` (Actif / Pause / Terminé / Annulé).
*   **`MaintenanceContractObserver`** : Génération atomique des références `MC-AAAA-NNNN`.
*   **`MaintenanceContractService`** : `generateDueInterventions()` (contrats actifs échus, transaction + `lockForUpdate`, avancement de l'échéance, passage en `COMPLETED` si dépassement de la fin), `generateForContract($force)` (action « Générer maintenant »), `computeNextDueDate()` et `notifyUpcoming()` (rappels J-30/J-15/J-7 configurés dans `config/interventions.php`, envoyés à l'e-mail du contact principal via `MaintenanceContractReminderNotification`, dédupliqués par le journal).
*   **Commandes planifiées** (`routes/console.php`, Europe/Paris, `withoutOverlapping`) : `interventions:generate-maintenance` (06:00) et `interventions:remind-maintenance` (07:00), avec paramètre `--date` pour les tests.
*   **Interface** : `MaintenanceContractResource` (groupe « Maintenance préventive »), formulaire avec filtrage de l'équipement par client (`third_party_id` live), actions « Générer maintenant », « Pause / Reprendre », « Annuler », et `InterventionsRelationManager` listant les interventions du contrat.
*   **Tests** : `MaintenanceContractTest` couvre la génération (idempotence, fréquence, fin de contrat, pause), la déduplication des rappels, l'envoi au contact principal et le cycle soft delete de l'observer.

### 8. Tests
*   Couverture robuste avec PestPHP. L'intégralité de la logique métier (gestion, facturation, maintenance prédictive, optimisation d'itinéraire), du déstockage automatique, de la facturation, des signatures, et des contraintes d'intégrité passe avec succès (100% de réussite). Les composants mineurs et les observers sont également couverts.

## 🚧 Ce qu'il reste à faire
*   Le socle initial du module est terminé et opérationnel, y compris avec les signatures cryptographiques et le portail client SAV.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités

