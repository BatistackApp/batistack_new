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

- **Mesure de distances sur la maquette** : Clic point A / Clic point B pour calculer et afficher la distance réelle entre eux.
- **Système de calques (Layers) IFC / DXF** : Arborescence permettant de cacher/afficher des couches spécifiques (par exemple : cacher les murs, afficher la tuyauterie).
- **Cacher temporairement un élément 3D** : Capacité de double cliquer sur un élément puis d'appuyer sur "Suppr" pour le rendre transparent/invisible pendant l'inspection.
- **Support complet de three-dxf** : Mappage fidèle des couleurs, épaisseurs de traits, et textes des plans AutoCAD 2D.
- **Miniature (Thumbnail) automatique** : Snapshot PNG généré en arrière-plan à l'upload pour illustrer le tableau Filament.

---

## ⏳ Ce qu'il reste à faire (Next Steps)

- **Camera Focus au Clic** : Implémenter une fonction dans le Viewer 3D permettant de "Zoomer" ou de déplacer la caméra directement sur une punaise (annotation) cliquée depuis le tableau Filament (via un événement Livewire propagé à AlpineJS).
- **Optimisation des chargements de gros fichiers IFC** : Le moteur `web-ifc` peut être gourmand. Il faudra éventuellement configurer un web worker (Multithreading) si de très gros modèles sont uploadés pour ne pas figer l'interface le temps du parsing géométrique.

---

## 💡 Idées d'améliorations et Nouvelles Fonctionnalités
*   **Intégration Réalité Augmentée (AR / WebXR)** : Permettre au chef de chantier de superposer la maquette 3D sur le monde réel via la caméra de sa tablette (WebXR), facilitant le repérage des réseaux invisibles derrière les murs.
*   **Comparaison de Révisions BIM (Version Control 3D)** : Permettre l'upload d'une nouvelle version (V2) d'une maquette existante et générer un affichage colorimétrique des changements (Vert = Ajouté, Rouge = Supprimé, Orange = Modifié).
*   **Détection Automatique de Collisions (Clash Detection)** : Scanner la maquette à la recherche d'intersections géométriques illogiques entre les corps d'état (ex: tuyau traversant une poutre) et générer automatiquement des punaises rouges d'alerte.
