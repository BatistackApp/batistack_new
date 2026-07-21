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

---

## 🚧 Ce qu'il reste à faire

- **Liaison avec les Bons de Commandes (Module Commerce / Chantiers)** : Automatiser la création d'un Ordre de Fabrication lorsqu'un Chantier est validé ou lorsqu'un produit spécifique est vendu via le CRM.
- **Contrôle Qualité** : Ajouter une étape de validation qualité à la fin de la production avant l'entrée en stock définitive.
- **Traçabilité des Numéros de Série/Lots** : Affiner le tracking des lots consommés si un article de la nomenclature nécessite une traçabilité stricte.
- **Interface Opérateur** : Simplifier l'affichage (mode tablette) pour que l'ouvrier dans l'atelier n'ait qu'un gros bouton "Démarrer" et "Terminer" par OF, sans voir toute l'interface d'administration.

---

## 💡 Idées d'améliorations & Nouvelles fonctionnalités utiles

- 🏷️ **Impression d'Étiquettes (Lien avec Module Articles)**
  Générer dynamiquement un PDF contenant un Code-barres ou un QR Code à l'achèvement d'un OF. Ce QR code pourrait être collé sur le produit fini pour être ensuite scanné sur le chantier ou lors des expéditions.

- ⏱️ **Pointage Temps Réel sur OF (Lien avec Module RH)**
  Permettre aux ouvriers d'utiliser la pointeuse biométrique ou mobile en spécifiant sur quel numéro d'OF ils travaillent, afin de calculer les coûts de main d'œuvre au centime près plutôt qu'au forfait.

- 📅 **Calendrier Capacitaire (Gantt de Production)**
  Afficher une vue "Gantt" permettant de lisser la charge de production dans le temps selon les ressources disponibles (machines ou ouvriers).

- 📦 **Génération Auto de Commandes d'Achat (Lien avec Module Tiers/Commerce)**
  Si le moteur MRP détecte une rupture de stock pour fabriquer un produit, générer automatiquement un "Brouillon de Commande Fournisseur" avec les manquants.
