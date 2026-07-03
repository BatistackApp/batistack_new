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
1.  **Vérification de la conformité documentaire (OCR) :** Automatiser la vérification de la validité des documents légaux déposés par les sous-traitants (Kbis, attestations URSSAF, assurances décennales) et paramétrer des alertes avant expiration.
2.  **Cartographie Interactive :** Visualiser la répartition géographique des fournisseurs, sous-traitants et clients par rapport aux chantiers en cours sur une carte (via Filament Map).
3.  **Portail d'appels d'offres Privé :** Permettre de diffuser des consultations et demandes de prix directement aux sous-traitants et fournisseurs via leur accès portail dédié.
4.  **Évaluation Financière & Solvabilité :** Intégration d'une API (ex: Creditsafe, Infogreffe) pour récupérer automatiquement les scores de solvabilité des tiers et définir des limites de crédit ou d'encours automatisées.
