# Batistack ERP

Logiciel de gestion et de comptabilité pour le domaine du bâtiment.
Logiciel développé de façon modulaire pour permettre une grande maintenabilité à long terme.

## Environnement de développement
- Stack principal : `Laravel 13` / `Livewire 4` / `FilamentPHP 5` / `Test Unitaire PestPHP`
- Packages Composer à utiliser :
    - `achyutn/filament-log-viewer`
    - `ariefng/filament-calculator`
    - `batistackapp/activity-log`
    - `batistackapp/filament-map-picker`
    - `batistackapp/progress-stepper`
    - `bezhansalleh/filament-panel-switch`
    - `caresome/filament-auth-designer`
    - `chillerlan/php-qrcode`
    - `devletes/filament-progress-bar`
    - `filament/spatie-laravel-media-library-plugin`
    - `guava/calendar`
    - `hydrat/filament-table-layout-toggle`
    - `laravel-lang/common`
    - `marcelorodrigo/filament-barcode-scanner-field`
    - `nakanakaii/filament-countries`
    - `nben/filament-record-nav`
    - `opcodesio/log-viewer`
    - `openplain/filament-tree-view`
    - `picqer/php-barcode-generator`
    - `qalainau/filament-inbox`
    - `saade/filament-autograph`
    - `spatie/browsershot`
    - `spatie/laravel-activitylog`
    - `spatie/laravel-medialibrary`
    - `tapp/filament-progress-bar-column`
    - `tinusg/filament-company-logo-column`
    - `tonegabes/filament-better-options`
    - `tonegabes/filament-phosphor-icons`
    - `zvizvi/user-fields`
