# 🎨 FRONTEND FILAMENTPHP - MODULE COMMERCE

## 📋 ARCHITECTURE FRONTEND

### Structure des Fichiers Créés

```
app/Filament/
├── Panels/
│   └── Commerce/
│       └── CommercePanelProvider.php           ✅ Panel principal
├── Resources/
│   └── Commerce/
│       ├── CustomerQuoteResource.php           ✅ Gestion devis
│       ├── CustomerOrderResource.php           ✅ Gestion commandes
│       ├── CustomerInvoiceResource.php         ✅ Gestion factures client
│       ├── SupplierInvoiceResource.php         ✅ Gestion factures fournisseur
│       ├── PaymentResource.php                 ✅ Gestion paiements
│       └── Pages/
│           ├── ListCustomerQuotes.php
│           ├── CreateCustomerQuote.php
│           ├── EditCustomerQuote.php
│           ├── ListCustomerOrders.php
│           ├── CreateCustomerOrder.php
│           ├── EditCustomerOrder.php
│           ... (et autres pages CRUD)
├── Pages/
│   └── Dashboard/
│       └── CommerceDashboard.php              ✅ Dashboard principal
└── Widgets/
    └── Commerce/
        ├── RevenueStatsWidget.php            ✅ KPI CA
        ├── QuoteConversionWidget.php         ✅ KPI Conversion
        ├── OutstandingInvoicesWidget.php     ✅ KPI Encours
        ├── TopCustomersWidget.php            ✅ Top clients
        ├── PaymentStatusWidget.php           ✅ Statut paiements
        └── OverdueInvoicesWidget.php         ✅ Factures en retard
```

---

## 🎯 RESSOURCES FILAMENT

### 1. CommercePanelProvider
**Rôle** : Configuration du panel Commerce

**Caractéristiques** :
- URL : `/commerce`
- Couleur primaire : Bleu
- Navigation : 5 groupes (Vente, BTP, Achat, Paiements)
- Auto-découverte des Resources et Pages
- Auto-découverte des Widgets

**Configuration** :
```php
return $panel
    ->id('commerce')
    ->path('commerce')
    ->label('Commerce')
    ->colors([...])
    ->discoverResources(...)
    ->navigationGroups([...])
```

---

### 2. CustomerQuoteResource
**Modèle** : `App\Models\Commerce\CustomerQuote`

**Formulaire** :
- Informations générales (Référence, Client, Chantier, Statut, Expiration)
- Lignes de devis (Repeater avec articles, quantités, prix)
- Totaux (HT, TVA, TTC)
- Notes et conditions

**Tableau** :
- Colonnes : Numéro, Client, Statut (badge), Montant TTC, Expiration
- Filtres : Statut, Client
- Actions : Éditer, Envoyer, Voir PDF
- Tri par défaut : Récents d'abord

**Flux** :
```
Créer → (DRAFT) → Envoyer → (SENT) → Client signe → (SIGNED)
                                   ↓
                          Commande créée
```

**Statuts visuels** :
- DRAFT : Gris (crayon)
- SENT : Bleu (avion)
- SIGNED : Vert (checkmark)
- REJECTED : Rouge (X)
- EXPIRED : Orange (horloge)

---

### 3. CustomerOrderResource
**Modèle** : `App\Models\Commerce\CustomerOrder`

**Formulaire** :
- Informations (Référence, Client, Chantier, Devis, Statut, Date)
- Lignes de commande (Repeater)
- Totaux
- Conditions et adresse de livraison

**Tableau** :
- Colonnes : Numéro, Client, Chantier, Statut, Montant, Date
- Filtres : Statut, Client
- Actions : Éditer, Voir bons de livraison, Créer facture
- Tri : Récents

**Workflow** :
```
Commande (CONFIRMED) → Livraison partielle (PARTIALLY_DELIVERED)
                    → Livraison complète (DELIVERED)
                    → Facturation (BILLED)
```

**Intégrations** :
- Lien vers CustomerDeliveryNoteResource
- Création rapide de factures client

---

### 4. CustomerInvoiceResource
**Modèle** : `App\Models\Commerce\CustomerInvoice`

**Formulaire** :
- Informations (Référence, Client, Commande, Type, Statut, Échéance)
- Lignes de facture
- Totaux
- Retenues et ajustements
- Notes

**Tableau** :
- Colonnes : Numéro, Client, Type (badge), Statut (badge), Montant, Échéance, En retard (icône)
- Filtres : Statut, Type, Client, Factures en retard
- Actions : Éditer, Voir PDF, Enregistrer paiement, Relancer
- Tri : Récents

**Types de factures** :
- SIMPLE : Facture standard
- ACOMPTE : Acompte sur commande
- SITUATION : Situation de travaux BTP

