---
title: DEV NOTE
icon: heroicon-o-document-text
order: 99
---

# ⚙️ Module Core (Architecture & Configuration)

## 📌 Vue d'ensemble du Module
Le module **Core** est la colonne vertébrale de l'ERP Batistack. Il fournit tous les services fondamentaux, l'architecture multi-tenant (entreprise), les paramètres globaux, et les moteurs transverses (génération de documents, géolocalisation, signature numérique, etc.) nécessaires au fonctionnement des autres modules (Interventions, Chantiers, Ventes...).

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/Core` & `app/Enums/Core`)
*   **`Company`** : Gestion des entreprises (fondation pour le multi-tenancy ou la gestion multi-agences). Intègre l'Observer `CompanyObserver`.
*   **`Setting`** : Gestion des paramètres globaux de l'application (TVA par défaut, configuration ERP).
*   **`Signature`** : Modèle centralisé pour la signature numérique de n'importe quelle entité (polymorphisme). Utilise les enums `SignatureStatus` et `SignatureType`.
*   **`Unit` & `VatRate`** : Gestion dynamique des unités de mesure (`UnitType`) et des taux de TVA applicables dans toute l'application.

### 2. Services Transverses (`app/Services/Core`)
Le cœur regorge de services essentiels déjà implémentés et fonctionnels :
*   **Génération de Documents** : 
    *   `DocumentService` : Moteur de génération de PDF.
    *   `PdfStamperService` : Apposition de certificats de signature numérique scellés (FPDI) sur les documents PDF existants.
*   **Moteur de Signatures** : `SignatureService` a évolué vers une architecture basée sur des "Drivers" (`Manager`). Il supporte les providers `local` (solution interne BatiStack sans abonnement tiers) et `docuseal` via la configuration `.env`. Il gère le hachage SHA-256 des documents et met à jour automatiquement les documents signés (Devis, Contrats RH, etc.).
*   **APIs & Utilitaires externes** :
    *   `SirenService` : Récupération des données d'entreprise via SIREN/SIRET.
    *   `GoogleMapsService` : Géocodage et calcul d'itinéraires.
    *   `WeatherAlertService` : Récupération d'alertes météorologiques (utile pour les chantiers).
*   **Utilitaires internes** : `VatService` (calculs de taxes rapides), `SettingService` (mise en cache des paramètres), `CompanyService`, `DeviceDetectorService`.

### 3. Logique Asynchrone & Interfaces (Jobs, Mail, Http)
*   **Jobs** : `CreateDocumentJob` (génération de PDF en arrière-plan) et `RefreshCoreCacheJob` (rafraîchissement du cache des paramètres).
*   **Mails & Contrôleurs** : 
    *   `SignatureController` gère la validation et l'apposition visuelle de la signature, avec acceptation automatique post-signature (ex: Devis vers Commande).
    *   `SignatureRequestedMail` a été optimisé pour le Cloud (S3/Minio) et la sécurité : il n'attache **plus** le PDF en pièce jointe, forçant l'utilisateur à passer par un lien de portail sécurisé.
*   **Stockage Cloud (S3/Minio)** : Pleine compatibilité S3 de BatiStack (utilisation de `temporaryUrl` pour l'aperçu dynamique de PDF sécurisés via `MediaAction` et de `Storage::disk()->download` pour contourner les limitations de `response()->download()` sur des fichiers non locaux - résolutions issues 117/118).
*   **Commandes & Notifications** : `CheckCoreSettingsCommand` (vérification de l'intégrité de la configuration) et `ConfigurationChangedNotification`.

### 4. Interface Utilisateur (Filament)
*   **Paramétrage Filament (UI)** : Les interfaces pour administrer les Taux de TVA, les Unités, les Paramètres Globaux et les Entreprises sont implémentées.
*   **Dashboard** : Tableau de bord de base configurable.
*   **GED - Gestion Documentaire (#345)** : Interface de visualisation et classement des PDF générés par `DocumentService`. Table `generated_documents` avec indexation automatique à la génération. Multi-panels : Core (admin CRUD complet), Salarie (lecture ses docs RH), Customer (lecture ses docs), Sous-traitant (lecture ses docs). Commande `core:index-generated-documents` pour ré-indexer les documents existants.

### 5. Tests
*   Les Observers (`CompanyObserver`, `SettingObserver`, `UnitObserver`, `VatRateObserver`) garantissent l'intégrité des données, avec une couverture de tests validant la sécurité et l'invalidation du cache.
*   La logique de hachage de la signature et le typage strict sont en place.
*   Couverture complète ajoutée pour `CompanyService` (données d'en-tête de documents) et `GoogleMapsService` (géocodage, matrice de distance, optimisation d'itinéraire).

### 6. Gestion des Droits et Permissions (Filament Shield)
*   Intégration de `spatie/laravel-permission` via `bezhansalleh/filament-shield`.
*   Les permissions granulaires (CRUD) sont automatiquement générées via la commande `shield:generate`.
*   **Synchronisation RH (Issue #226)** : Attribution dynamique du rôle utilisateur en fonction de l'intitulé de poste (`job_title`) renseigné dans le contrat actif du module RH. 
*   **Révocation automatisée** : Tâche planifiée quotidienne `rh:sync-expired-roles` pour désactiver automatiquement les droits liés à un poste en cas d'expiration du contrat.

### 7. Piste d'Audit / Activity Log (Issue #227)
*   **Traçabilité Globale** : Intégration de `spatie/laravel-activitylog` via le plugin `batistackapp/activity-log`.
*   **Entités Suivies** : Historisation de toutes les modifications (création, mise à jour, suppression) sur les modèles critiques (Devis, Factures, Chantiers).
*   **Visualisation UI** : Un composant `ActivityLogRelationManager` est greffé aux ressources Filament pour offrir une vue chronologique (Timeline) claire sur l'historique de chaque entité.

## 🚧 Ce qu'il reste à faire
*   Le module Core sert de socle pour l'ensemble du système. Sa maintenance se fait en continu à mesure que les autres modules évoluent.
*   **Dashboard (Issue #185)** : Les widgets avancés (`laboiteacode`) sont implémentés et affichés sur le Dashboard par défaut (via `discoverWidgets`), mais **aucune page Dashboard personnalisée** n'existe (le panel utilise le `Dashboard::class` par défaut). Une page dédiée permettrait une disposition optimisée (KPIs en haut, listes d'alertes en pleine largeur).

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Workflow Approbations Multiples** : Permettre d'avoir plusieurs signataires sur un même document via le `SignatureService` (actuellement **un seul** signataire : `Signature.user_id`, `SignatureProviderInterface` à email unique, `DocusealProvider` avec un seul `submitter`).
