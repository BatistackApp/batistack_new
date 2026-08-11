---
title: Le Cycle des Achats
icon: heroicon-o-truck
order: 3
---

# 📦 Le Cycle des Achats (Fournisseurs)

Tout comme pour les ventes, les achats suivent un processus rigoureux permettant de tracer qui a commandé quoi, pour quel chantier, et à quel prix.

## 1. La Demande d'Achat (Purchase Request)

Souvent initiée par un chef de chantier qui constate un manque de matériel, la "Demande d'Achat" est un brouillon qui permet de solliciter le service des achats ou la direction.

## 2. La Commande d'Achat (Purchase Order)

Une fois validée, la demande se transforme en Commande Fournisseur. 
Le document PDF généré à partir de la commande peut être envoyé directement au négoce en matériaux. C'est ce document qui engage financièrement l'entreprise.

## 3. Le Bon de Réception (Receipt Note)

Lorsque le camion du fournisseur arrive sur le chantier ou au dépôt, il faut attester de la réception.
- Convertissez la Commande en "Bon de Réception".
- Ajustez les quantités si le livreur n'a pas tout apporté (livraison partielle).
- L'ERP mettra automatiquement à jour l'état de votre stock !

## 4. La Facture Fournisseur et l'Audit

C'est ici que l'ERP brille. Lorsque la facture du fournisseur arrive par courrier ou email :
1. Créez la Facture Fournisseur et liez-la au(x) Bon(s) de Réception.
2. Batistack procède à un **Audit automatique**. Il compare le prix unitaire facturé par rapport au prix unitaire initialement convenu sur la commande d'achat, et s'assure que les quantités facturées n'excèdent pas les quantités reçues.
3. Si un écart est détecté, l'ERP lève un avertissement (Flag rouge). Vous êtes sûr de ne jamais payer pour du matériel non reçu ou facturé plus cher que prévu.

## 5. La Gestion des Sous-Traitants

Un sous-traitant (artisan externe) travaille différemment d'un négoce de matériaux.
L'ERP gère un flux spécifique pour eux : la **Situation de Sous-Traitant**. 
Le fonctionnement est miroir à la situation client : l'artisan vous déclare un pourcentage d'avancement de ses travaux, et vous générez une situation pour tracer l'évolution du paiement et de la retenue de garantie légale (5%).
