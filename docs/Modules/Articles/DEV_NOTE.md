# 📦 Module Articles & Stocks

## 📌 État Actuel (Ce qui est fait)
*   **Backend & Base de données :** Gestion fine de l'inventaire en place avec prise en charge du multi-entrepôts, des transferts de stocks et du suivi par numéros de série.
*   **Compositions / Recettes :** Système permettant la création d'ouvrages BTP complexes à partir d'articles de base.
*   **Exports :** Export de la valorisation de l'inventaire (CSV / PDF) implémenté et testé.
*   **Mouvements & Audit Log :** L'enregistrement automatique et robuste des mouvements de stock (Audit Log avec PUMP recalculé dynamiquement) est en place pour toute entrée/sortie d'inventaire. L'affectation de stocks virtuels aux VUL est gérée.
*   **Tests :** 100% de succès sur la suite de 125 tests. Couverture complète de la logique métier.

## 🚧 Ce qu'il reste à faire
*   **Interface Utilisateur :** Construire les Panels Filament pour administrer visuellement les catalogues et la valorisation du stock.
*   **Interaction Manuelle :** Liaison du frontend avec la logique de mouvements de stock (entrées/sorties) pour la gestion courante.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
1.  **Génération de Bons de Réapprovisionnement :** Créer un process ou Job nocturne qui analyse les seuils d'alerte de "stock minimum" et prépare automatiquement des brouillons de Commandes d'Achat (Brouillon).
2.  **Lecteur de Code-barres Intégré :** Utiliser le plugin `filament-barcode-scanner-field` pour que les magasiniers puissent scanner le matériel directement depuis leur téléphone lors d'un retrait ou retour de chantier, mettant à jour le stock en direct.
3.  **Valorisation Financière en Temps Réel :** Calcul et affichage sur le dashboard de la valeur monétaire totale immobilisée en stock.
