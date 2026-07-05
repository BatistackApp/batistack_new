# 🤝 Module Tiers (CRM : Clients, Fournisseurs, Sous-Traitants)

## 📌 État Actuel (Ce qui est fait)
*   **Backend :** Modélisation de la base (Clients, Fournisseurs, Sous-traitants, Adresses multiples, Contacts, Banques). Vue 360° financière et opérationnelle implémentée, ainsi que les notifications automatiques de rappel de vigilance.
*   **Logique :** Les relations sont fonctionnelles. L'intégration de données publiques (INSEE) est prête via `Core\SirenService`. Le système de Scoring Fournisseur (basé sur la qualité, délais, litiges) est opérationnel.
*   **Tests :** Validé à 100% (plus de 172 tests PestPHP exécutés et validés avec succès).
*   **Frontend :** Les fondations du Panel Filament sont initiées. L'interface principale est fonctionnelle.
*   **Portails Externes B2B :** L'accès pour les Sous-traitants (SubcontractorPanel) et pour les Clients (CustomerPanel) a été configuré et sécurisé.
*   **Évaluation Financière & Solvabilité :** Intégration de l'API publique ouverte (`recherche-entreprises.api.gouv.fr`) pour récupérer en temps réel le statut juridique (liquidation, redressement) des tiers et affichage via des alertes visuelles.
*   **Portail d'Appels d'Offres Privé :** Module de "Consultations" permettant de publier des appels d'offres ciblés pour les chantiers. Les sous-traitants peuvent soumettre leurs offres chiffrées directement depuis leur portail sécurisé.
*   **Conformité Documentaire :** Gestion centralisée des documents obligatoires (Kbis, URSSAF, Décennale) avec système d'upload, suivi des dates d'expiration et notifications automatisées envoyées 30 jours et 7 jours avant échéance.

## 🚧 Ce qu'il reste à faire
*(La base du module est terminée et extrêmement complète. Aucune amélioration prioritaire n'est requise dans l'immédiat).*

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
- Intégration avancée de la signature électronique pour les devis et marchés de sous-traitance (ex: DocuSeal).
