# ERP Batistack

Logiciel de gestion et de comptabilité pour le domaine du batiment.
Logiciel développer de facon modulaire pour permettre une grande maintenabilité à long terme.

---
## Environnement de développement
- Stack Principal: Laravel 13 / Livewire 4 / FilamentPHP 5 / Test Unitaire PestPHP
- Package composer à utiliser:
    - achyutn/filament-log-viewer
    - ariefng/filament-calculator
    - batistackapp/activity-log
    - batistackapp/filament-map-picker
    - batistackapp/progress-stepper
    - bezhansalleh/filament-panel-switch
    - caresome/filament-auth-designer
    - chillerlan/php-qrcode
    - devletes/filament-progress-bar
    - filament/spatie-laravel-media-library-plugin
    - guava/calendar
    - hydrat/filament-table-layout-toggle
    - laravel-lang/common
    - marcelorodrigo/filament-barcode-scanner-field
    - nakanakaii/filament-countries
    - nben/filament-record-nav
    - opcodesio/log-viewer
    - openplain/filament-tree-view
    - picqer/php-barcode-generator
    - qalainau/filament-inbox
    - saade/filament-autograph
    - spatie/browsershot
    - spatie/laravel-activitylog
    - spatie/laravel-medialibrary
    - tapp/filament-progress-bar-column
    - tinusg/filament-company-logo-column
    - tonegabes/filament-better-options
    - tonegabes/filament-phosphor-icons
    - zvizvi/user-fields

