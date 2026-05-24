# 📊 RELATION MANAGERS - MODULE COMMERCE

## 🎯 Vue d'ensemble

Les Relation Managers permettent de naviguer et gérer les relations entre les models depuis la Resource parente.

**6 Relation Managers créés** avec actions métier intégrées :

```
Devis
├─ OrdersRelationManager (Commandes créées)
│
Commande
├─ DeliveryNotesRelationManager (Bons de livraison)
├─ InvoicesRelationManager (Factures client)
└─ SituationsRelationManager (Situations BTP)
│
Bon de Commande Fournisseur
├─ ReceiptNotesRelationManager (Bons de réception)
│
Facture Fournisseur
└─ PaymentAllocationsRelationManager (Paiements alloués)
```

---

## 📝 INTÉGRATION DANS LES RESOURCES

### 1. OrdersRelationManager
**Intégration dans CustomerQuoteResource** :

```php
// Ajouter dans getRelations() de CustomerQuoteResource
public static function getRelations(): array
{
    return [
        RelationManagers\OrdersRelationManager::class,
    ];
}
```

**Fonctionnalités** :
- ✅ Lister les commandes créées à partir du devis
- ✅ Créer une nouvelle commande (duplie les lignes auto)
- ✅ Voir/Éditer une commande
- ✅ Créer un BL depuis la commande
- ✅ Créer une facture depuis la commande

**Workflow** :
```
Devis → [Tab: Commandes]
       ├─ [Nouvelle commande] → Copie lignes + prix
       ├─ [Voir BL] → Redirection vers DeliveryNotesRM
       └─ [Créer facture] → Redirection vers InvoicesRM
```

---

### 2. DeliveryNotesRelationManager
**Intégration dans CustomerOrderResource** :

```php
public static function getRelations(): array
{
    return [
        RelationManagers\DeliveryNotesRelationManager::class,
    ];
}
```

**Fonctionnalités** :
- ✅ Lister tous les bons de livraison
- ✅ Créer un nouveau BL avec lignes partielles
- ✅ Mise à jour auto du statut commande (PARTIALLY_DELIVERED/DELIVERED)
- ✅ Voir/Éditer un BL
- ✅ Générer PDF du BL
- ✅ Créer facture depuis BL

**Workflow** :
```
Commande (CONFIRMED) → [Tab: BL]
                      ├─ [Nouveau BL]
                      │  └─ Entrée : Articles livrés (qty partielle possible)
                      │  └─ Mise à jour : Commande → PARTIALLY_DELIVERED
                      ├─ [PDF] → Télécharge le bon de livraison
                      └─ [Créer facture] → Crée facture simple
```

**Calcul statut commande** :
```php
if (total_delivered == total_commande)
    status = DELIVERED
else
    status = PARTIALLY_DELIVERED
```

---

### 3. InvoicesRelationManager
**Intégration dans CustomerOrderResource** :

```php
public static function getRelations(): array
{
    return [
        RelationManagers\InvoicesRelationManager::class,
    ];
}
```

**Fonctionnalités** :
- ✅ Lister factures associées à la commande
- ✅ Créer facture (3 types : Simple, Acompte, Situation)
- ✅ Légalisation automatique (numéro séquentiel, hash NF525)
- ✅ Génération PDF
- ✅ Créer paiement directement
- ✅ Envoyer relance (pré-remplit template)
- ✅ Créer avoir (gestion retours)

**Workflow créer facture** :
```
[Nouvelle facture]
├─ Type : SIMPLE / ACOMPTE / SITUATION
├─ Si ACOMPTE → Saisir montant
├─ Montant HT auto depuis commande (sauf acompte)
├─ Création facture (statut DRAFT)
├─ Légalisation automatique :
│  ├─ Numéro : FC-2026-00123
│  ├─ Hash NF525
│  └─ Passage VALIDATED
└─ Email client auto
```

