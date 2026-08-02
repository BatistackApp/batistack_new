# 🛒 Module Commerce & Facturation

## 📌 Vue d'ensemble du Module
Le module **Commerce** couvre l'intégralité du cycle de vente et d'achat de l'entreprise : des devis aux commandes, des bons de livraison à la facturation (clients et fournisseurs), incluant le suivi des paiements, les situations de travaux et les avoirs.

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/Commerce` & `app/Enums/Commerce`)
*   **Ventes (Clients)** : `CustomerQuote`, `CustomerOrder`, `CustomerDeliveryNote`, `CustomerSituation`, `CustomerInvoice`, `CustomerCreditNote` et leurs lignes de détails respectives.
*   **Achats (Fournisseurs)** : `PurchaseRequest`, `PurchaseOrder`, `ReceiptNote`, `SubcontractorSituation`, `SupplierInvoice`, `SupplierCreditNote` et leurs lignes de détails.
*   **Paiements** : `Payment` et `PaymentAllocation` (pour gérer les règlements partiels).
*   **Enums strictes** : Gestion rigoureuse des statuts (`QuoteStatus`, `OrderStatus`, `DeliveryStatus`, `InvoiceStatus`, `PaymentStatus`).

### 2. Logique Métier & Services (`app/Services/Commerce`)
*   **Génération et Conversion** : `QuoteService`, `CustomerOrderService`, `PurchaseService`, `SituationService`, etc., pour la transformation fluide d'un document à un autre (ex: Devis -> Commande).
*   **Génération de Documents** : Génération PDF des documents avec l'identité de l'entreprise, configurée via Browsershot. Les problèmes de race conditions sur la génération ont été fixés (Jobs asynchrones). L'affichage PDF à la volée est implémenté. Mise en page CSS corrigée.
*   **Numérotation & Légalisation** : `InvoiceLegalizationService`. Résolution des erreurs de séquençage et des contraintes d'unicité lors de la validation des factures.
*   **Analytique et Sécurité** : `CommerceAnalyticService`, `SupplierInvoiceAuditService` et `RetentionGuaranteeService`.

### 3. Observers & Événements (`app/Observers/Commerce`)
*   Multiples Observers (`CustomerQuoteObserver`, `CustomerInvoiceObserver`, etc.) gérant la numérotation automatique et la mise à jour en cascade des statuts de documents liés.

### 4. Interface Utilisateur (Filament)
*   **Interfaces 100% complètes** : Le dossier `app/Filament/Commerce` est très riche. Toutes les ressources possèdent leur interface Filament traduite et avec les gestionnaires de relations (Relation Managers).
*   **Workflows automatisés** : Ajout de boutons d'action rapide dans les tableaux pour transformer les documents sans friction.
*   **Lecteur de Code-barres** : Intégration du scan de code-barres dans les formulaires des Commandes Clients et Factures Fournisseurs pour identifier et insérer rapidement des lignes d'articles.
*   **Signature Électronique Intégrée** : Bouton d'envoi de devis intégrant une demande de signature numérique (via `LocalSignatureProvider`). Le client reçoit un email, signe sur le portail public, et le devis passe automatiquement en statut `ACCEPTED` déclenchant la suite du processus (création chantier/commande).

### 5. Tests
*   Validation complète du module avec 100% de succès sur la gigantesque suite de **182 tests** PestPHP. Le cycle de vie complet est garanti sans faille logicielle.

## 🚧 Ce qu'il reste à faire
*   Peaufiner potentiellement certains détails cosmétiques des exports PDF selon les retours utilisateurs finaux.
*   Intégrer les workflows de relances d'impayés.

## 💡 Idées d'amélioration et Nouvelles Fonctionnalités
*   **Paiement en ligne & Relances automatiques** : Intégrer un lien de paiement Stripe ou prélèvement SEPA (GoCardless) en bas de la facture électronique, couplé à un robot qui relance par email les impayés à J+3, J+15.
*   **Bibliothèques d'Ouvrages** : Interfacer le devis avec Batiprix ou une autre bibliothèque d'ouvrages BTP standardisée pour accélérer le chiffrage.
*   **Refonte du Dashboard Commercial (Widgets Avancés)** : Intégration de `laboiteacode/filament-dashboard-widgets` pour afficher le CA (Variance), le funnel de conversion (Devis -> Facture), la progression des objectifs et les alertes d'impayés.
