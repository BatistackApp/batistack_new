# 📦 Module Articles & Stocks

## 📌 Vue d'ensemble du Module
Le module **Articles & Stocks** permet la gestion complète de l'inventaire, du référentiel produit (matériaux, outillage, prestations) et de la logistique multi-entrepôts (incluant les véhicules des techniciens).

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/Articles` & `app/Enums/Articles`)
*   **`Item` & `ItemComposition`** : Référentiel des articles. Gère les nomenclatures (recettes/compositions) permettant de créer des ouvrages complexes à partir d'articles de base.
*   **`Warehouse` & `Stock`** : Gestion multi-entrepôts. Affectation des stocks à des entrepôts physiques ou à des stocks virtuels (VUL/Camionnettes).
*   **`StockMouvement`** : Traçabilité complète des mouvements de stock. Utilise les Enums `StockMouvementType` (Entrée, Sortie, Transfert) et `StockMouvementSource` (Achat, Chantier, SAV).

### 2. Logique Métier, Services & Commandes (`app/Services/Articles` & `app/Console/Commands`)
*   **`StockService`** : Logique robuste pour la gestion des mouvements de stock. Vérifie les disponibilités, gère les transferts entre entrepôts et déclenche les alertes de seuil.
*   **`InventoryService`** : Valorisation de l'inventaire et recalcul dynamique du PUMP (Prix Unitaire Moyen Pondéré) lors des entrées en stock.
*   **`ItemService`** : Gestion du cycle de vie des articles.
*   **`CheckLowStockCommand`** : Commande nocturne d'analyse des stocks. Gère les alertes locales (entrepôts) et génère automatiquement les brouillons de Commandes d'Achat regroupés par `supplier_id` (basé sur le `min_stock` global et les commandes en cours).
*   **Transfert de Kits (`transferKit`)** : Logique stricte de déstockage d'un ouvrage parent vers une camionnette, avec vérification de la disponibilité de chaque composant enfant dans l'entrepôt source.
*   **Actions Filament** : `DestockKitAction` pour préparer rapidement un kit en 1 clic depuis l'interface (présente sur les listes Articles et Entrepôts).
*   **Traçabilité Outillage (NFC/RFID)** : Interconnexion avec le module RH (`Equipements`). Le champ `item_id` sur un équipement RH permet d'associer un bien physique (ex: Perceuse N°123) au catalogue logistique global. Le suivi des prêts est géré via la table `equipement_assignments` et l'interface de scan NFC dédiée côté RH.

### 3. Observers & Événements (`app/Observers/Articles`)
*   **`StockMouvementObserver`** : L'enregistrement automatique (Audit Log) est en place pour toute entrée/sortie d'inventaire, garantissant l'intégrité de la base.
*   **`BarcodeObserver`** : Génération ou assignation de codes-barres lors de la création d'articles.

### 4. Interface Utilisateur (Filament)
*   **Interfaces Filament (CRUD)** : Les ressources visuelles pour le module Articles existent et permettent la gestion du catalogue et des entrepôts.
*   **Lecteur de Code-barres** : Intégration du module `filament-barcode-scanner-field` (utilisé notamment pour le module de scan outillage NFC côté RH).

### 5. Tests
*   100% de succès sur la suite de tests (125 tests). Couverture complète de la logique métier, du calcul du PUMP, et de la prévention des stocks négatifs.

## 🚧 Ce qu'il reste à faire
*   **Tableau de Bord Logistique** : Le Dashboard logistique complet n'est pas encore finalisé. Il nécessitera l'intégration de widgets avancés (via le package `laboiteacode`) pour suivre la valeur d'inventaire, les ruptures et la rotation.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Prévisions par IA** : Anticiper les ruptures de stock selon les chantiers planifiés et la saisonnalité.
*   **Refonte du Dashboard Logistique (Widgets Avancés)** : Intégration de `laboiteacode/filament-dashboard-widgets` pour afficher la répartition par entrepôt, les alertes de stock minimal, et la variance du PUMP.
