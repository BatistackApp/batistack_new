---
title: Paiements et Relances
icon: heroicon-o-currency-euro
order: 4
---

# 💳 Paiements et Relances (Dunning)

La trésorerie est le nerf de la guerre. Batistack met tout en œuvre pour vous faire payer plus vite.

## 1. Paiement en Ligne (Stripe)

Chaque PDF de facture (ou d'acompte) généré par l'ERP intègre un **QR Code dynamique** et un lien cliquable. 

- Lorsque votre client scanne ce code avec son smartphone ou clique sur le lien depuis son ordinateur, il est redirigé vers un portail de paiement en ligne sécurisé (propulsé par Stripe).
- Il peut payer par Carte Bancaire ou Prélèvement SEPA.
- **L'automatisation magique** : Dès que la transaction Stripe réussit, l'ERP l'intercepte, crée le reçu de paiement, et passe la Facture en "Payée" instantanément, de jour comme de nuit, sans que vous n'ayez rien à faire.

## 2. Allocation des paiements multiples

Un client vous doit 3 factures distinctes de 1000€. Il vous fait un virement de 3000€.
Grâce au mécanisme d'**Allocation**, vous pouvez créer un seul "Paiement" de 3000€, puis répartir cet argent (Allouer) sur les 3 factures concernées afin de les solder toutes en même temps.

## 3. Le Processus de Relance (Dunning)

Ne perdez plus de temps à vérifier quelles factures sont en retard. 
Le moteur de relances (Dunning Process) s'en occupe pour vous chaque nuit :

1. **J+3 après échéance** : L'ERP envoie automatiquement un e-mail de relance amiable au client.
2. **J+15 après échéance** : Envoi d'une seconde relance plus ferme (Rappel #2).
3. **J+30 après échéance** : Le système passe aux choses sérieuses. Il génère et envoie une **Mise en Demeure** par e-mail (et/ou courrier) et ajoute automatiquement l'indemnité forfaitaire de recouvrement (40€) légale à la facture.

> [!TIP]
> Vous gardez le contrôle : Vous pouvez désactiver ce processus automatique client par client si vous avez des accords commerciaux spécifiques avec l'un d'entre eux.
