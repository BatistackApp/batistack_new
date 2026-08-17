---
title: Paiement des salaires par API
order: 4
---

# 💳 Paiement des Salaires par API Bancaire (Open Banking)

Le module Paie peut initier les virements SEPA des bulletins **directement via l'API bancaire (Bridge Payment Initiation)**, sans fichier XML SEPA à importer manuellement.

## 🔑 Principes

- **Fournisseur** : Bridge Payment Initiation (`https://api.bridgeapi.io`).
- **Compte émetteur** : un compte bancaire connecté via Bridge (`bridge_bank_id` non nul sur `bank_accounts`).
- **Regroupement (bulk)** : un **run de paiement** = une requête de paiement Bridge regroupant un virement par bulletin.
- **Flux interactif** : Bridge ne débite pas automatiquement. Après l'initiation, un **`consent_url` (valable ~15 min)** est retourné. Le payeur doit l'ouvrir et **valider les virements à sa banque** (2FA). Les virements ne sont exécutés qu'après cette validation.
- **Asynchrone + polling** : une fois validé, le statut est interrogé périodiquement et le bulletin passe automatiquement à **PAID**.

## ⚙️ Configuration

Dans `.env` :

```env
BRIDGE_PAYMENTS_SANDBOX=true
BRIDGE_PAYMENTS_CLIENT_ID=
BRIDGE_PAYMENTS_CLIENT_SECRET=
BRIDGE_PAYMENTS_CALLBACK_URL=
```

Le bloc `bridge.payments` est défini dans `config/services.php`. En sandbox, l'API Bridge de test est utilisée. Les credentials sont **distincts** de ceux de l'agrégation (`bridge.client_id`/`bridge.client_secret`).

## 🧩 Composants

- `App\Services\Banque\BridgePaymentService` : couche API (initiation `POST /v3/payment/payment-requests`, lecture du statut, mapping des statuts Bridge → statuts métier).
- `App\Services\Paie\SalaryPaymentService` :
  - `createRun()` : crée un run idempotent (clé `idempotency_key` = hash `compte:période:signature`) ;
  - `initiateRun()` : envoie la requête bulk à Bridge et stocke `consent_url` + `bridge_payment_request_id` ;
  - `pollRun()` : interroge Bridge, met à jour le run/lignes et marque les bulletins **PAID** + `payment_date` sur succès.
- `App\Enums\Paie\SalaryPaymentStatus` : `PENDING`, `AWAITING_VALIDATION`, `PROCESSING`, `SUCCEEDED`, `FAILED`, `CANCELED`.
- Modèles : `SalaryPaymentRun`, `SalaryPaymentLine` (audités via `spatie/laravel-activitylog`).

## 🖥️ Interface

1. **Action groupée « Payer par virement API »** sur les bulletins (statut `VALIDATED`, net > 0) dans `PayslipResource`. Un formulaire demande le compte émetteur (banque connectée).
2. Le run est créé puis **`InitiateSalaryPaymentRunJob`** est dispatché (async) pour l'initiation Bridge.
3. Suivi dans la ressource **« Runs de paiement »** (`SalaryPaymentRunResource`) :
   - « Ouvrir la validation bancaire » : ouvre le `consent_url` (si `AWAITING_VALIDATION`).
   - « Rafraîchir le statut » : polling manuel d'un run.
   - « Relancer l'initiation » : régénère un nouveau lien si le précédent a expiré.

## ⏱️ Polling automatique

La commande `paie:poll-salary-payments` est planifiée **toutes les 5 minutes** (`routes/console.php`) et met à jour tous les runs non terminés disposant d'un `bridge_payment_request_id`.

## 🔄 Mapping des statuts Bridge

| Bridge          | Statut métier        |
|-----------------|----------------------|
| `PDNG`          | `PROCESSING`         |
| `ACTC`          | `PROCESSING`         |
| `ACCP`          | `AWAITING_VALIDATION` |
| `ACSP`          | `PROCESSING`         |
| `ACSC`          | `SUCCEEDED`          |
| `RJCT` / `CANC` | `FAILED` / `CANCELED`|

## 🧪 Tests

`tests/Feature/Modules/Paie/BridgePaymentServiceTest.php` et `SalaryPaymentServiceTest.php` couvrent l'initiation, l'idempotence, le polling et le passage en PAID (via `Http::fake()`).