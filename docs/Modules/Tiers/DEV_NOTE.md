# 🤝 Module Tiers (CRM : Clients, Fournisseurs, Sous-Traitants)

## 📌 État Actuel (Ce qui est fait)
*   **Backend :** Modélisation de la base (Clients, Fournisseurs, Sous-traitants, Adresses multiples, Contacts, Banques). 
*   **Logique :** Les relations sont fonctionnelles. L'intégration de données publiques (INSEE) est prête via `Core\SirenService`.
*   **Tests :** Validé à 100% (168 tests PestPHP exécutés et validés).
*   **Frontend :** Les fondations du Panel Filament sont initiées dans `app/Filament/Tiers`. L'interface principale est fonctionnelle, incluant le bouton de recherche SIRET via l'API INSEE.
*   **Portail Externe B2B :** Déjà implémenté (`Filament/Customer`).

## 🚧 Ce qu'il reste à faire
*(La base du module est terminée, voir les améliorations)*

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
1.  **Scoring Fournisseur / Sous-traitant (Validé) :** Algorithme qui note automatiquement la fiabilité d'un fournisseur (délais de livraison respectés, qualité, litiges) pour aider le service achat dans ses décisions.
