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

## Ce qu'il reste à faire
- **Imputation Analytique** :
  - Implémenter la répartition proportionnelle du coût d'amortissement de l'actif sur le(s) chantier(s) affecté(s) lors du passage de la dotation (Objet de la V1).
- **Frontend / UX** :
  - Finir de stabiliser les tests Frontend de la ressource `FixedAssetResource` (correction des middlewares et schémas Filament V5) et s'assurer que l'UI réponde aux standards premium exigés (micro-animations, design vibrant).
  - Ajouter des filtres avancés sur les tables Filament pour rechercher par mois d'acquisition, par type ou par chantier.
- **Lien Comptable** :
  - Gérer l'export comptable (FEC / FEC Pro) ou l'intégration des journaux d'amortissement vers la comptabilité générale.

## Proposition de nouvelles fonctionnalités ou d'amélioration
- **Code-Barres / QR Codes pour le Suivi d'Inventaire** :
  - Générer un QR Code pour chaque actif immobilisé à la création, imprimable et collable sur le matériel.
  - Utilisation d'un scanneur ou de la caméra du téléphone via l'application pour réaliser des inventaires physiques annuels rapidement.
- **Maintenance et Réparations liées aux Actifs** :
  - Lier les factures d'entretien (Module Commerce) à une immobilisation pour avoir un coût total de possession (TCO) par équipement.
  - Créer des alertes pour le renouvellement du matériel en fin de durée d'amortissement ou lorsque le coût d'entretien dépasse la valeur VNC.
- **Alertes et Notifications Automatisées** :
  - Notifier l'administrateur ou l'expert-comptable lorsqu'une session d'amortissement automatique a échoué, ou à l'approche de la clôture de l'exercice pour validation.
- **Révision d'Amortissement** :
  - Permettre la réévaluation ou dépréciation anticipée d'un actif (suite à une détérioration ou une obsolescence rapide) et le recalcul automatique du tableau d'amortissement.