**Statuts** :
- DRAFT : Gris (brouillon)
- VALIDATED : Bleu (validée, numérotée)
- PAID : Vert (payée)
- LITIGE : Rouge (contestée)
- CANCELLED : Gris (annulée)

**Indicateurs** :
- Badge "En retard" si due_date < now() et status != PAID
- Lien PDF pour téléchargement
- Action "Enregistrer paiement" redirige vers PaymentResource

---

### 5. SupplierInvoiceResource
**Modèle** : `App\Models\Commerce\SupplierInvoice`

**Formulaire** :
- Informations (Référence, Fournisseur, BC, Échéance, Statut)
- Lignes de facture
- Totaux
- Audit 3 voies (Statut, Raison litige)

**Tableau** :
- Colonnes : Numéro, Fournisseur, BC, Statut, Montant, Échéance
- Filtres : Statut, Fournisseur, Factures en litige
- Actions : Éditer, Auditer, Payer, Voir litige

**Workflow d'audit** :
```
DRAFT → AUDIT (contrôle 3 voies auto)
          ↓
       ✓ Valide → BON_A_PAYER → Paiement
       ✗ Litige → LITIGE → Correction requise
```

**Statuts d'audit** :
- DRAFT : Gris (nouvelle)
- AUDIT : Bleu (en cours de vérification)
- BON_A_PAYER : Vert (approuvée)
- LITIGE : Rouge (anomalies détectées)
- PAID : Violet (payée)

**Litiges détectables** :
- Quantité facturée > Quantité reçue
- Prix facturé > Prix BC + 2%
- Articles non commandés
- Facture sans BC

---

### 6. PaymentResource
**Modèle** : `App\Models\Commerce\Payment`

**Formulaire** :
- Informations (Référence, Type, Moyen, Date, Montant, Statut)
- Lettrage (Repeater avec allocation par facture)
- Notes

**Tableau** :
- Colonnes : Référence, Type, Moyen, Montant, Statut, Date
- Filtres : Type (in/out), Statut, Moyen
- Actions : Éditer, Supprimer

**Types** :
- `in` : Encaissement (client vers nous)
- `out` : Décaissement (nous vers fournisseur)

**Moyens** :
- Virement bancaire
- Chèque
- Espèces
- Carte bancaire
- Autre

**Lettrage** :
- Support multi-documents (Facture client, Facture fournisseur, Situations)
- Allocation flexible par document
- Passage auto en PAID quand 100% alloué

---

## 📊 DASHBOARD - CommerceDashboard

### Widgets affichés

#### 1. RevenueStatsWidget
**Affiche** :
- CA du mois (HT) - Facturé
- CA encaissé (HT) - Réellement reçu
- En attente de paiement (HT)
- Pipeline (Devis)

**Données source** : CommerceAnalyticService::getRevenueMetrics()

#### 2. QuoteConversionWidget
**Affiche** :
- Nombre de devis du mois
- Taux de conversion (%)
- Nombre de commandes

**Données source** : CommerceAnalyticService::getQuoteConversionRate()

#### 3. OutstandingInvoicesWidget
**Affiche** :
- Retard 0-30 jours (Warning)
- Retard 31-60 jours (Danger)
- Retard 60+ jours (Danger critique)

**Données source** : DuePaymentService::getCustomerAgingReport()

#### 4. TopCustomersWidget
**Affiche** :
- Top 3 clients par CA (HT)
- Affichage : Rang + Nom + CA

**Données source** : CommerceAnalyticService::getTopCustomers(3)

#### 5. PaymentStatusWidget
**Affiche** :
- À encaisser (Clients) - Warning
- À payer (Fournisseurs) - Danger

**Calcul direct** :
```php
CustomerInvoice::where('status', 'validated')->sum('total_ttc')
SupplierInvoice::where('status', 'bon_a_payer')->sum('amount_ttc')
```

#### 6. OverdueInvoicesWidget
**Affiche** :
- Nombre de factures impayées en retard
- Montant total en retard

**Filtre** :
```php
where('status', 'validated')
->where('due_date', '<', now())
```

---

## 🚀 UTILISATION

### Accès au Panel Commerce

```
URL : /commerce
Authentification : Requise (login Laravel Fortify)
Permissions : À définir via Spatie/Roles
```

### Navigation principale

```
CYCLE VENTE
├─ Devis
├─ Commandes
├─ Bons de livraison
└─ Factures client

SITUATIONS BTP
└─ Situations de travaux

CYCLE ACHAT
├─ Demandes de prix
├─ Bons de commande
└─ Factures fournisseur

PAIEMENTS
├─ Paiements
└─ Relances
```

### Workflow exemple : Devis → Commande → Facture → Paiement

