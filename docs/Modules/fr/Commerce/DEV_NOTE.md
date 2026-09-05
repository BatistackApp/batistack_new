---
title: DEV NOTE
icon: heroicon-o-document-text
order: 99
---

# 🛒 Module Commerce & Facturation

## 📌 Vue d'ensemble du Module
Le module **Commerce** couvre l'intégralité du cycle de vente et d'achat de l'entreprise : des devis aux commandes, des bons de livraison à la facturation (clients et fournisseurs), incluant le suivi des paiements, les situations de travaux et les avoirs.

## 📌 État Actuel (Ce qui est fait)

### 1. Modèles de Données & Enums (`app/Models/Commerce` & `app/Enums/Commerce`)
*   **Ventes (Clients)** : `CustomerQuote`, `CustomerOrder`, `CustomerDeliveryNote`, `CustomerSituation`, `CustomerInvoice`, `CustomerCreditNote` et leurs lignes de détails respectives.
*   **Achats (Fournisseurs)** : `PurchaseRequest`, `PurchaseOrder`, `ReceiptNote`, `SubcontractorSituation`, `SupplierInvoice`, `SupplierCreditNote` et leurs lignes de détails.
*   **Paiements** : `Payment` et `PaymentAllocation` (pour gérer les règlements partiels et l'allocation des paiements aux factures).
*   **Enums strictes** : Gestion rigoureuse des statuts (`QuoteStatus`, `OrderStatus`, `DeliveryStatus`, `InvoiceStatus`, `PaymentStatus`).

### 2. Logique Métier & Services (`app/Services/Commerce`)
*   **Génération et Conversion** : `QuoteService`, `CustomerOrderService`, `PurchaseService`, `SituationService`, etc., pour la transformation fluide d'un document à un autre (ex: Devis -> Commande).
*   **Génération de Documents** : Génération PDF des documents avec l'identité de l'entreprise (configurée via Browsershot), incluant désormais les bons de commande (client/fournisseur), situations de travaux, audits de factures fournisseurs, et relevés de compte client. L'affichage PDF à la volée est implémenté avec gestion asynchrone pour éviter les race conditions.
*   **Numérotation & Légalisation** : `InvoiceLegalizationService`. Résolution des erreurs de séquençage et des contraintes d'unicité lors de la validation des factures.
*   **Analytique et Sécurité** : `CommerceAnalyticService`, `SupplierInvoiceAuditService` et `RetentionGuaranteeService`.
*   **Relances Automatiques d'Impayés (Dunning Process)** : Tâche planifiée (`commerce:process-dunning` via `DuePaymentService`) vérifiant chaque nuit les factures échues et envoyant automatiquement des e-mails graduels (Relance amiable à J+3, J+15, puis Mise en demeure avec ajout automatique des indemnités forfaitaires de 40€ et pénalités de retard à J+30).

*   **Paiement en Ligne Sécurisé (Stripe)** : Intégration de l'API Stripe pour le paiement des factures. Un QR Code cliquable est généré sur le PDF des factures validées menant vers une page Checkout sécurisée. Un webhook Stripe écoute les succès de paiement pour automatiser la création de `Payment` et son `PaymentAllocation`, ce qui bascule la facture en "Payée" sans intervention humaine.

### 3. Observers & Événements (`app/Observers/Commerce`)
*   Multiples Observers (`CustomerQuoteObserver`, `CustomerInvoiceObserver`, etc.) gérant la numérotation automatique et la mise à jour en cascade des statuts de documents liés.

### 4. Interface Utilisateur (Filament)
*   **Interfaces 100% complètes** : Le dossier `app/Filament/Commerce` est très riche. Toutes les ressources possèdent leur interface Filament traduite et avec les gestionnaires de relations (Relation Managers).
*   **Workflows automatisés** : Ajout de boutons d'action rapide dans les tableaux pour transformer les documents sans friction.
*   **Lecteur de Code-barres** : Intégration du scan de code-barres dans les formulaires des Commandes Clients et Factures Fournisseurs pour identifier et insérer rapidement des lignes d'articles.
*   **Signature Électronique Intégrée** : Bouton d'envoi de devis intégrant une demande de signature numérique (via `LocalSignatureProvider`). Le client reçoit un email, signe sur le portail public, et le devis passe automatiquement en statut `ACCEPTED` déclenchant la suite du processus (création chantier/commande).
*   **Refonte du Dashboard Commercial** : Les widgets avancés (`laboiteacode`) sont développés (CA avec variance, entonnoir de conversion paramétrable, progression des objectifs mensuels, alertes d'impayés) et **câblés sur le Dashboard** via `getWidgets()` (7 widgets).

### 5. Tests
*   Validation complète du module avec 100% de succès sur la gigantesque suite de tests PestPHP (incluant les nouvelles fonctionnalités : rapports PDF, allocations de paiements, annulations, dé-lettrages et paiements Stripe en ligne avec webhooks). Le cycle de vie complet est garanti sans faille logicielle.
*   **Harmonisation des exports PDF** (Issue #329) : En-têtes de documents standardisés (gros titre bleu `#1e40af` + référence) via `pdf.layout`, couleurs homogènes (bleu Batistack partout, suppression du `#575aff` dans la situation), devise unifiée en `€`, accents corrigés (relevé client), pieds de page dupliqués retirés (utilise celui du layout), CSS dupliqués nettoyés. **Implémentation complète des gabarits stubs** : Bon de Commande Fournisseur (`purchase_order`) et Rapport d'Audit Facture Fournisseur (`supplier_invoice_audit`).

## 🚧 Ce qu'il reste à faire
*   *(Peaufiner les détails cosmétiques des exports PDF selon retours utilisateurs finaux — fait, voir Issue #329.)*
