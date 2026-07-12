# 💶 Module Commerce & Facturation

## 📌 État Actuel (Ce qui est fait)
*   **Backend :** Structure du tunnel de vente et d'achat totalement couverte. Supporte les Devis (Clients), Commandes (Clients/Fournisseurs), Situations (Clients/Sous-traitants), Bons de Livraison, Factures et Paiements.
*   **Logique Métier :** Gestion des workflows de statuts (Brouillon -> Envoyé -> Accepté -> Facturé). Les règles de validation sont couvertes.
*   **Lecteur de Code-barres :** Intégration du scan de code-barres dans les formulaires des Commandes Clients et Factures Fournisseurs pour identifier et insérer rapidement des lignes d'articles.
*   **Tests :** Validation complète du module avec 100% de succès sur la gigantesque suite de **182 tests** PestPHP. Le cycle de vie complet est garanti sans faille logicielle.

## 🚧 Ce qu'il reste à faire
*   **Génération de Documents :** Finaliser et designer la génération des PDF (devis, factures) avec l'identité de l'entreprise.
*   **Interfaces Filament :** Créer les vues permettant aux commerciaux de manipuler facilement de gros devis multi-lignes.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
1.  **Signature Électronique (DocuSeal) :** Déployer l'outil `docuseal` (présent dans composer) pour que les clients signent électroniquement et légalement les devis directement en ligne. L'acceptation du devis sera ainsi automatisée.
2.  **Paiement en ligne & Relances automatiques :** Intégrer un lien de paiement Stripe ou prélèvement SEPA (GoCardless) en bas de la facture électronique, couplé à un robot qui relance par email les impayés à J+3, J+15.
3.  **Bibliothèques d'Ouvrages :** Interfacer le devis avec Batiprix ou une autre bibliothèque d'ouvrages BTP standardisée pour accélérer le chiffrage.