```
1. Créer Devis
   [Commerce] → [Devis] → [Créer]
   Remplir : Client, Chantier, Lignes, Dates

2. Envoyer Devis
   [Devis] → [Action: Envoyer]
   Status : DRAFT → SENT
   PDF généré automatiquement
   Email au client

3. Client accepte
   Status change : SENT → SIGNED
   Commande créée automatiquement ✓

4. Créer Bon de livraison
   [Commandes] → [Commande] → [Voir BL]
   [Créer BL]
   Status : CONFIRMED → PARTIALLY_DELIVERED

5. Créer Facture
   [Commandes] → [Action: Créer facture]
   Type : SIMPLE / ACOMPTE / SITUATION
   Status : DRAFT → VALIDATED (légalisée)
   Email au client

6. Enregistrer Paiement
   [Factures] → [Action: Enregistrer paiement]
   Créer paiement avec lettrage
   Allocation : facture reçoit montant
   Status : VALIDATED → PAID (si 100% alloué)

7. Dashboard
   [Tableau de bord]
   Voir CA, conversions, retards, etc.
```

---

## 🔧 CONFIGURATION REQUISE

### Provider Filament

Dans `config/app.php`, ajouter le CommercePanelProvider :

```php
'providers' => [
    // ...
    App\Filament\Panels\Commerce\CommercePanelProvider::class,
],
```

### Routes Filament

Filament auto-découvre les resources et routes :

```
/commerce (Panel)
/commerce/customer-quotes (Devis)
/commerce/customer-orders (Commandes)
/commerce/customer-invoices (Factures client)
/commerce/supplier-invoices (Factures fournisseur)
/commerce/payments (Paiements)
```

### Base de Données

Tables requises (migrations) :
- `customer_quotes`
- `customer_orders`
- `customer_invoices`
- `supplier_invoices`
- `payments`
- `payment_allocations`
- Etc. (déjà créées aux Étapes 2-4)

---

## 🎨 PERSONNALISATION

### Ajouter des colonnes au tableau

Exemple : Ajouter nom commercial à CustomerQuoteResource

```php
Tables\Columns\TextColumn::make('client.company_name')
    ->label('Société')
    ->searchable()
```

### Ajouter des actions personnalisées

Exemple : Action "Dupliquer" une facture

```php
Tables\Actions\Action::make('duplicate')
    ->label('Dupliquer')
    ->icon('heroicon-o-document-duplicate')
    ->action(fn (CustomerInvoice $record) => 
        $record->replicate()->save()
    )
```

### Ajouter des widgets custom

Créer un nouveau widget dans `app/Filament/Widgets/Commerce/` et l'enregistrer dans le Dashboard.

---

## ✅ CHECKLIST FRONTEND - ÉTAPE 1 (COMPLÉTÉE)

- ✅ Panel Provider créé (CommercePanelProvider)
- ✅ 5 Resources créées :
  - CustomerQuoteResource
  - CustomerOrderResource
  - CustomerInvoiceResource
  - SupplierInvoiceResource
  - PaymentResource
- ✅ 6 Widgets créés pour le Dashboard
- ✅ Dashboard principal (CommerceDashboard)
- ✅ Navigation organisée en groupes
- ✅ Actions contextuelles (Envoyer, Auditer, Payer)
- ✅ Filtres et recherche
- ✅ Intégrations entre ressources

---

## 📋 ÉTAPE 2 FRONTEND - À FAIRE

Pages supplémentaires recommandées :
- Pages personnalisées CRUD (ListPages, CreatePages, EditPages)
- Relation Managers pour naviguer entre ressources
- Pages spécialisées (Ex: PageAuditInvoices)
- Modales personnalisées (Ex: Modal de relance de paiement)
- Exports (PDF, Excel) via Actions
- Rapports (Aging, Top clients, Marges par chantier)

---

## 🚀 PROCHAINES ÉTAPES

Vous pouvez maintenant :

1. **Tester l'application** :
   ```bash
   php artisan serve
   # Accéder à http://localhost:8000/commerce
   ```

2. **Personnaliser le design** :
   - Modifier les couleurs
   - Ajouter des logos
   - Ajuster les layouts

3. **Ajouter des reports/analytics avancés** :
   - Graphiques de tendances
   - Exports PDF/Excel
   - Tableaux de synthèse

4. **Mettre en place les permissions** :
   - Spatie/Laravel-permission
   - Rôles : Admin, Commercial, Comptable, Achats

5. **Déployer en production** :
   - Database migrations
   - Configuration SSL
   - Monitoring

---

**FIN DE L'ÉTAPE 1 FRONTEND - PANEL ET RESSOURCES FILAMENT COMPLÉTÉS** ✅
