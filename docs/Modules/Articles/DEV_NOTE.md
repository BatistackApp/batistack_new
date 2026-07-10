# 📦 Module Articles & Stocks

## 📌 État Actuel (Ce qui est fait)
*   **Backend & Base de données :** Gestion fine de l'inventaire en place avec prise en charge du multi-entrepôts, des transferts de stocks et du suivi par numéros de série.
*   **Compositions / Recettes :** Système permettant la création d'ouvrages BTP complexes à partir d'articles de base.
*   **Exports :** Export de la valorisation de l'inventaire (CSV / PDF) implémenté et testé.
*   **Mouvements & Audit Log :** L'enregistrement automatique et robuste des mouvements de stock (Audit Log avec PUMP recalculé dynamiquement) est en place pour toute entrée/sortie d'inventaire. L'affectation de stocks virtuels aux VUL est gérée.
*   **Tests :** 100% de succès sur la suite de 125 tests. Couverture complète de la logique métier.

*   **Tableau de Bord Logistique :** Création d'un dashboard logistique complet avec graphiques des mouvements de stocks, répartition par entrepôt, et suivi des livraisons attendues.
*   **Lecteur de Code-barres Intégré :** Déploiement du module `filament-barcode-scanner-field` permettant de scanner ou rechercher globalement un article depuis la barre de recherche ou la fiche article.

## 🚧 Ce qu'il reste à faire
*   **Interaction Manuelle :** Liaison du frontend avec la logique de mouvements de stock (entrées/sorties) pour la gestion courante.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
1.  **Génération de Bons de Réapprovisionnement Automatisée :** Créer un process ou Job nocturne qui analyse les seuils d'alerte de "stock minimum" et prépare automatiquement des brouillons de Commandes d'Achat ciblées par fournisseur.
2.  **Gestion des "Kits" de Chantier :** Permettre de préparer à l'avance des "Kits" ou "Palettes" avec une nomenclature précise (ex: Kit Pose Menuiserie) pour les déstocker en un seul clic vers une camionnette.
3.  **Traçabilité Outillage (NFC/RFID) :** Intégration avancée avec des étiquettes NFC pour le suivi unitaire du petit outillage électroportatif coûteux (afin de savoir exactement quel employé l'a emprunté et sur quel chantier).
