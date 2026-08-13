---
title: Trésorerie et Clôture
icon: heroicon-o-chart-bar
order: 4
---

# 📈 Trésorerie, Paiements et Clôture

Au-delà du simple pointage, le module Banque exploite vos données financières pour vous offrir une vision claire sur l'avenir et faciliter les paiements.

## 1. Le Prévisionnel de Trésorerie (Forecast)

Le Dashboard principal de l'ERP intègre un widget de "Prévisionnel de Trésorerie". Ce graphique superpose :

- **Votre solde actuel confirmée** (issu de la banque).
- **L'impact de vos dettes et créances** : Le système additionne toutes les factures clients en attente de paiement (entrées à venir) et soustrait les factures fournisseurs à payer (sorties à venir).
- **Le lissage des Devis** : Le système prend les devis signés non encore facturés et lisse leur montant sur les 30 prochains jours pour projeter votre solde futur.

C'est l'outil indispensable pour savoir si vous pourrez payer vos charges à la fin du mois.

## 2. Payer vos Fournisseurs (Export SEPA)

Batistack facilite le règlement de vos factures d'achat.
1. Depuis la liste des **Factures Fournisseurs**, sélectionnez les factures que vous souhaitez payer.
2. Choisissez l'action groupée **"Payer par virement (SEPA)"**.
3. Le système va regrouper les montants par fournisseur et générer un fichier XML au format `.pain` (norme SEPA européenne).
4. Il ne vous reste plus qu'à uploader ce fichier sur le site de votre banque, qui exécutera tous les virements d'un coup !

## 3. L'Espace "Clôture Mensuelle" (Pour l'Expert-Comptable)

Pour faciliter le travail de fin de mois avec votre comptable, une vue dédiée appelée **"Clôture Mensuelle"** agit comme un détecteur d'anomalies :

- **Transactions non catégorisées** : Les dépenses que le système n'a pas su affecter.
- **Transactions orphelines > 1000€** : Les gros mouvements sans facture associée.
- **Factures payées sans transaction** : Les factures qui ont été marquées manuellement comme "Payées" par un utilisateur, mais dont l'ERP n'a trouvé aucune trace sur le compte bancaire.

> [!TIP]
> **Donnez un accès restreint à votre comptable !**
> Créez un compte utilisateur avec le rôle "Expert-Comptable". Il pourra se connecter à Batistack, exporter les écritures, vérifier la clôture mensuelle et télécharger les PDF des factures sans pouvoir modifier les données opérationnelles.