- Services auxiliaires à utiliser :
    - Détecteur de support (mobile, bureau, tablette, etc.)
    - Google Geocoding (géolocalisation chantier, adresse, flottes, etc.)
    - Google Distance Matrix (calcul d'itinéraire et de distance pour les chantiers)
    - Service OCR permettant l'enregistrement à partir de captures, scans, etc.
    - Interrogation du répertoire SIREN, permettant de vérifier les informations des tiers et/ou d'enregistrer directement les tiers
    - Service de signature numérique développé par nos soins, n'utilisant pas de services tiers.
    
## Modules & Descriptifs

### Sommaire
- [Core](#core-x)
- [Tiers](#tiers-x)
- [Chantiers](#chantiers-x)
- [Articles & Stocks](#articles--stocks-x)
- [Commerce / Facturation](#commerce--facturation-x)
- [Resources Humaines (RH) & Service de Pointage](#resources-humaines-rh--service-de-pointage-x)
- [Paie](#paie-x)
- [Banque](#banque-x)
- [Note de Frais](#note-de-frais-x)
- [Flottes](#flottes-x)
- [Locations](#locations-x)
- [Immobilisations](#immobilisations-x)
- [GPAO](#gpao-x)
- [Interventions](#interventions-x)
- [3D Visions](#3d-visions)
    
<a name="core-x"></a>
### Core [x]
**Descriptifs:** Ce module est le centre de l'application, il doit contenir à la fois les informations de base du programme, de l'entreprise et toutes les configurations utiles au logiciel
**Portée:** Tous les modules

<a name="tiers-x"></a>
### Tiers [x]
**Descriptifs:** Gestion des clients, fournisseurs et sous-traitants.
**Portée:** Tous les modules ayant besoins de la base de donnée des tiers de l'application

<a name="chantiers-x"></a>
### Chantiers [x]
**Descriptifs:** Suivi des projets, incluant la gestion des coûts (main-d'œuvre, location, achats), le suivi budgétaire et la génération de rapports de rentabilité (PDF/CSV).
Dans l'idéal, il doit permettre la centralisation des informations des chantiers et des ressources applicables.
**Portée:** Tous les modules ayant besoins de la base de donnée des chantiers de l'application

<a name="articles--stocks-x"></a>
### Articles & Stocks [x]
**Descriptifs:** Gestion du catalogue d'articles, des ouvrages (recettes) et du stock multi-dépôts. Inclut la traçabilité des lots et des dates de péremption pour les articles sensibles.
**Portée:** Tous les modules ayant besoins de la base de donnée des articles de l'application

<a name="commerce--facturation-x"></a>
### Commerce / Facturation [x]
**Descriptifs:** Création de devis, factures, acomptes, suivi des paiements (client/fournisseur). Note : inclut la facturation d'avancement de chantiers.
**Portée:** Tous les modules ayant besoins de la base de donnée des factures de l'application

<a name="resources-humaines-rh--service-de-pointage-x"></a>
### Resources Humaines (RH) & Service de Pointage [x]
**Descriptifs:** Permettre la gestion complète des employés de l'entreprise ainsi que du pointage horaire.
**Portée:** Tous les modules ayant besoins de la base de donnée des ressources humaines de l'application

<a name="paie-x"></a>
### Paie [x]
**Descriptifs:** Permet le calcul des fiches de paie des employés de l'entreprise avec gestion complète du workflow de paie, de la saisie à l'impression des fiches de paie et des paiements de celles-ci (salaire, acomptes, acomptes grand déplacement, etc.).
- [x] Saisie et calcul des bulletins
- [x] Génération PDF du bulletin (moteur Chromium/Browsershot)
- [x] Workflow de validation et verrouillage (clôture)
- [x] Export comptable des écritures de paie
- [x] Génération du fichier de virement SEPA
- [x] Espace salarié et envoi des fiches de paie
- [x] Exports DADS/DSN & cumuls annuels réels
**Portée:** Tous les modules ayant besoins de la base de donnée des paie de l'application

<a name="banque-x"></a>
### Banque [x]
**Descriptifs:** Gestion des comptes, synchronisation des transactions et rapprochement bancaire automatisé (possibilité d'utiliser des API tierces).
**Portée:** Tous les modules ayant besoins de la base de donnée bancaire de l'application

<a name="note-de-frais-x"></a>
### Note de Frais [x]
**Descriptifs:** Gestion des dépenses avec workflow de validation et comptabilisation automatique (possibilité d'OCR).
**Portée:** Tous les modules ayant besoins de la base de donnée des frais de l'application

<a name="flottes-x"></a>
### Flottes [x]
**Descriptifs:** Gestion complète (véhicules, assurances, maintenances, contraventions, etc.). **Assignation sécurisée avec détection de conflits** et notifications. **Imputation analytique des coûts aux chantiers.**
**Portée:** Tous les modules ayant besoins de la base de donnée des flottes de l'application

<a name="locations-x"></a>
### Locations [x]
**Descriptifs:** Gestion des contrats fournisseurs. **Support de la périodicité, alertes d'expiration et génération automatique des factures fournisseurs.** Intégration d'un calendrier de suivi et consolidation avec les ressources déployées sur chantier.
**Portée:** Tous les modules ayant besoins de la base de donnée des locations de l'application

<a name="immobilisations-x"></a>
### Immobilisations [x]
**Descriptifs:** Permet la gestion complète des immobilisations de l'entreprise (enregistrement comptable, VNC, coût analytique, dotations, etc.).
**Portée:** Tous les modules ayant besoins de la base de donnée des immobilisations de l'application

<a name="gpao-x"></a>
### GPAO [x]
**Descriptifs:** Gestion des ordres de fabrication (OF), planification, suivi de statut, mise à jour des stocks. **Inclut un système de calcul des besoins en matériaux (MRP simplifié) et la génération automatique de suggestions d'achats.**
**Portée:** Tous les modules ayant besoins de la base de donnée des GPAO de l'application

<a name="interventions-x"></a>
### Interventions [x]
**Descriptifs:** Gestion des interventions (forfait ou régie). **Déstockage intelligent (dépôt par défaut), facturation client avec marge configurable et suivi de rentabilité, comptabilisation analytique des coûts.**
**Portée:** Tous les modules ayant besoins de la base de donnée des interventions de l'application

<a name="3d-visions"></a>
### 3D Visions
**Descriptifs:** Structure backend prête pour la gestion des maquettes 3D. Intégration d'un viewer BIM/IFC.
**Portée:** Tous les modules ayant besoins de la base de donnée des maquettes 3D de l'application

## Workflow de développement

Développement de structure backend et frontend modulaire.
L'ordre doit être scrupuleusement respecté afin de garantir l'unicité du développement.
Chaque étape de développement doit être dans un "canevas" distinct.

### 1. Développement backend
- **Étape 1 :** Définir le contexte métier et logique du module en prenant en compte les modules existants.
- **Étape 2 :** Définir les migrations, énumérations, modèles Eloquent, usines et seeders propres au module développé.
- **Étape 3 :** Définir la portée technique du module en s'appuyant sur des exemples concrets.
- **Étape 4 :** Définir les services dont le module aura besoin dans l'espace de noms `App\Services\{NomDuModule}`.
- **Étape 5 :** Définir les automatisations du module développé dans l'ordre suivant :
    - Observateurs
    - Tâches (jobs)
    - Commandes console/Artisan
    - Notifications/e-mails
    - Tâches planifiées (cron) (uniquement si besoin avéré)
- **Étape 6 :** Établir les tests unitaires/fonctionnels en corrélation avec le module au format PestPHP.
 
### 2. Développement frontend
- **Étape 1 :** Définir le panneau Filament propre au module développé.
- **Étape 2 :** Développer les ressources nécessaires au module (ressource, schéma, table, gestionnaires de relations, actions, widgets, etc.).






 





        
