---
title: DEV NOTE
icon: heroicon-o-document-text
order: 99
---

# 🧊 Module Vision 3D (BIM)

**Date** : 28 Juillet 2026
**Statut** : Module de base fonctionnel et intégré

---

## ✅ Ce qui a été fait

- **Architecture Polymorphique** : Création du modèle `BimModel` (`modelable`), permettant d'attacher une maquette 3D à un Chantier, ou à l'avenir à un Matériel, une Intervention, etc.
- **Stockage Sécurisé** : Implémentation de `BimStorageService` garantissant le filtrage d'extensions sécurisé (`ifc, dxf, gltf, glb, obj, stl`) et le nettoyage du disque physique lors des suppressions.
- **Intégration Frontend / Moteur 3D** :
  - Mise en place d'un composant AlpineJS / Blade dédié (`bim-viewer.blade.php`).
  - Intégration de `web-ifc-viewer` (via compilation Wasm) pour le BIM (IFC).
  - Intégration de `three.js` + `dxf-parser` pour la lecture des plans AutoCAD 2D/3D (DXF).
  - Système de bascule automatique du parseur selon le type de fichier.
- **Interface Utilisateur (Filament)** :
  - Création de la ressource indépendante `BimModelResource`.
  - Intégration transparente dans la vue d'un Chantier via `BimModelsRelationManager` (onglet dédié).
- **Système de Punaises (Annotations Interactives)** :
  - Base de données : Création du modèle `BimAnnotation` pour stocker les coordonnées X,Y,Z.
  - UI 3D : Bouton "Mode Annotation" transformant le curseur en crosshair, interceptant le clic sur la géométrie du modèle (Raycasting).
  - Affichage : Création de `BimAnnotationsRelationManager` pour la liste CRUD des punaises.
  - **Interactivité Live & Polymorphisme** : Les punaises peuvent être liées de manière polymorphique à une `ChantierTask` ou à une `Intervention`. Au survol (Raycasting), un Tooltip affiche les informations de la tâche. Au clic, une fenêtre modale native Filament s'ouvre pour afficher les détails complets de la tâche associée.
- **Tests** : 
  - Rédaction des tests PestPHP (`BimModelTest.php`).
  - Validation de la chaîne de téléchargement, attachement polymorphique, et purge disque (100% Passed).

---

## 🛒 Quantitatifs (BOM) & Génération de Commandes (Issue #278)

**Date** : 15 Août 2026

Passerelle Vision 3D → Articles → Achats pour générer des commandes d'achat depuis les quantitatifs d'une maquette (v1 : saisie manuelle, extraction IFC automatique prévue plus tard).

- **Modèle de données** :
  - Migration `2026_08_15_100000_create_bim_quantities_table.php` → table `bim_quantities` (`bim_model_id`, `item_id`, `element_name`, `unit`, `quantity_required` `decimal(12,4)`).
  - Modèle `App\Models\Vision3D\BimQuantity` (relations `bimModel`/`item`, cast `decimal:4`).
  - Relation `BimModel::quantities()` (hasMany). Factories `BimModelFactory` & `BimQuantityFactory`.
- **Logique métier** : `App\Services\Articles\BomProcurementService`
  - `resolveRequirements(BimModel)` : regroupe les `BimQuantity` par article, calcule le besoin net (`besoin brut − stock physique − stock en commande` via `Stock` et `PurchaseOrderItem` sur PO non clôturés), et ne retourne que les ruptures > 0.
  - `generatePurchaseOrders(BimModel)` : crée/met à jour (`updateOrCreate` sur référence `PO-BIM-{date}-{supplier}`) des `PurchaseOrder` brouillons groupés par fournisseur, rattachés au chantier de la maquette (`resolveChantierId`), et recalcule les totaux HT/TTC. Articles sans fournisseur ignorés avec `Log::warning`.
- **Interface Filament** :
  - `BimQuantitiesRelationManager` (onglet « Quantitatifs (BOM) » sur `BimModelResource`) : CRUD des lignes (Select article, élément, unité, quantité requise `minValue(0.01)`).
  - Action « Générer le bon de commande » sur `ViewBimModel` : modal de récap (vue `filament.pages.bim-procurement-recap`) puis redirection vers le `PurchaseOrderResource` (panel `commerce`).
- **Tests** : `tests/Feature/Modules/Articles/BomProcurementServiceTest.php` (8 tests : regroupement par article, déduction stock physique/commandé, exclusion des articles couverts, regroupement par fournisseur, totaux HT/TTC, idempotence, articles sans fournisseur).
- **Non couvert (v1)** : extraction automatique des quantités IFC et génération d'une `PurchaseRequest` (liste de courses).

---

- **Mesure de distances sur la maquette** : Clic point A / Clic point B pour calculer et afficher la distance réelle entre eux.
- **Système de calques (Layers) IFC / DXF** : Arborescence permettant de cacher/afficher des couches spécifiques (par exemple : cacher les murs, afficher la tuyauterie).
- **Cacher temporairement un élément 3D** : Capacité de double cliquer sur un élément puis d'appuyer sur "Suppr" pour le rendre transparent/invisible pendant l'inspection.
- **Support complet de three-dxf** : Mappage fidèle des couleurs, épaisseurs de traits, et textes des plans AutoCAD 2D.
- **Miniature (Thumbnail) automatique** : Snapshot PNG généré en arrière-plan à l'upload pour illustrer le tableau Filament.
- **Intégration Réalité Augmentée (AR / WebXR)** : Implémentation du support WebXR avec l'API Hit-Test native. Permet d'afficher un réticule sur les surfaces détectées par la caméra d'un smartphone et de projeter la maquette IFC dans l'environnement réel (échelle 1:1) de manière fluide et autonome par rapport à la boucle de rendu de base.
- **Comparaison de Révisions BIM (Version Control 3D)** : Possibilité d'uploader une nouvelle version (enfant) d'une maquette IFC existante (parent) et de calculer dynamiquement le différentiel géométrique en 3D dans le navigateur (basé sur le GlobalId). Colorisation automatique (Vert = Ajouté, Orange = Modifié, Rouge fantôme = Supprimé) et toggle UI pour masquer les éléments supprimés.
- **Détection Automatique de Collisions (Clash Detection)** : Implémentation d'un algorithme rapide de détection des collisions basé sur les boîtes englobantes (AABB). L'utilisateur sélectionne deux calques spécifiques (Types IFC, ex: Murs vs Tuyauterie) à comparer. L'UI prévisualise les intersections détectées avec des sphères violettes et permet la création en lot d'annotations `BimAnnotation` aux points de conflit via une action Filament dédiée.

---

## ⏳ Ce qu'il reste à faire (Next Steps)

- **Camera Focus au Clic** : Implémenter une fonction dans le Viewer 3D permettant de "Zoomer" ou de déplacer la caméra directement sur une punaise (annotation) cliquée depuis le tableau Filament (via un événement Livewire propagé à AlpineJS).
- **Optimisation des chargements de gros fichiers IFC** : Le moteur `web-ifc` peut être gourmand. Il faudra éventuellement configurer un web worker (Multithreading) si de très gros modèles sont uploadés pour ne pas figer l'interface le temps du parsing géométrique.

---

## 💡 Idées d'améliorations et Nouvelles Fonctionnalités
*   **Historique complet des révisions BIM** : Pouvoir remonter de V3 à V1.
*   **Filtres de collisions plus fins** : Permettre de filtrer la détection de collisions par système ou zone spatiale plutôt que simplement par calque entier.
