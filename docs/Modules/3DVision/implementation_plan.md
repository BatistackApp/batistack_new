# Plan d'Implémentation : Module 3D Vision (BIM)

Ce document décrit l'architecture et les étapes de développement pour intégrer la gestion des maquettes 3D (BIM) au sein de l'ERP Batistack.

## Objectif
Permettre le stockage, la visualisation et l'interaction avec des maquettes numériques (fichiers IFC/BIM) directement depuis l'interface Filament, et les lier aux entités opérationnelles (Chantiers, Interventions).

## User Review Required

> [!IMPORTANT]
> **Choix de la technologie du Viewer 3D**
> L'intégration d'un visualiseur IFC natif dans un navigateur nécessite une librairie JS spécialisée. 
> Je propose d'utiliser **IFC.js** (ou `web-ifc-viewer`), qui est le standard open-source actuel pour lire le format BIM `.ifc` dans le web. Êtes-vous d'accord avec ce choix technique ou préférez-vous un autre format (comme `.glb`/`.gltf` généré en amont) ?

> [!WARNING]
> **Taille des fichiers 3D**
> Les fichiers IFC peuvent être très lourds (plusieurs centaines de Mo). Il faudra s'assurer que la configuration PHP (`upload_max_filesize`, `post_max_size`) de votre serveur accepte les gros fichiers.

## Open Questions

1. Souhaitez-vous que les utilisateurs puissent cliquer sur la maquette 3D pour y "déposer des punaises" (Annotations/Markers) afin de créer directement une tâche ou une intervention liée à ce point précis ?
2. Voulons-nous limiter l'upload strictement au format `.ifc`, ou autoriser d'autres formats 3D (`.obj`, `.gltf`) ?

## Proposed Changes

### 1. Base de Données & Modèles (Backend)

#### [NEW] `app/Models/Vision3D/BimModel.php`
- Représente un fichier 3D uploadé.
- Colonnes : `id`, `name`, `file_path`, `file_size`, `format`, `version`, `modelable_type`, `modelable_id` (Relation polymorphique pour l'attacher à un Chantier ou un Actif).

#### [NEW] `app/Models/Vision3D/BimAnnotation.php` (Optionnel)
- Représente un point d'intérêt sur la maquette.
- Colonnes : `bim_model_id`, `title`, `description`, `camera_position_x`, `camera_position_y`, `camera_position_z`, `target_id` (Lien vers une Intervention).

#### [NEW] `database/migrations/..._create_bim_models_table.php`
- Création des tables associées.

### 2. Interface Utilisateur (Filament)

#### [NEW] `resources/views/filament/components/bim-viewer.blade.php`
- Composant Blade/Alpine.js qui va encapsuler la librairie JavaScript (ex: `web-ifc-viewer`) pour le rendu WebGL.

#### [NEW] `app/Filament/Vision3D/Resources/BimModelResource.php`
- CRUD pour gérer les fichiers 3D.
- Infolist contenant le `bim-viewer` pour naviguer dans la maquette en plein écran.

#### [MODIFY] `app/Filament/Chantiers/Resources/ChantierResource.php`
- Ajout d'un onglet ou d'un Relation Manager "Maquettes 3D" pour lier un BIM à un projet.

### 3. Logique & Services

#### [NEW] `app/Services/Vision3D/BimStorageService.php`
- Service dédié à la validation et au stockage sécurisé (disque `local` ou `s3`) des gros fichiers 3D.

## Verification Plan

### Automated Tests
- `BimModelTest.php` : Tester l'upload, la relation polymorphique (attachement à un Chantier) et la suppression (nettoyage du fichier physique).

### Manual Verification
- Uploader un vrai fichier `.ifc` de test.
- Vérifier que le viewer WebGL s'affiche correctement dans l'infolist Filament sans faire crasher le navigateur.
- Naviguer dans les calques de la maquette (si supporté par la librairie).
