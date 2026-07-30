# 🏗️ DEV NOTE : Module 3D Vision (BIM)

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

## ⏳ Ce qu'il reste à faire (Next Steps)

- **Camera Focus au Clic** : Implémenter une fonction dans le Viewer 3D permettant de "Zoomer" ou de déplacer la caméra directement sur une punaise (annotation) cliquée depuis le tableau Filament (via un événement Livewire propagé à AlpineJS).
- **Optimisation des chargements de gros fichiers IFC** : Le moteur `web-ifc` peut être gourmand. Il faudra éventuellement configurer un web worker (Multithreading) si de très gros modèles sont uploadés pour ne pas figer l'interface le temps du parsing géométrique.

---

## 💡 Idées d'améliorations et Nouvelles Fonctionnalités

### À inclure sous forme de "Issues GitHub" (Backlog)
- [ ] **Feature Issue : "Mesure de distances sur la maquette"**
  > Permettre à l'utilisateur de cliquer sur deux points dans le viewer 3D pour calculer et afficher la distance réelle entre eux (très utile pour vérifier la largeur d'un mur ou d'une porte).
  
- [ ] **Feature Issue : "Système de calques (Layers) IFC / DXF"**
  > Implémenter une arborescence (Treeview) permettant de cacher/afficher des couches spécifiques (par exemple : cacher les murs, afficher la tuyauterie, etc.) en exploitant l'arbre IFC.
  
- [ ] **Feature Issue : "Cacher temporairement un élément 3D"**
  > Ajouter la capacité de double cliquer sur un élément (mur, toit) puis d'appuyer sur un bouton (ou la touche "Suppr" du clavier) pour le rendre transparent/invisible, afin de voir ce qui se cache derrière pendant l'inspection.
  
- [ ] **Feature Issue : "Support complet de three-dxf"**
  > Actuellement, le POC parse le DXF, mais un rendu complexe nécessiterait d'intégrer une librairie spécialisée pour mapper fidèlement les couleurs, épaisseurs de traits, et textes des plans AutoCAD 2D.
  
- [ ] **Enhancement Issue : "Miniature (Thumbnail) automatique"**
  > À l'upload d'un fichier 3D, demander au moteur de faire un rendu (snapshot PNG) en arrière-plan pour l'utiliser comme image de miniature dans le tableau (liste) des maquettes dans Filament.
