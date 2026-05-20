# 📊 MODULE COMMERCE - ÉTAPE 5 : AUTOMATISMES

## 🎯 OBJECTIF DE L'ÉTAPE 5

Développer **l'orchestration automatique** du module Commerce via :
- **Observers** : Actions déclenchées par les événements de modèles
- **Jobs** : Tâches asynchrones en arrière-plan
- **Commands** : Scripts CLI pour exécution manuelle ou planifiée
- **Notifications** : Alertes utilisateurs (email + in-app)

---

## 📋 STRUCTURE DÉVELOPPÉE

### **1. OBSERVERS (Événements de modèles)**

#### 1.1. CustomerQuoteObserver
**Fichier** : `app/Observers/Commerce/CustomerQuoteObserver.php`

**Événements écoutés** :
- `created()` : Initialisation du devis
- `updated()` : Transitions de statut

**Actions automatiques** :

```
DRAFT → SENT
├─ Génération PDF du devis
├─ Stockage du chemin PDF
├─ Notification client (QuoteSentNotification)
└─ Log d'audit

SENT → SIGNED
├─ Notification au commercial (QuoteAcceptedNotification)
└─ Log : "Devis accepté par [Client]"

SENT → REJECTED
├─ Notification au commercial (QuoteRejectedNotification)
└─ Log : "Devis rejeté par [Client]"
```

**Exemple d'utilisation** :
```php
// Dans le modèle CustomerQuote :
protected static function booted(): void
{
    static::observe(CustomerQuoteObserver::class);
}

// Automatiquement, l'observer sera déclenché
$quote->update(['status' => QuoteStatus::SENT]);
// → PDF généré + Email client + Log
```

---

#### 1.2. CustomerInvoiceObserver
**Fichier** : `app/Observers/Commerce/CustomerInvoiceObserver.php`

**Événements écoutés** :
- `created()` : Création de facture
- `updated()` : Transitions de statut

**Actions automatiques** :

```
CREATED
├─ Définition échéance par défaut (30 jours)
├─ Génération PDF de la facture
├─ Stockage du chemin PDF
└─ Log

DRAFT → VALIDATED
├─ Appel du service InvoiceLegalizationService
├─ Attribution numéro séquentiel définitif
├─ Verrouillage NF525 (anti-fraude)
├─ Notification client (InvoiceGeneratedNotification)
├─ Job : SendCustomerInvoiceEmailJob (asynchrone)
└─ Log : "Facture légalisée"

VALIDATED → PAID
├─ Notification client (InvoicePaidNotification)
└─ Log : "Facture payée intégralement"

VALIDATED → CANCELLED
└─ Log warning : "Facture annulée"
```

**Exemple** :
```php
// Protected auto_send = true → Génération et envoi auto
$invoice = CustomerInvoice::create([...]);
// → PDF généré
// → Client notifié par email asynchronement
// → Facture verrouillée légalement
```

---

#### 1.3. SupplierInvoiceObserver
**Fichier** : `app/Observers/Commerce/SupplierInvoiceObserver.php`

**Événements écoutés** :
- `created()` : Enregistrement de facture fournisseur
- `updated()` : Transitions de statut

**Actions automatiques** :

```
CREATED
├─ Définition échéance par défaut (30 jours)
└─ Log

DRAFT → AUDIT
├─ Appel SupplierInvoiceAuditService::auditInvoice()
├─ Contrôle 3 voies automatique :
│  ├─ Quantité facturée ≤ Quantité reçue ?
│  └─ Prix facturé ≤ Prix BC + 2% ?
├─ Si VALIDE → Passage en BON_A_PAYER
│  └─ Notification Trésorerie (SupplierInvoiceReadyForPaymentNotification)
└─ Si INVALID → Passage en LITIGE
   └─ Notification Achats (SupplierInvoiceAuditFailedNotification)

AUDIT → BON_A_PAYER
├─ Notification au service Compta/Trésorerie
└─ Log : "Facture validée - Bon à payer"

AUDIT → LITIGE
├─ Blocage automatique du paiement
├─ Notification service Achats avec litiges détectés
└─ Log warning : "Facture en litige - Raisons : ..."
```

**Exemple** :
```php
// Facture fournisseur créée et auditée
$supplierInvoice = SupplierInvoice::create([...]);
// → Audit automatique lancé
// → Si OK : "BON À PAYER" + notification Compta
// → Si KO : "LITIGE" + notification Achats + blocage paiement
```