**Actions facture** :
```
Facture (VALIDATED)
├─ [PDF] → Voir/télécharger
├─ [Payer] → Crée paiement + lettrage
├─ [Relancer] → Email relance (si overdue)
└─ [Créer avoir] → Geste commercial / retour
```

---

### 4. ReceiptNotesRelationManager
**Intégration dans PurchaseOrderResource** (À créer) :

```php
public static function getRelations(): array
{
    return [
        RelationManagers\ReceiptNotesRelationManager::class,
    ];
}
```

**Fonctionnalités** :
- ✅ Lister les BR pour une BC
- ✅ Créer BR avec articles reçus (quantities partielles)
- ✅ Génération auto du numéro BR
- ✅ Mise à jour stock (optionnel)
- ✅ Créer facture fournisseur à partir du BR

**Workflow** :
```
BC (Bon de Commande) → [Tab: Bons de réception]
                      ├─ [Nouveau BR]
                      │  ├─ Date réception
                      │  └─ Lignes reçues (qty peut être partielle)
                      │
                      └─ [Créer facture]
                         └─ Pré-remplie avec articles du BR
                         └─ Lancera audit 3 voies auto
```

---

### 5. SituationsRelationManager
**Intégration dans CustomerOrderResource** :

```php
public static function getRelations(): array
{
    return [
        RelationManagers\SituationsRelationManager::class,
    ];
}
```

**Fonctionnalités** :
- ✅ Lister situations de travaux (BTP)
- ✅ Créer situation avec avancement par section
- ✅ Calcul auto : Retenue 5%, Prorata, Delta
- ✅ Création auto de facture situation
- ✅ Légalisation auto de la facture
- ✅ Voir PDF situation + facture

**Workflow création situation** :
```
[Nouvelle situation]
├─ Avancement par section :
│  ├─ Section 1 : 100% (complétée)
│  ├─ Section 2 : 45% (en cours)
│  └─ Section 3 : 0% (pas commencée)
│
├─ Retenue de garantie : 5% (légal)
├─ Compte prorata : 0.5% (optionnel)
│
├─ Calcul automatique :
│  ├─ Avancement global : 48,3%
│  ├─ Montant ce mois : Avancement% * Total - Déjà facturé
│  ├─ Retenue : 5% du montant
│  └─ Net à facturer : Montant - Retenue - Prorata
│
├─ Création facture situation auto
├─ Légalisation auto (numéro séquentiel)
└─ Email client auto
```

---

### 6. PaymentAllocationsRelationManager
**Intégration dans SupplierInvoiceResource** :

```php
public static function getRelations(): array
{
    return [
        RelationManagers\PaymentAllocationsRelationManager::class,
    ];
}
```

**Fonctionnalités** :
- ✅ Lister tous les paiements lettrés
- ✅ Créer nouveau lettrage (allocation)
- ✅ Support multi-allocations (même facture, plusieurs paiements)
- ✅ Passage auto en PAID si 100% alloué
- ✅ Supprimer allocation (dé-lettrage)

**Workflow** :
```
Facture Fournisseur → [Tab: Paiements alloués]
                    ├─ [Nouveau lettrage]
                    │  ├─ Choisir paiement
                    │  └─ Saisir montant alloué
                    │
                    ├─ Vérification auto :
                    │  └─ Si total == facture TTC → PAID
                    │
                    └─ [Supprimer] → Dé-lettrage
```

---

## 🔄 INTÉGRATIONS CROSS-RESOURCE

### Customer Quote → Customer Order
```
Devis.OrdersRelationManager
├─ [Nouvelle commande]
│  ├─ Copie client_id
│  ├─ Copie chantier_id
│  ├─ Duplique lignes (immuable)
│  └─ Copie totaux HT/TTC
│
└─ Commande créée
   └─ Peut maintenant :
      ├─ Créer bons de livraison
      ├─ Créer factures
      └─ Créer situations BTP
```

