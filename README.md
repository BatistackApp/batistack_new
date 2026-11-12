<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Batistack ERP">
</p>

# 🏗️ Batistack - L'ERP Nouvelle Génération pour le BTP

**Batistack** est un Progiciel de Gestion Intégré (ERP) modulaire, conçu spécifiquement pour les entreprises du bâtiment et des travaux publics (BTP).
Développé avec Laravel 13 et propulsé par Filament v5 / Livewire 4, Batistack offre une expérience utilisateur premium, une architecture robuste (Monolithe Modulaire), et une intégration profonde entre tous les métiers de l'entreprise.

---

## 🚀 Fonctionnalités Clés & Architecture Modulaire

L'application est structurée autour d'une architecture **Monolithe Modulaire** où chaque domaine métier (Module) est isolé (Modèles, Services, Observers, UI) tout en communiquant de manière fluide avec le reste du système.

### 🏢 1. Les Piliers Opérationnels
* **🚧 Chantiers (Projets)** : Gestion complète des projets de construction. Budgets prévisionnels, suivi de l'avancement, tableaux de bord de rentabilité en temps réel avec intégration des coûts matériels (Articles), humains (RH) et externes (Locations, Flottes, Immobilisations).
* **🛒 Commerce & Facturation** : Du devis (avec lecteur de code-barres) à la facture finale. Génération PDF asynchrone, situations de travaux, gestion des avoirs et suivi des encaissements.
* **📦 Articles & Stocks** : Gestion multi-entrepôts, valorisation PUMP (Prix Unitaire Moyen Pondéré), gestion des nomenclatures (BOM) et des mouvements de stocks stricts.
* **🤝 Tiers (CRM)** : Gestion à 360° des Clients, Fournisseurs et Sous-Traitants. Intégration de l'API Gouv pour la vérification de solvabilité, alertes sur expiration des documents légaux (Kbis, URSSAF) et Portails B2B dédiés.

### ⚙️ 2. Production & Moyens Matériels
* **🏭 GPAO (Production)** : Moteur MRP (Material Requirements Planning) pour gérer la fabrication en atelier. Tableau Kanban interactif, terminaux tactiles de pointage, QR codes et génération automatique des ordres d'achat en cas de rupture.
* **🚐 Flottes** : Gestion du parc automobile, alertes de maintenance, calcul du TCO via les frais d'essence et gestion légale des amendes routières.
* **🏗️ Immobilisations** : Suivi des actifs de l'entreprise, génération automatique des tableaux d'amortissement (Linéaire/Dégressif), alertes VGP et export FEC.
* **🚜 Locations** : Gestion des contrats de location de matériel, facturation fournisseur récurrente et imputation analytique automatique sur les chantiers.

### 👥 3. Ressources Humaines & Finances
* **👥 RH & Pointage** : Kiosque biométrique, notes de frais avec extraction OCR, onboarding digital et gestion des habilitations / visites médicales.
* **💶 Paie** : Moteur de paie, génération de fiches de paie pro forma, exports DSN et fichiers de virement SEPA.
* **🏦 Banque** : Rapprochement bancaire via Open Banking (Bridge API), lettrage semi-automatique et suivi de trésorerie.

### 🛡️ 4. Socle Technique (Core)
* **Core** : Moteur d'audit transversal, gestion fine des rôles, webhooks et intégrations tierces.

---

## 🛠️ Stack Technologique

* **Backend** : Laravel 13 (PHP 8.3+ requis)
* **Frontend** : Filament PHP v5 et Livewire 4
* **Base de données** : MySQL 8+ / MariaDB / PostgreSQL (selon validation de l'environnement)
* **Tests** : Pest PHP v4
* **Tâches asynchrones** : Laravel Queue / Scheduler
* **Génération PDF** : Spatie Browsershot (Chromium)

---

## ⚙️ Installation (Développement)

1. Cloner le dépôt :
   ```bash
   git clone https://github.com/BatistackApp/batistack_new.git
   cd batistack_new
   ```
2. Installer les dépendances :
   ```bash
   composer install
   npm ci
   npm run build
   ```
3. Configurer l'environnement :
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. Configurer la base dans .env puis exécuter :
   ```bash
   php artisan migrate --seed
   ```
5. Créer le lien de stockage et démarrer :
   ```bash
   php artisan storage:link
   php artisan serve
   ```

L'application locale est accessible sur http://localhost:8000/admin.

---

## 📦 Documentation de release 1.0.0

Les procédures d'exploitation et de validation de la release sont disponibles dans le dossier [docs/1.0.0](./docs/1.0.0/) :

- [Installation](./docs/1.0.0/installation.md)
- [Déploiement](./docs/1.0.0/deployment.md)
- [Mise à niveau](./docs/1.0.0/upgrade.md)
- [Sauvegarde et restauration](./docs/1.0.0/backup-restore.md)
- [Rollback](./docs/1.0.0/rollback.md)
- [Limitations connues](./docs/1.0.0/known-limitations.md)
- [Statut des modules](./docs/1.0.0/module-status.md)
- [Politique de gel](./docs/1.0.0/release-policy.md)
- [Décision GO / NO-GO](./docs/avancement/v1.0.0-go-no-go.md)

**Important :** le fichier .env.example est destiné au développement. Ne pas l'utiliser tel quel en production.