---

## 🔧 JOBS (Tâches asynchrones)

### 2.1. SendCustomerInvoiceEmailJob
**Fichier** : `app/Jobs/Commerce/SendCustomerInvoiceEmailJob.php`

**Déclencheur** : Observer `CustomerInvoiceObserver::handleInvoiceLegalized()`

**Configuration** :
- Retry : 3 tentatives
- Délai de retry : 1min, puis 5min
- Timeout : 120 secondes

**Processus** :
```
1. Vérification du statut VALIDATED
2. Récupération contact principal du client
3. Envoi email avec PDF en pièce jointe
4. Sauvegarde timestamp d'envoi
5. En cas d'échec après 3 retry :
   └─ Notification admins + Log critique
```

**Exemple de dispatch** :
```php
// Déclenché automatiquement par l'observer
SendCustomerInvoiceEmailJob::dispatch($invoice);

// Ou manuellement
SendCustomerInvoiceEmailJob::dispatch($invoice)->delay(now()->addMinutes(5));
```

---

### 2.2. CheckOverdueInvoicesJob
**Fichier** : `app/Jobs/Commerce/CheckOverdueInvoicesJob.php`

**Fréquence** : **Quotidienne** (via scheduler Laravel)

**Processus** :
```
1. Récupération factures VALIDATED en retard de paiement
2. Pour chaque facture :
   ├─ Calcul jours de retard
   ├─ Détermination niveau relance (1, 2, ou 3)
   ├─ Vérification : pas de relance récente
   └─ Envoi notification appropriée
```

**Niveaux de relance** :
```
Niveau 1 : 0-30 jours → Relance amiable
           "Nous vous rappelons que..."
           
Niveau 2 : 31-60 jours → Mise en demeure
           "Nous mettons en demeure le paiement..."
           
Niveau 3 : 60+ jours → Dernière relance
           "Ceci est notre dernière relance avant procédure..."
```

**Configuration du Scheduler** :
```php
// Dans routes/console.php ou app/Console/Kernel.php
$schedule->job(CheckOverdueInvoicesJob::class)
    ->daily()
    ->at('09:00');  // 9h du matin
```

---

### 2.3. CheckExpiredQuotesJob
**Fichier** : `app/Jobs/Commerce/CheckExpiredQuotesJob.php`

**Fréquence** : **Quotidienne** (via scheduler)

**Processus** :
```
1. Récupération devis SENT/DRAFT expirés (expires_at < now())
2. Pour chaque devis :
   ├─ Passage en EXPIRED
   ├─ Sauvegarde timestamp d'expiration
   └─ Notification au commercial
```

**Configuration du Scheduler** :
```php
$schedule->job(CheckExpiredQuotesJob::class)
    ->daily()
    ->at('10:00');  // 10h du matin
```

---

## 💻 COMMANDS (Scripts CLI)

### 3.1. CommerceOrchestratorCommand
**Fichier** : `app/Console/Commands/Commerce/CommerceOrchestratorCommand.php`

**Signature** :
```bash
php artisan commerce:orchestrator [--check-quotes] [--check-invoices] [--all]
```

**Options** :
- `--check-quotes` : Vérifie uniquement les devis expirés
- `--check-invoices` : Vérifie uniquement les factures impayées
- `--all` : Exécute tous les contrôles (par défaut)

**Utilisation** :
```bash
# Exécuter tous les contrôles
php artisan commerce:orchestrator

# Vérifier uniquement les factures impayées
php artisan commerce:orchestrator --check-invoices

# Vérifier uniquement les devis expirés
php artisan commerce:orchestrator --check-quotes
```

**Sortie console** :
```
╔════════════════════════════════════════════════════════╗
║     BATISTACK - Commerce Orchestrator                  ║
╚════════════════════════════════════════════════════════╝

🔍 Lancement du scan des devis expirés...
   ✓ Job envoyé en file d'attente

🔍 Lancement du scan des factures impayées...
   ✓ Job envoyé en file d'attente

✅ Tous les jobs ont été envoyés avec succès.
```

---

## 🔔 NOTIFICATIONS

### 4.1. Notifications Développées
**Fichier** : `app/Notifications/Commerce/CommerceNotifications.php`

