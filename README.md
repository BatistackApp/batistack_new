<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Batistack ERP">
</p>

# 🏗️ Batistack - L'ERP Nouvelle Génération pour le BTP

**Batistack** est un Progiciel de Gestion Intégré (ERP) modulaire, conçu spécifiquement pour les entreprises du bâtiment et des travaux publics (BTP). 
Développé avec [Laravel 13](https://laravel.com) et propulsé par [Filament v5](https://filamentphp.com/) / [Livewire 4](https://livewire.laravel.com/), Batistack offre une expérience utilisateur premium, une architecture robuste (Monolithe Modulaire), et une intégration profonde entre tous les métiers de l'entreprise.

---

## 🚀 Fonctionnalités Clés & Architecture Modulaire

L'application est structurée autour d'une architecture **Monolithe Modulaire** où chaque domaine métier (Module) est isolé (Modèles, Services, Observers, UI) tout en communiquant de manière fluide avec le reste du système.

### 🏢 1. Les Piliers Opérationnels
*   **🚧 Chantiers (Projets)** : Gestion complète des projets de construction. Budgets prévisionnels, suivi de l'avancement, tableaux de bord de rentabilité en temps réel avec intégration des coûts matériels (Articles), humains (RH) et externes (Locations, Flottes, Immobilisations).
*   **🛒 Commerce & Facturation** : Du devis (avec lecteur de code-barres) à la facture finale. Génération PDF asynchrone, situations de travaux, gestion des avoirs et suivi des encaissements.
*   **📦 Articles & Stocks** : Gestion multi-entrepôts, valorisation PUMP (Prix Unitaire Moyen Pondéré), gestion des nomenclatures (BOM) et des mouvements de stocks stricts.
*   **🤝 Tiers (CRM)** : Gestion à 360° des Clients, Fournisseurs et Sous-Traitants. Intégration de l'API Gouv pour la vérification de solvabilité, alertes sur expiration des documents légaux (Kbis, URSSAF) et **Portails B2B dédiés**.

### ⚙️ 2. Production & Moyens Matériels
*   **🏭 GPAO (Production)** : Moteur MRP (Material Requirements Planning) pour gérer la fabrication en atelier. Tableau Kanban interactif, terminaux tactiles de pointage, QR codes et génération automatique des ordres d'achat en cas de rupture.
*   **🚐 Flottes** : Gestion du parc automobile, alertes de maintenance, calcul du TCO via les frais d'essence et gestion légale des amendes routières.
*   **🏗️ Immobilisations** : Suivi des actifs de l'entreprise, génération automatique des tableaux d'amortissement (Linéaire/Dégressif), alertes VGP et export FEC (Fichier des Écritures Comptables).
*   **🚜 Locations** : Gestion des contrats de location de matériel (pelles, grues), facturation fournisseur récurrente et imputation analytique automatique sur les chantiers.

### 👥 3. Ressources Humaines & Finances
*   **👥 RH & Pointage** : Kiosque biométrique (reconnaissance faciale), Notes de Frais avec extraction OCR (Google Cloud Vision), onboarding digital, matrices de polyvalence et gestion complète des habilitations / visites médicales.
*   **💶 Paie** : Moteur de paie complet avec réintégration fiscale (CSG/CRDS), calcul automatique des heures supplémentaires/paniers, génération de fiches de paie pro forma, exports DSN et fichiers de virement SEPA.
*   **🏦 Banque** : Rapprochement bancaire dynamique via Open Banking (Bridge API), lettrage semi-automatique des factures et suivi de la trésorerie.

### 🛡️ 4. Socle Technique (Core)
*   **Core** : Moteur d'audit transversal (`OwenIt/Auditing`), gestion fine des rôles (Spatie), Webhooks, et intégrations tierces (Mail, SMS, stockage Cloud).

---

## 🛠️ Stack Technologique

*   **Framework Backend** : [Laravel 13](https://laravel.com/) (PHP 8.3+ requis, PHP 8.4 utilisé en local)
*   **Framework Frontend (Admin)** : [Filament PHP v5](https://filamentphp.com/) avec [Livewire 4](https://livewire.laravel.com/) (TALL Stack : Tailwind CSS, Alpine.js, Laravel, Livewire)
*   **Base de Données** : MySQL 8+ / MariaDB / PostgreSQL
*   **Tests** : [Pest PHP v4](https://pestphp.com/) pour la couverture de la logique métier et des fonctionnalités critiques
*   **Tâches Asynchrones** : Laravel Horizon / Redis (Génération PDF, OCR, Mails, Webhooks)
*   **Génération PDF** : Spatie Browsershot (Puppeteer)

---

## ⚙️ Installation (Environnement de Développement)

1.  **Cloner le dépôt :**
    ```bash
    git clone https://github.com/BatistackApp/batistack_new.git
    cd batistack_new
    ```

2.  **Installer les dépendances :**
    ```bash
    composer install
    npm install
    npm run build
    ```

3.  **Configuration de l'environnement :**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    *Configurez votre base de données dans le fichier `.env`.*

4.  **Base de données & Seeders :**
    ```bash
    php artisan migrate --seed
    ```
    *Le seeder générera l'utilisateur administrateur, les profils de cotisations de base, ainsi que le référentiel minimal.*

5.  **Lien symbolique du stockage :**
    ```bash
    php artisan storage:link
    ```

6.  **Lancer le serveur local :**
    ```bash
    php artisan serve
    ```
    L'application (Panel Admin) sera accessible par défaut sur `http://localhost:8000/admin`.

---

## 🧪 Tests

L'application est fortement testée pour garantir la fiabilité des calculs financiers, de la gestion de stock et de la paie.

```bash
php artisan test
```

---

## 💡 Philosophie de Développement

Batistack privilégie :
1. **L'intégrité des données** (via l'architecture événementielle et les Observers).
2. **L'automatisation** (tâches planifiées pour les alertes, factures récurrentes, DSN).
3. **L'expérience utilisateur** (interfaces Filament réactives, portails dédiés).

*Développé pour les constructeurs qui exigent l'excellence opérationnelles.*
