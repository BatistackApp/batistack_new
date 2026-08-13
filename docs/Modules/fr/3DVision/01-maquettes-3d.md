---
title: Les Maquettes 3D
icon: heroicon-o-document-plus
order: 2
---

# 🏗️ Les Maquettes 3D et Plans

Le module Vision 3D vous permet d'importer vos conceptions issues de logiciels de CAO (BIM, Autocad, Sketchup, Revit) pour les rendre accessibles à tous les collaborateurs, sans nécessiter de licence de logiciel tiers.

## 1. Importer une Maquette

Pour ajouter une maquette :
1. Allez dans le menu **Vision 3D > Maquettes 3D**.
2. Cliquez sur **Créer**.
3. Renseignez un **Nom** explicite (ex: "Bâtiment A - CVC").
4. Sélectionnez le **Format** correspondant à votre fichier.
5. Uploadez le fichier.

### Formats Supportés

L'ERP Batistack intègre un moteur de rendu universel capable de lire les formats standards de l'industrie :
*   **IFC (BIM)** : Le standard ouvert pour la maquette numérique du bâtiment (Idéal pour Revit, ArchiCAD).
*   **DXF** : Format vectoriel standard pour les plans 2D et 3D (AutoCAD).
*   **GLTF / GLB** : Formats 3D modernes, légers et optimisés pour le web.
*   **OBJ & STL** : Formats 3D bruts, très utiles pour les pièces de fabrication mécanique (GPAO).

## 2. Visualisation 3D

Une fois la maquette enregistrée, cliquez dessus pour ouvrir la vue de détails. 
Batistack affichera un **Visualiseur 3D interactif**. 

Vous pouvez utiliser votre souris (ou votre doigt sur tablette) pour :
*   **Pivoter** (Clic gauche maintenu)
*   **Déplacer / Pan** (Clic droit maintenu)
*   **Zoomer** (Molette)

> [!NOTE]  
> Le temps de chargement initial dépend de la taille (`file_size`) de votre maquette. Les modèles BIM très denses (fichiers IFC complexes) peuvent prendre quelques secondes pour s'afficher.

## 3. Versionnement et Comparaison de Révisions (IFC)

Pour les maquettes au format **IFC**, Batistack permet de gérer les différentes révisions de la maquette (versions successives livrées par le bureau d'études ou l'architecte).

### Uploader une Nouvelle Révision
Depuis la fiche d'un chantier, dans l'onglet **Maquettes 3D**, cliquez sur le bouton vert **"Nouvelle Révision"** d'un fichier IFC existant (V1). 
Cela créera automatiquement une version enfant (V2) en copiant les attributs de la V1 et en y rattachant le nouveau fichier.

### Comparaison Visuelle en 3D (Diff)
Lorsque vous visualisez une maquette ayant une version précédente, un bouton **"Comparer avec V1"** apparaît en bas à gauche de la vue 3D.
En cliquant sur ce bouton, Batistack analyse géométriquement les deux fichiers et affiche :
- **🟢 Vert (Ajouté)** : Les nouveaux éléments apparus dans la V2.
- **🟠 Orange (Modifié)** : Les éléments dont les propriétés ont changé.
- **🔴 Rouge Translucide (Supprimé)** : Les anciens éléments (Fantômes) qui n'existent plus dans la V2. Un bouton permet de masquer ces fantômes.