Toutes les notifications :
- ✅ Implémentent `ShouldQueue` (asynchrone)
- ✅ Supportent mail + database (in-app)
- ✅ Incluent contenu formaté + actions

#### **QuoteSentNotification**
- **Destinataire** : Contact principal du client
- **Canal** : Mail + Database
- **Contenu** :
  - Numéro du devis
  - Montants HT/TTC
  - Date d'expiration
  - Lien pour consulter

#### **QuoteRejectedNotification**
- **Destinataire** : Commercial (créateur du devis)
- **Type** : Danger (rouge)
- **Contenu** : Alerte + lien pour investigation

#### **QuoteExpiredNotification**
- **Destinataire** : Commercial
- **Type** : Warning (orange)
- **Contenu** : Date expiration + suggestion relance

#### **InvoiceGeneratedNotification**
- **Destinataire** : Contact principal du client
- **Type** : Success (vert)
- **Contenu** :
  - Numéro facture
  - Montant TTC
  - Échéance de paiement
  - Lien PDF

#### **PaymentReminderNotification**
- **Destinataire** : Contact client
- **Type** : Primary/Warning/Danger (selon niveau)
- **Niveaux** :
  1. "⏰ Rappel de paiement"
  2. "🚨 Mise en demeure"
  3. "⚖️ Dernière relance"

#### **SupplierInvoiceAuditFailedNotification**
- **Destinataire** : Service Achats
- **Type** : Danger
- **Contenu** : Liste des litiges détectés

#### **SupplierInvoiceReadyForPaymentNotification**
- **Destinataire** : Service Trésorerie
- **Type** : Success
- **Contenu** : "BON À PAYER" + montant

---

## 📊 DIAGRAMME DE FLUX COMPLET

```
┌─────────────────────────────────────────────────────────────┐
│                    CYCLE DEVIS                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  CustomerQuote::create()                                   │
│       │                                                    │
│       ▼ Observer created()                                │
│    expires_at défini (30j)                               │
│                                                            │
│  $quote->update(['status' => SENT])                       │
│       │                                                    │
│       ▼ Observer updated()                                │
│    ├─ PDF généré                                          │
│    ├─ Email client (QuoteSentNotification)               │
│    └─ Log d'audit                                         │
│                                                            │
│  Job: CheckExpiredQuotesJob (daily à 10h)               │
│       │                                                    │
│       ▼ (Si expires_at < now())                         │
│    ├─ Passage en EXPIRED                                │
│    └─ Notification commercial                            │
│                                                            │
│  $quote->update(['status' => SIGNED])                    │
│       │                                                    │
│       ▼ Observer updated()                                │
│    ├─ QuoteAcceptedNotification                          │
│    └─ Création Commande + Chantier                       │
│                                                            │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                   CYCLE FACTURE CLIENT                       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  CustomerInvoice::create()                                │
│       │                                                    │
│       ▼ Observer created()                                │
│    ├─ due_date définie (30j)                             │
│    ├─ PDF généré                                          │
│    └─ Log d'audit                                         │
│                                                            │
│  $invoice->update(['status' => VALIDATED])               │
│       │                                                    │
│       ▼ Observer updated()                                │
│    ├─ Légalisation NF525 (numéro séquentiel)            │
│    ├─ Notification client (InvoiceGeneratedNotification) │
│    └─ Job: SendCustomerInvoiceEmailJob (async)          │
│                 └─ Email + PDF en pièce jointe           │
│                                                            │
│  Job: CheckOverdueInvoicesJob (daily à 09h)             │
│       │                                                    │
│       ▼ (Si due_date < now())                           │
│    └─ Relance selon niveau de retard (1, 2 ou 3)        │
│       └─ PaymentReminderNotification au client           │
│                                                            │
│  Payment::allocatePayment($invoice)                       │
│       │                                                    │
│       ▼ (Si payé totalement)                             │
│    ├─ Passage en PAID                                     │
│    ├─ Notification client (InvoicePaidNotification)      │
│    └─ Log : "Facture payée intégralement"               │
│                                                            │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│              CYCLE FACTURE FOURNISSEUR                       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  SupplierInvoice::create()                               │
│       │                                                    │
│       ▼ Observer created()                                │
│    └─ due_date définie (30j)                             │
│                                                            │
│  $invoice->update(['status' => AUDIT])                   │
│       │                                                    │
│       ▼ Observer updated()                                │
│    ├─ Contrôle 3 voies :                                │
│    │  ├─ Quantité facturée ≤ Quantité reçue ?          │
│    │  └─ Prix facturé ≤ Prix BC + 2% ?                │
│    │                                                      │
│    ├─ Si VALIDE → VALIDATED                             │
│    │  ├─ Notification : SupplierInvoiceReadyForPayment │
│    │  └─ "BON À PAYER"                                 │
│    │                                                      │
│    └─ Si INVALIDE → LITIGE                              │
│       ├─ Blocage du paiement                            │
│       ├─ Notification : SupplierInvoiceAuditFailed     │
│       └─ Liste des litiges envoyée                      │
│                                                            │
│  Payment::allocatePayment($invoice) [BON_A_PAYER seul]  │
│       │                                                    │
│       └─ Passage en PAID (après paiement)               │
│                                                            │
└─────────────────────────────────────────────────────────────┘
```

