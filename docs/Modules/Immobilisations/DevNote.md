# Module Immobilisations - Dev Note

## Ce qui est fait
- **Architecture et Base de données** :
  - Création des migrations pour les catégories d'actifs (`asset_categories`), les actifs (`fixed_assets`) et le tableau d'amortissement (`depreciations`).
  - Implémentation des modèles Eloquent associés avec leurs relations (`AssetCategory`, `FixedAsset`, `Depreciation`).
  - Ajout des énumérations pour standardiser les données : `AssetType`, `AssetStatus`, `DepreciationMethod`.
- **Logique Métier (Backend)** :
  - Création du `DepreciationCalculatorService` pour le calcul automatisé des dotations selon la méthode d'amortissement (Linéaire ou Dégressive) et gestion du prorata temporis.
  - Création du `AssetDisposalService` pour gérer la cession ou le rebut des immobilisations et calculer la plus-value ou moins-value.
  - Implémentation du `FixedAssetObserver` pour générer automatiquement le tableau prévisionnel d'amortissement dès la création d'un actif.
  - Création d'une commande Artisan `immobilisations:run-depreciations` permettant de valider et clôturer les dotations à chaque fin d'exercice.
- **Tests (Backend)** :
  - Tests unitaires complets pour le `DepreciationCalculatorService` garantissant l'exactitude comptable des calculs.
  - Tests d'intégration pour le cycle de vie complet d'un `FixedAsset` et de la commande `immobilisations:run-depreciations`.
- **Interface Utilisateur (Frontend Filament)** :
  - Création du provider `ImmobilisationPanelProvider` avec un panel dédié accessible sur la route `/immobilisation`.
  - Implémentation des ressources Filament `AssetCategoryResource` et `FixedAssetResource` incluant la séparation V5 (Schemas et Tables).
  - Création de widgets pour le Dashboard (Valeur Totale des Actifs, Graphique de Prévision des Dotations).
  - Traduction complète en français de l'interface (modèles, groupes de navigation, formulaires et tableaux).
  - Ajout de filtres avancés (Catégorie, Statut, Chantier, Date d'acquisition) sur les tableaux de données.
- **Imputation Analytique (V1 atteinte)** :
  - Ajout de la clé `chantier_id` sur la table `depreciations`.
  - Modification de la commande `immobilisations:run-depreciations` pour "photographier" et conserver l'affectation du chantier au moment du passage en comptabilité.
  - Mise à jour du `ChantierAnalyticService` (Module Chantiers) pour consolider automatiquement ces coûts d'amortissement dans la marge brute des chantiers.
  - Tests d'intégration complets validant le workflow analytique (`ImputationAnalytiqueTest.php`).

- **Lien Comptable (FEC export)** :
  - Création du `FecExportService` générant un fichier TXT (tabulations) respectant les 18 colonnes de la DGFiP.
  - Déduction du compte d'amortissement (`28...`) à partir du compte de l'actif (`2...`).
  - Action Filament pour télécharger l'export FEC des amortissements de l'année.

- **Code-Barres / QR Codes pour le Suivi d'Inventaire** :
  - Génération de PDF (Plaquettes) avec QR Codes pour l'étiquetage physique du matériel.
  - Scan mobile : L'appareil photo d'un smartphone permet de valider instantanément la présence (champ `last_inventoried_at`).

- **Maintenance et Réparations des Actifs** :
  - Création du modèle `AssetMaintenance` pour logger toutes les pannes et factures de réparations des équipements.
  - Basculement automatique du statut de la machine (En réparation <-> Actif).
  - Liaison directe avec le `ChantierAnalyticService` : le coût des réparations ampute instantanément la marge financière du chantier en cours.

- **Révision / Dépréciation Exceptionnelle d'un Actif** :
  - Gestion des pertes de valeur non planifiées (sinistres, casse irréparable, obsolescence).
  - Création du modèle `AssetImpairment` pour logger la perte de valeur à une date donnée.
  - Recalcul dynamique du reste du plan d'amortissement (`DepreciationCalculatorService::recalculateSchedule`) par lissage de la nouvelle VNC sur le temps d'utilité restant de l'actif.

## Ce qu'il reste à faire
- **Améliorations futures** :
  - Ajouter la numérisation des factures fournisseurs via OCR lors de l'enregistrement de l'actif.
  - Automatiser la gestion des subventions d'investissement si nécessaire.

## Proposition de nouvelles fonctionnalités ou d'amélioration
- **Alertes et Notifications Automatisées** :
  - Notifier l'administrateur ou l'expert-comptable lorsqu'une session d'amortissement automatique a échoué, ou à l'approche de la clôture de l'exercice pour validation.
