# 🤝 Module Tiers (CRM : Clients, Fournisseurs, Sous-Traitants)

## 📌 État Actuel (Ce qui est fait)
*   **Backend :** Modélisation de la base (Clients, Fournisseurs, Sous-traitants, Adresses multiples, Contacts, Banques). Vue 360° financière et opérationnelle implémentée, ainsi que les notifications automatiques de rappel de vigilance.
*   **Logique :** Les relations sont fonctionnelles. L'intégration de données publiques (INSEE) est prête via `Core\SirenService`. Le système de Scoring Fournisseur (basé sur la qualité, délais, litiges) est opérationnel.
*   **Tests :** Validé à 100% (plus de 172 tests PestPHP exécutés et validés avec succès).
*   **Frontend :** Les fondations du Panel Filament sont initiées. L'interface principale est fonctionnelle.
*   **Portails Externes B2B :** L'accès pour les Sous-traitants (SubcontractorPanel) et pour les Clients (CustomerPanel) a été configuré et sécurisé.

## 🚧 Ce qu'il reste à faire
*(La base du module est terminée, voir les améliorations)*

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
1.  **Intégration CRM Avancée :** Relier les clients à des campagnes d'e-mailing ou de relance.
