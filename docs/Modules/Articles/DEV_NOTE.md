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

### 3. Observers & Événements (`app/Observers/Articles`)
*   **`StockMouvementObserver`** : L'enregistrement automatique (Audit Log) est en place pour toute entrée/sortie d'inventaire, garantissant l'intégrité de la base.
*   **`BarcodeObserver`** : Génération ou assignation de codes-barres lors de la création d'articles.

### 4. Tests
*   100% de succès sur la suite de tests (125 tests). Couverture complète de la logique métier, du calcul du PUMP, et de la prévention des stocks négatifs.

## 🚧 Ce qu'il reste à faire
*   **Interfaces Filament (CRUD)** : Actuellement, aucune ressource visuelle (`ItemResource`, `WarehouseResource`) n'est créée dans Filament. Le CRUD complet doit être généré.
*   **Tableau de Bord Logistique** : Le Dashboard logistique (graphiques des mouvements, répartition par entrepôt) mentionné précédemment n'est pas implémenté dans l'arborescence Filament actuelle.
*   **Lecteur de Code-barres** : Intégrer visuellement le module `filament-barcode-scanner-field` dans les formulaires d'entrées/sorties de stock.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Gestion des "Kits" de Chantier** : Permettre de préparer à l'avance des "Kits" ou "Palettes" avec une nomenclature précise (ex: Kit Pose Menuiserie) pour les déstocker en un seul clic vers une camionnette.
*   **Traçabilité Outillage (NFC/RFID)** : Intégration avancée avec des étiquettes NFC pour le suivi unitaire du petit outillage électroportatif coûteux (savoir qui l'a emprunté et sur quel chantier).
