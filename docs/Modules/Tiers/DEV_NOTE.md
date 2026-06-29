# 🤝 Module Tiers (CRM : Clients, Fournisseurs, Sous-Traitants)

## 📌 État Actuel (Ce qui est fait)
*   **Backend :** Modélisation de la base (Clients, Fournisseurs, Sous-traitants, Adresses multiples, Contacts, Banques). 
*   **Logique :** Les relations sont fonctionnelles. L'infrastructure pour l'intégration de données publiques (SIREN/SIRET) est prête.
*   **Tests :** Validé à 100% (168 tests PestPHP exécutés et validés).
*   **Frontend :** Les fondations du Panel Filament sont initiées dans `app/Filament/Tiers`.

## 🚧 Ce qu'il reste à faire
*   **Interface Utilisateur :** Terminer et peaufiner les pages de gestion Filament.
*   **API SIREN :** Connecter effectivement l'API publique (ex: Pappers ou INSEE) pour que la création d'un Tiers remplisse automatiquement le nom, l'adresse, la TVA et le NAF depuis le SIRET saisi.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
1.  **Portail Externe B2B :** Ouvrir un accès limité sécurisé permettant aux clients de télécharger leurs factures et devis, ou aux sous-traitants de déposer leurs attestations URSSAF et assurances (Document de vigilance légale obligatoire).
2.  **Scoring Fournisseur / Sous-traitant :** Algorithme qui note automatiquement la fiabilité d'un fournisseur (délais de livraison respectés, qualité, litiges) pour aider le service achat dans ses décisions.
