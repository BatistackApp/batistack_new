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
*   **Moteur de Signatures** : `SignatureService` gère le hachage SHA-256 (checksums) des documents, la création de tokens UUID, et la validation de l'intégrité des données.
*   **APIs & Utilitaires externes** :
    *   `SirenService` : Récupération des données d'entreprise via SIREN/SIRET.
    *   `GoogleMapsService` : Géocodage et calcul d'itinéraires.
    *   `WeatherAlertService` : Récupération d'alertes météorologiques (utile pour les chantiers).
*   **Utilitaires internes** : `VatService` (calculs de taxes rapides), `SettingService` (mise en cache des paramètres), `CompanyService`, `DeviceDetectorService`.

### 3. Logique Asynchrone & Interfaces (Jobs, Mail, Http)
*   **Jobs** : `CreateDocumentJob` (génération de PDF en arrière-plan) et `RefreshCoreCacheJob` (rafraîchissement du cache des paramètres).
*   **Mails & Contrôleurs** : `SignatureController` et `SignatureRequestedMail` pour permettre aux clients de signer des documents via un lien externe sécurisé par Token.
*   **Commandes & Notifications** : `CheckCoreSettingsCommand` (vérification de l'intégrité de la configuration) et `ConfigurationChangedNotification`.

### 4. Tests
*   Les Observers (`CompanyObserver`, `SettingObserver`, etc.) garantissent l'intégrité des données.
*   La logique de hachage de la signature et le typage strict sont en place.

## 🚧 Ce qu'il reste à faire
*   **Paramétrage Filament (UI)** : Bien que les bases de la base de données soient solides, les interfaces Filament (pages de paramètres) pour administrer les Taux de TVA, les Unités, les Paramètres Globaux et les Entreprises doivent être finalisées ou créées.
*   **Rôles et Permissions** : Intégration complète de Filament Shield ou d'un système similaire pour la gestion granulaire des rôles (Admin, Technicien, Commercial) au niveau global.
*   **Dashboard** : Personnalisation du Dashboard de base (Widgets, KPI transverses).

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Stockage Cloud** : Connecter le `DocumentService` à un bucket S3 pour archiver les PDF signés.
*   **Signature Avancée** : Intégration optionnelle de prestataires certifiés eIDAS (ex: Yousign, DocuSign) dans le `SignatureService` pour des contrats à très forte valeur légale (actuellement, la signature "maison" avec empreinte est utilisée).
