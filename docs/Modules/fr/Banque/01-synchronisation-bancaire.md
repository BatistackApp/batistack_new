---
title: Synchronisation Bancaire
icon: heroicon-o-arrow-path-rounded-square
order: 2
---

# 🔄 La Synchronisation Bancaire

Pour que Batistack puisse rapprocher vos factures, il a d'abord besoin de connaître les mouvements sur vos comptes bancaires.

Deux méthodes s'offrent à vous : la connexion automatique (Open Banking) ou l'import manuel.

## 1. Connexion Automatique (Open Banking)

Batistack utilise un agrégateur bancaire sécurisé (Powens/Bridge) pour se connecter directement à votre banque (Crédit Agricole, BNP, Société Générale, Qonto, Shine, etc.) en respectant la directive européenne DSP2.

### Comment connecter un compte ?
1. Allez dans **Banque > Comptes Bancaires**.
2. Cliquez sur **Connecter une banque**.
3. Vous serez redirigé vers l'interface sécurisée de votre banque pour saisir vos identifiants.
4. Une fois validé, Batistack récupérera chaque nuit les nouvelles transactions.

> [!WARNING]
> **Renouvellement de l'autorisation (DSP2)**
> Pour des raisons de sécurité imposées par la loi, l'autorisation donnée à Batistack expire tous les 90 jours (ou 180 jours selon la banque). L'ERP vous affichera une notification 5 jours avant l'expiration pour vous inviter à renouveler l'autorisation en un clic.

## 2. Import Manuel (Relevés CSV / QIF)

Si vous ne souhaitez pas connecter votre banque directement, ou si vous devez rattraper un historique très ancien, vous pouvez utiliser l'import manuel.

1. Téléchargez le relevé de compte au format `.csv` ou `.qif` depuis le site web de votre banque.
2. Dans Batistack, allez dans **Banque > Transactions**.
3. Utilisez l'action **Importer un relevé**.
4. Le système identifiera les doublons et n'importera que les nouvelles lignes.