- Service Auxiliaire à utiliser:
    - Detecteur de Support (Mobile, Desktop, Tablette, etc...)
    - Google Geocoding (Geolocalisation chantier, adresse, flottes, etc...)
    - Google Distance MAtrix (Calcul d'itinéraire et de distance pour les chantier)
    - Service OCR Permettant l'enregistrement à partir de capture, scan, etc...
    - Interrogation répertoire SIREN, permettant de vérifier les informations des tiers et/ou enregistrer directement les tiers
    - Service de Signature numérique developper par nos soins, n'utilise pas de services tiers.
    
---
## Modules & Descriptifs

    - Core           
    - Tiers
    - Chantiers
    - Articles & Stocks
    - Commerce / Facturation
    - Resources Humaines (RH) & Service de Pointage
    - Paie
    - Banque
    - Note de Frais
    - Flotte
    - Locations
    - Immobilisations
    - GPAO [x]
    - Interventions
    - 3D Visions
    
### Core [x]
**Descriptifs:** Ce module est le centre de l'application, il doit contenir à la fois les informations de base du programme, de l'entreprise et toutes les configurations utiles au logiciel
**Portés:** Tous les modules

### Tiers [x]
**Descriptifs:** Gestion des clients, fournisseurs et sous-traitants.
**Portés:** Tous les modules ayant besoins de la base de donnée des tiers de l'application

## Chantiers [x]
**Descriptifs:** Suivi des projets, incluant la gestion des coûts (main-d'œuvre, location, achats), le suivi budgétaire et la génération de rapports de rentabilité (PDF/CSV).
Dans l'idéal, il doit permettre la centralisation des informations des chantiers et des resources applicable.
**Portés:** Tous les modules ayant besoins de la base de donnée des chantiers de l'application

### Articles & Stocks [x]
**Descriptifs:** Gestion du catalogue d'articles, des ouvrages (recettes) et du stock multi-dépôts.
**Portés:** Tous les modules ayant besoins de la base de donnée des articles de l'application

### Commerce / Facturation [x]
**Descriptifs:** Création de devis, factures, acomptes, suivi des paiements. (Client/Fournisseur) NB les facturation d'avancement de chantiers
**Portés:** Tous les modules ayant besoins de la base de donnée des factures de l'application

### Resources Humaines (RH) & Service de Pointage [x]
**Descriptifs:** Permettre la gestion complètes des employés de l'entreprise ainsi que du pointage horaire.
**Portés:** Tous les modules ayant besoins de la base de donnée des ressources humaines de l'application

### Paie [x]
**Descriptifs:** Permet le calcul des fiches de paie des employés de l'entreprise avec gestion complète du workflow de paie, de la saisie à l'impression des fiches de paies et des paiements de celle-ci (Salaire, Acomptes, Acomptes Grand Déplacement, etc...)
- [x] Saisie et calcul des bulletins
- [x] Génération PDF du bulletin (Moteur Chromium/Browsershot)
- [x] Workflow de validation et verrouillage (Clôture)
- [x] Export comptable des écritures de paie
- [x] Génération du fichier de virement SEPA
- [x] Espace Salarié et Envoi des fiches de paie
- [x] Exports DADS/DSN & Cumuls annuels réels
**Portés:** Tous les modules ayant besoins de la base de donnée des paie de l'application

### Banque [x]
**Descriptifs:** Gestion des comptes, synchronisation des transactions et rapprochement bancaire automatisé. (Possibilité d'utiliser des API Tiers)
**Portés:** Tous les modules ayant besoins de la base de donnée bancaire de l'application

### Note de Frais [x]
**Descriptifs:**  Gestion des dépenses avec workflow de validation et comptabilisation automatique. (Possibilité OCR)
**Portés:** Tous les modules ayant besoins de la base de donnée des frais de l'application

### Flottes [x]
**Descriptifs:** Gestion complète (Véhicules, Assurances, Maintenances, Contravention, etc...). **Assignation sécurisée avec détection de conflits** et notifications. **Imputation analytique des coûts aux chantiers.**
**Portés:** Tous les modules ayant besoins de la base de donnée des flottes de l'application

### Locations [x]
**Descriptifs:** Gestion des contrats fournisseurs. **Support de la périodicité, alertes d'expiration et génération automatique des factures fournisseurs.** Intégration d'un calendrier de suivi et consolidation avec les ressources déployées sur chantier.
**Portés:** Tous les modules ayant besoins de la base de donnée des locations de l'application

### Immobilisations [x]
**Descriptifs:** Permet la gestion complète des immobilisations de l'entreprise (Enregistrement comptable, VNC, Cout analytique, Dotations, etc...)
**Portés:** Tous les modules ayant besoins de la base de donnée des immobilisations de l'application

### GPAO [x]
**Descriptifs:** Gestion des Ordres de Fabrication (OF), planification, suivi de statut, mise à jour des stocks. **Inclut un système de calcul des besoins en matériaux (MRP simplifié) et la génération automatique de suggestions d'achats.**
**Portés:** Tous les modules ayant besoins de la base de donnée des GPAO de l'application

### Interventions
**Descriptifs:** Gestion des interventions (Forfait ou Régie). **Déstockage intelligent (Dépôt par défaut), facturation client avec marge configurable et suivi de rentabilité, comptabilisation analytique des coûts.**
**Portés:** Tous les modules ayant besoins de la base de donnée des interventions de l'application

### 3D Visions
**Descriptifs:** Structure Backend prête pour la gestion des maquettes 3D. Intégration d'un viewer BIM/IFC.
**Portés:** Tous les modules ayant besoins de la base de donnée des maquettes 3D de l'application

---
## Workflow de développement

Developpement de structure backend et frontend modulaire.
L'ordre doit être scrupuleusement respecter afin de garantir l'unicité du développement.
Chaque étape de développement doit être dans un "Canvas" distinct.

### 1. Développement BACKEND
 - Etape 1: Définir le contexte métier et logique du module en prenant en compte les modules existants.
 - Etape 2: Définir les migrations, Enums et Modèle Eloquents, Factories, Seeder propre au module développer.
 - Etape 3: Définir La porté technique du module en s'appuyant sur des exemples concret.
 - Etape 4: Définir les services dont le module aura besoin dans le namespace App/Service/{NomDuModule}
 - Etape 5: Définir les automatisations du module développer suivant l'ordre suivant
    - Observer
    - Jobs
    - Consoles/Commandes Artisan
    - Notifications / Mails
    - Schedules cron (Uniquement si besoin avéré)
 - Etape 6: Etablissement des Tests Unitaires/Features en corrélation avec le Module dans le format PESTPHP.
 
### 2. Développement FRONTEND
 - Etape 1: Définir le panel filament propre au module développer
 - Etape 2: Développer les resources necessaire au module (Resource, Schema, Table, RelationManagers, Actions, Widget, etc...)
 - Etape 3: Définir le tableau de bord générale du panel du module
 





        
