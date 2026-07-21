# 🏭 Note de Développement - Module GPAO (Gestion de Production Assistée par Ordinateur)

## 🎯 Vue d'Ensemble
Le module **GPAO** a pour objectif de gérer les opérations de production ou d'assemblage en atelier au sein de Batistack. Il s'intègre profondément avec l'inventaire (déstockage des matières premières et entrée des produits finis) et les ressources humaines (pointage de la main d'œuvre).

---

## ✅ Ce qui a été fait

### 1. Architecture Backend et Logique Métier
- **Modèles de données** : Création des modèles `ManufacturingOrder` (Ordres de Fabrication) et `ManufacturingRequirement` (Nomenclatures/Besoins).
- **Service MRP (Material Requirements Planning)** : Implémentation du moteur de calcul des besoins `MrpService` qui génère automatiquement les nomenclatures requises pour un OF basé sur la recette de l'article (BOM).
- **Intégration d'Inventaire** : Création du `ProductionInventoryService` pour consommer les matières premières (décrémentation des stocks) et réceptionner les produits finis de manière transactionnelle avec ajustement du PUMP (Prix Unitaire Moyen Pondéré).
- **Intégration RH (Main-d'œuvre)** : Association avec le pointage des employés pour valoriser le coût de la main-d'œuvre directe (MOD) sur l'Ordre de Fabrication.
- **Observers et Événements** : Utilisation de `ManufacturingOrderObserver` pour déclencher les actions MRP à la création et gérer le cycle de vie de l'OF de manière découplée.
- **Couverture de Tests** : 100% de réussite sur la suite de tests PestPHP (`ManufacturingOrderTest`).

### 2. Interface Utilisateur (Filament v5)
- **Ressource Dédiée** : Création de `ManufacturingOrderResource` incluant les formulaires de saisie et la table de listing de base.
- **Vue Kanban Sur-Mesure** : Implémentation complète d'un tableau Kanban interactif (Drag & Drop) en utilisant Alpine.js et Livewire, contournant l'incompatibilité des packages tiers avec Filament v5.
- **Tableau de Nomenclature (Relation Manager)** : Intégration d'un tableau permettant d'éditer manuellement les matières requises et de visualiser les quantités consommées via le `RequirementsRelationManager`.
- **Espace Dédié (Panel Switch)** : Création d'un panneau Filament spécifique `GpaoPanelProvider` ("Atelier & Production") et intégration au `PanelSwitch` dans `AppServiceProvider`.

### 3. Intégration Commerce & GPAO (Pont des Commandes)
- **Modèle relationnel** : Ajout d'une relation hiérarchique sur les Ordres de Fabrication (`parent_id` / `children`) et d'une liaison avec la commande d'origine (`customer_order_id`).
- **Génération MRP et Sous-OF** : Le Job `GenerateManufacturingOrdersJob` scrute la commande client et la nomenclature des ouvrages pour générer automatiquement et récursivement des OFs et sous-OFs.
- **Déclencheur Automatique (Observer)** : Génération des OFs déclenchée automatiquement lorsque le statut de la Commande Client passe à "Confirmé".
- **Génération et Affichage UI** : 
  - Ajout d'un bouton d'action manuelle "Générer les OF" sur le formulaire de la commande client.
  - Ajout d'un `ManufacturingOrdersRelationManager` sur la page Commande Client pour centraliser la vue de la production.
  - Affichage d'un lien direct "Commande d'origine" sur les Ordres de Fabrication pour naviguer d'un espace à l'autre.

### 4. Contrôle Qualité
- **Workflow de validation** : Ajout d'une étape "Au contrôle" (statut `QUALITY_CONTROL`) entre la fin de production et l'entrée en stock.
- **Traçabilité** : Création d'une table dédiée `QualityCheck` pour historiser l'inspecteur, la date, la décision (Validé/Refusé) et les notes.
- **UI Filament** : Ajout d'une modale "Contrôle Qualité" pour valider/refuser un OF, ainsi qu'un `QualityChecksRelationManager` pour voir l'historique sur chaque OF.

### 5. Interface Opérateur (Tablette)
- **Intégration RH** : Ajout d'un champ `access_atelier` géré depuis la fiche de l'employé pour autoriser l'accès à la production.
- **Portail Salarié** : Création d'une page simplifiée optimisée pour les tablettes (`AtelierProduction`) dans l'espace Salarié existant.
- **Fonctionnalités** :
  - Séparation entre les tâches "À faire / En cours" et "Historique".
  - Actions rapides avec de gros boutons pour "Démarrer" et "Terminer" la production.
  - Affichage direct de la nomenclature (recette) et bouton pour visualiser la documentation PDF associée au produit fini.

### 6. Génération de PDF et Étiquettes (QR Code)
- **Génération asynchrone** : Le job `GenerateManufacturingOrderPdfJob` se déclenche automatiquement lorsqu'un Ordre de Fabrication est validé (statut `COMPLETED`).
- **Media Library** : Le document PDF est rattaché à l'historique du modèle via Spatie Media Library.
- **Intégration d'un QR Code** : Le document intègre un QR Code natif contenant la référence de l'OF via `chillerlan/php-qrcode` (idéal pour le scannage logistique / sur chantier).
- **Interface de Téléchargement** :
  - Ajout d'une action de téléchargement sur l'interface d'administration.
  - Ajout d'un bouton de téléchargement "À la volée" sur l'interface tablette pour les opérateurs.

---

## 🚧 Ce qu'il reste à faire

- ⏱️ **Pointage Temps Réel sur OF (Lien avec Module RH)**
  Permettre aux ouvriers d'utiliser la pointeuse biométrique ou mobile en spécifiant sur quel numéro d'OF ils travaillent, afin de calculer les coûts de main d'œuvre au centime près plutôt qu'au forfait.

- 📅 **Calendrier Capacitaire (Gantt de Production)**
  Afficher une vue "Gantt" permettant de lisser la charge de production dans le temps selon les ressources disponibles (machines ou ouvriers).

- 📦 **Génération Auto de Commandes d'Achat (Lien avec Module Tiers/Commerce)**
  Si le moteur MRP détecte une rupture de stock pour fabriquer un produit, générer automatiquement un "Brouillon de Commande Fournisseur" avec les manquants.
