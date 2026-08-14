---
title: DEV NOTE
icon: heroicon-o-document-text
order: 99
---

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
*   **`InventoryService` & `CycleCountingService`** : Valorisation de l'inventaire, recalcul dynamique du PUMP, et gestion complète des inventaires tournants (comptages réguliers générés automatiquement, validation et régularisations).
*   **`ItemService`** : Gestion du cycle de vie des articles.
*   **`CheckLowStockCommand`** : Commande nocturne d'analyse des stocks. Gère les alertes locales (entrepôts) et génère automatiquement les brouillons de Commandes d'Achat regroupés par `supplier_id` (basé sur le `min_stock` global et les commandes en cours).
*   **Transfert de Kits (`transferKit`)** : Logique stricte de déstockage d'un ouvrage parent vers une camionnette, avec vérification de la disponibilité de chaque composant enfant dans l'entrepôt source.
*   **Réservation de Stock (Allocation)** : Le `StockService` intègre le concept de *stock réservé*. Le stock réellement disponible (`getAvailableQuantity()`) est distinct du stock physique pour sécuriser la marchandise destinée aux chantiers (méthodes `reserve`, `release`, `consumeReserved`). Une sortie standard puise uniquement dans le disponible.
*   **Actions Filament** : `DestockKitAction` pour préparer rapidement un kit en 1 clic depuis l'interface (présente sur les listes Articles et Entrepôts).
*   **Traçabilité Outillage (NFC/RFID)** : Interconnexion avec le module RH (`Equipements`). Le champ `item_id` sur un équipement RH permet d'associer un bien physique (ex: Perceuse N°123) au catalogue logistique global. Le suivi des prêts est géré via la table `equipement_assignments` et l'interface de scan NFC dédiée côté RH.

*   **Traçabilité des lots et dates de péremption** : Le `StockService` et les mouvements de stock gèrent maintenant les numéros de lots et les dates de péremption pour les articles sensibles. Un job quotidien (`CheckExpiringStocksJob`), planifié à 08:00, alerte les administrateurs des lots approchant de leur date de péremption.

### 3. Observers & Événements (`app/Observers/Articles`)
*   **`StockMouvementObserver`** : L'enregistrement automatique (Audit Log) est en place pour toute entrée/sortie d'inventaire, garantissant l'intégrité de la base.
*   **`BarcodeObserver`** : Génération ou assignation de codes-barres lors de la création d'articles.

### 4. Interface Utilisateur (Filament)
*   **Interfaces Filament (CRUD)** : Les ressources visuelles pour le module Articles existent et permettent la gestion du catalogue et des entrepôts.
    *   *Inventaire Tournant* : L'interface permet la génération d'un comptage aléatoire ou planifié, et une saisie rapide des stocks réels. Processus d'approbation intégré avec traçabilité complète.
    *   *Stock Réservé* : L'affichage des stocks dans la fiche d'un article distingue le "Stock Physique", le "Stock Réservé" (badge) et le "Stock Disponible" (calculé dynamiquement).
    *   *Actions rapides* : Boutons d'action "Réserver", "Libérer" et "Consommer Rsv." directement intégrés aux lignes de la table des stocks pour une gestion fluide des réservations chantiers.
*   **Lecteur de Code-barres** : Intégration du module `filament-barcode-scanner-field` (utilisé notamment pour le module de scan outillage NFC côté RH).
*   **Dashboard Logistique** : Tableau de bord intégrant des widgets analytiques avancés pour suivre la valeur d'inventaire par magasin, les tendances de sorties de stock et les alertes (via `laboiteacode/filament-dashboard-widgets`).

*   **Impression d'Étiquettes PDF** : Action de masse (BulkAction) disponible sur la ressource Articles pour générer des étiquettes au format A4 (Avery 3x7) ou Thermique (Dymo) incluant le QR Code de l'article pour le scan physique.

### 5. Tests
*   100% de succès sur la suite de tests (plus de 130 tests). Couverture complète de la logique métier (calcul du PUMP, prévention des stocks négatifs, seuils d'alerte, transfert de kits, gestion de la récursion infinie pour les compositions, **tests complets de la réservation de stock et blocage des sorties sur stock réservé**, etc.) via `InventoryServiceTest`, `ItemServiceTest`, et `StockServiceTest`.
*   **Couverture d'Interface** : Tests de l'action d'impression d'étiquettes (`ItemLabelsTest.php`) avec mock du moteur PDF.

## 🚧 Ce qu'il reste à faire
*   Le module est complet dans sa version actuelle.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Prévisions par IA** : Anticiper les ruptures de stock selon les chantiers planifiés et la saisonnalité.
*   **Gestion des emplacements de stockage (bin-picking)** : Suivi précis de l'emplacement des articles dans les entrepôts.