---

## ⚙️ CONFIGURATION DU SCHEDULER LARAVEL

Dans `app/Console/Kernel.php` ou `routes/console.php` :

```php
use App\Jobs\Commerce\CheckExpiredQuotesJob;
use App\Jobs\Commerce\CheckOverdueInvoicesJob;

protected function schedule(Schedule $schedule)
{
    // Commerce : Devis expirés (quotidien à 10h)
    $schedule->job(CheckExpiredQuotesJob::class)
        ->daily()
        ->at('10:00')
        ->name('commerce:check-expired-quotes')
        ->withoutOverlapping();

    // Commerce : Factures impayées (quotidien à 09h)
    $schedule->job(CheckOverdueInvoicesJob::class)
        ->daily()
        ->at('09:00')
        ->name('commerce:check-overdue-invoices')
        ->withoutOverlapping();
}
```

**Pour que le scheduler s'exécute** :
```bash
# À ajouter dans cron (crontab -e)
* * * * * cd /chemin/vers/app && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📦 ENREGISTREMENT DES OBSERVERS

Dans `app/Providers/AppServiceProvider.php` ou créer un `CommerceServiceProvider.php` :

```php
<?php

namespace App\Providers;

use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerQuote;
use App\Models\Commerce\SupplierInvoice;
use App\Observers\Commerce\CustomerInvoiceObserver;
use App\Observers\Commerce\CustomerQuoteObserver;
use App\Observers\Commerce\SupplierInvoiceObserver;
use Illuminate\Support\ServiceProvider;

class CommerceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Enregistrement des observers
        CustomerQuote::observe(CustomerQuoteObserver::class);
        CustomerInvoice::observe(CustomerInvoiceObserver::class);
        SupplierInvoice::observe(SupplierInvoiceObserver::class);
    }
}
```

N'oubliez pas de register le provider dans `config/app.php` :

```php
'providers' => [
    // ...
    App\Providers\CommerceServiceProvider::class,
],
```

---

## ✅ CHECKLIST ÉTAPE 5

- ✅ **3 Observers** développés
  - CustomerQuoteObserver
  - CustomerInvoiceObserver
  - SupplierInvoiceObserver

- ✅ **3 Jobs** développés
  - SendCustomerInvoiceEmailJob
  - CheckOverdueInvoicesJob
  - CheckExpiredQuotesJob

- ✅ **1 Command** développée
  - CommerceOrchestratorCommand

- ✅ **7 Notifications** développées
  - QuoteSentNotification
  - QuoteRejectedNotification
  - QuoteExpiredNotification
  - InvoiceGeneratedNotification
  - PaymentReminderNotification
  - SupplierInvoiceAuditFailedNotification
  - SupplierInvoiceReadyForPaymentNotification

---

## 🎯 RÉSUMÉ FINAL

**L'Étape 5 fournit** :
- ✅ Automatisation complète du cycle de vie des devis, factures client et fournisseur
- ✅ Notifications contextuelle pour chaque événement métier
- ✅ Audit automatique des factures fournisseur (anti-fraude)
- ✅ Relances intelligentes de paiement (3 niveaux)
- ✅ Détection automatique des devis/factures expirés
- ✅ Exécution asynchrone via la file d'attente (queue)
- ✅ Scheduler pour les tâches quotidiennes

**Le module Commerce est maintenant entièrement automatisé et prêt pour la Étape 6 (Tests)** 🚀

---

**FIN DE L'ÉTAPE 5 - AUTOMATISMES**
