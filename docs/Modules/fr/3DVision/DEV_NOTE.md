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
  - Modèle `App\Models\Vision3D\BimQuantity` (relations `bimModel`/`item`, cast `decimal:4`). Validation métier `saving` : `quantity_required` strictement positive (+ contrainte `CHECK (quantity_required > 0)` en base).
  - Relation `BimModel::quantities()` (hasMany). Factories `BimModelFactory` & `BimQuantityFactory`.
- **Logique métier** : `App\Services\Articles\BomProcurementService`
  - `resolveRequirements(BimModel)` : regroupe les `BimQuantity` par article, calcule le besoin net (`besoin brut − stock physique − stock en commande` via `Stock` et `PurchaseOrderItem` sur PO non clôturés), et ne retourne que les ruptures > 0. Exclut du stock en commande le bon de commande déjà généré pour cette maquette + ce fournisseur (`orderReference()`), afin de permettre sa mise à jour sans double comptage.
  - `generatePurchaseOrders(BimModel)` : chaque bon est **unique et scopé à sa maquette** via la référence `PO-BIM-{bimModelId}-{supplierId}` (`orderReference()`), ce qui évite tout conflit entre maquettes et toute réécriture du `chantier_id` d'une autre maquette. Crée/met à jour (`updateOrCreate`) des `PurchaseOrder` brouillons groupés par fournisseur, rattachés au chantier de la maquette si présent (`resolveChantierId`, sinon `null`), **synchronise les lignes** (upsert des besoins puis suppression des lignes devenues inutiles), et recalcule les totaux HT/TTC. Articles sans fournisseur ignorés avec `Log::warning` et exposés via la clé `ignored_items` du retour.
- **Interface Filament** :
  - `BimQuantitiesRelationManager` (onglet « Quantitatifs (BOM) » sur `BimModelResource`) : CRUD des lignes (Select article, élément, unité, quantité requise `minValue(0.01)`).
  - Action « Générer le bon de commande » sur `ViewBimModel` : modal de récap (vue `filament.pages.bim-procurement-recap`) puis redirection vers le `PurchaseOrderResource` (panel `commerce`).
- **Tests** : `tests/Feature/Modules/Articles/BomProcurementServiceTest.php` (8 tests : regroupement par article, déduction stock physique/commandé, exclusion des articles couverts, regroupement par fournisseur, totaux HT/TTC, idempotence, articles sans fournisseur).
- **Non couvert (v1)** : extraction automatique des quantités IFC et génération d'une `PurchaseRequest` (liste de courses).

---

## 🎯 Viewer 3D Avancé (Août 2026 — Issues #155, #156, #157, #159, #161, #249, #250, #251)

- **Mesure de distances sur la maquette (IFC)** (#155) : Clic point A / Clic point B pour calculer et afficher la distance réelle entre eux (boutons actifs en format IFC, Escape = annuler, Suppr = supprimer une mesure).
- **Système de calques (Layers) IFC** (#156) : Arborescence spatiale IFC permettant de cacher/afficher des couches spécifiques (cacher les murs, afficher la tuyauterie). *(Pas encore géré pour les DXF.)*
- **Calques DXF** (Issue #316) : Extension du système de calques aux plans AutoCAD DXF via l'API `dxf-viewer` (`GetLayers`/`ShowLayer`). Liste plate triée (nom + pastille couleur + checkbox), bouton "Calques" disponible pour IFC **et** DXF, bouton "Tout afficher". État de session UI (non persisté). Les calques vides/frozen sont omis (`nonEmptyOnly=true`).
- **Cacher temporairement un élément 3D** (#157) : Double-clic sur un élément de la maquette IFC pour le masquer pendant l'inspection, avec bouton de restauration globale. *(La combinaison "Suppr" prévue à l'origine n'est pas implémentée.)*
- **Miniature (Thumbnail) automatique** (#159) : Infrastructure en place (`GenerateBimThumbnailJob` + vue headless + colonne `thumbnail_path`). L'observer `BimModelObserver` déclenche le job à la création et à la modification du fichier. La vignette est affichée dans le tableau `BimModelResource` (Issue #315).
- **Camera Focus au Clic** (#161) : Action "focus" sur chaque punaise du tableau → événement Livewire `focus-annotation` propagé à AlpineJS → la caméra zoome et se centre sur l'annotation (IFC et DXF).
- **Réalité Augmentée AR / WebXR** (#249) : Session `immersive-ar` avec l'API Hit-Test native — réticule sur les surfaces détectées par la caméra du smartphone, placement de la maquette IFC à l'échelle 1:1, boucle de rendu XR dédiée.
- **Comparaison de Révisions BIM (Version Control 3D)** (#250) : Upload d'une nouvelle version (enfant, `parent_id` + `version`) et calcul dynamique du différentiel géométrique dans le navigateur basé sur le GlobalId — colorisation automatique (Vert = Ajouté, Orange = Modifié, Rouge fantôme = Supprimé) et toggle UI pour masquer les éléments supprimés.
- **Détection Automatique de Collisions (Clash Detection)** (#251) : Algorithme basé sur les boîtes englobantes (AABB). Sélection de deux types IFC (ex : Murs vs Tuyauterie), prévisualisation des intersections avec des sphères violettes et création en lot d'annotations `BimAnnotation` via l'action Filament `saveClashes`.

---

## ⏳ Ce qu'il reste à faire (Next Steps)

- **Optimisation des chargements de gros fichiers IFC** : Le moteur `web-ifc` peut être gourmand. Il faudra éventuellement configurer un web worker (Multithreading) si de très gros modèles sont uploadés pour ne pas figer l'interface le temps du parsing géométrique.
- **Étendre les calques aux DXF** : ~~Ajouter le parsing et la visibilité des calques DXF~~ *(Fait — voir Issue #316)*.
- **Cacher un élément via la touche "Suppr"** : Compléter le double-clic par la combinaison prévue à l'origine (#157).
- **Mesure de distances en DXF** : Étendre la mesure point A → point B aux plans AutoCAD (actuellement réservée aux IFC).
- **Support complet de three-dxf** : Mappage fidèle des couleurs, épaisseurs de traits et textes des plans AutoCAD 2D (le rendu DXF actuel repose sur `dxf-viewer`).

---

## 💡 Idées d'améliorations et Nouvelles Fonctionnalités
*   **Historique complet des révisions BIM** : La comparaison V1 est en place ; pouvoir naviguer dans tout l'historique (V3 → V2 → V1) et comparer deux versions arbitraires.
*   **Filtres de collisions plus fins** : Permettre de filtrer la détection de collisions par système ou zone spatiale plutôt que simplement par calque entier.