### Customer Order → Delivery Note → Invoice
```
Commande
├─ DeliveryNotesRelationManager
│  ├─ [Nouveau BL]
│  │  ├─ Entrée : Articles livrés (qty partielles)
│  │  └─ Mise à jour statut commande
│  │
│  └─ InvoicesRelationManager
│     ├─ [Nouvelle facture]
│     │  ├─ Pré-remplie avec lignes commande
│     │  ├─ Légalisation auto
│     │  └─ Email client auto
│     │
│     └─ PaymentResource
│        └─ [Enregistrer paiement]
│           └─ Lettrage + passage PAID
```

### Purchase Order → Receipt Note → Supplier Invoice
```
BC
├─ ReceiptNotesRelationManager
│  ├─ [Nouveau BR]
│  │  ├─ Entrée : Articles reçus
│  │  └─ Mise à jour stock
│  │
│  └─ [Créer facture fournisseur]
│     ├─ Pré-remplie avec articles BR
│     ├─ Audit 3 voies auto
│     │  ├─ Qty facturée ≤ Qty reçue ?
│     │  └─ Prix facturé ≤ Prix BC + 2% ?
│     │
│     ├─ Si VALIDE → BON_A_PAYER
│     │  └─ PaymentResource
│     │     └─ [Enregistrer paiement]
│     │
│     └─ Si INVALIDE → LITIGE
│        └─ Notification service Achats
```

---

## ✅ INTÉGRATION COMPLÈTE

### 1. Enregistrer les Relations dans les Resources

**CustomerQuoteResource** :
```php
public static function getRelations(): array
{
    return [
        RelationManagers\OrdersRelationManager::class,
    ];
}
```

**CustomerOrderResource** :
```php
public static function getRelations(): array
{
    return [
        RelationManagers\DeliveryNotesRelationManager::class,
        RelationManagers\InvoicesRelationManager::class,
        RelationManagers\SituationsRelationManager::class,
    ];
}
```

**PurchaseOrderResource** (À créer) :
```php
public static function getRelations(): array
{
    return [
        RelationManagers\ReceiptNotesRelationManager::class,
    ];
}
```

**SupplierInvoiceResource** :
```php
public static function getRelations(): array
{
    return [
        RelationManagers\PaymentAllocationsRelationManager::class,
    ];
}
```

### 2. Enregistrer les Resources manquantes

Resources à créer :
- ❌ PurchaseRequestResource
- ❌ PurchaseOrderResource
- ❌ CustomerDeliveryNoteResource
- ❌ CustomerSituationResource
- ❌ ReceiptNoteResource

### 3. Créer les Pages CRUD

Pour chaque RelationManager, Filament génère auto les pages CRUD.

---

## 📊 RÉSUMÉ DES RELATIONS

| Parent | Child | RelationManager | Actions |
|--------|-------|-----------------|---------|
| Quote | Orders | OrdersRelationManager | Créer, Voir, Éditer, Créer BL/Facture |
| Order | DeliveryNotes | DeliveryNotesRelationManager | Créer, Voir, Éditer, PDF, Facturer |
| Order | Invoices | InvoicesRelationManager | Créer, Voir, Éditer, PDF, Payer, Relancer, Avoir |
| Order | Situations | SituationsRelationManager | Créer, Voir, PDF, Facture liée |
| PurchaseOrder | ReceiptNotes | ReceiptNotesRelationManager | Créer, Voir, Éditer, Créer Facture Fournisseur |
| SupplierInvoice | Allocations | PaymentAllocationsRelationManager | Créer, Supprimer, Lettrage auto |

---

## 🚀 ACTIVACIÓN

Une fois créés, les Relation Managers apparaissent automatiquement sous forme d'onglets dans la Resource parente :

```
Devis (View/Edit)
├─ Informations générales
├─ Lignes de devis
├─ Totaux
└─ [TAB] Commandes ← OrdersRelationManager
   ├─ Tableau avec commandes créées
   ├─ [+Nouvelle commande]
   ├─ Actions : Voir, Créer BL, Créer facture
   └─ Filtres et tri
```

---

**FIN DE LA DOCUMENTATION RELATION MANAGERS** ✅
