---
title: Exports & Comptabilité
icon: heroicon-o-document-arrow-down
order: 3
---

# 📤 Exports & Comptabilité

La validation des fiches de paie n'est que la première étape. Batistack vous aide à finaliser le processus légal et financier grâce à ses générateurs d'exports.

## 1. Les Virements Bancaires (SEPA)

Plutôt que de saisir les virements de vos 50 salariés un par un sur le site de votre banque, utilisez l'outil d'**Export SEPA**.
En un clic, l'ERP génère un fichier normé (`pain.001.001.03`) contenant tous les salaires nets à verser. Il vous suffit d'importer ce fichier unique sur le portail de votre banque.

## 2. Le Journal de Paie (OD Comptable)

La paie génère de nombreuses écritures comptables (Salaires, Charges à payer, URSSAF). 
L'outil d'**Export Comptable** produit un fichier d'Opérations Diverses (OD) en partie double (Comptes 641, 421, 431...). Ce fichier est prêt à être intégré dans votre logiciel de comptabilité (Sage, Cegid, EBP), vous évitant toute saisie manuelle.

## 3. L'Export Social (Format DSN/DADS)

Pour déclarer les salaires aux organismes sociaux (URSSAF, Retraite), Batistack intègre un exportateur de données compatibles **DSN**.
Il regroupe les bases de cotisations, les montants patronaux et salariaux au format `.csv`, vous facilitant la télédéclaration mensuelle.

### Le Suivi des Déclarations DSN

Chaque génération d'export DSN est ** tracée** dans une ressource dédiée **« Soumissions DSN »**. Vous y suivez, période par période :

- le **statut** de la déclaration (Prête, Exportée, Soumise, Acceptée, Rejetée) ;
- la **date** de génération, le **nombre de lignes** et les **totaux** déclarés ;
- le **fichier** correspondant, téléchargeable à tout moment.

> [!TIP]
> **Boucler la campagne de paie**
> Marquez les bulletins d'une période comme **« Prêts pour la DSN »** (action groupée), générez l'export, puis mettez à jour le statut de la soumission au fil des accusés de réception. Vous gardez ainsi une trace d'audit complète de vos déclarations.

> [!NOTE]
> **Télédéclaration automatique (M2M)**
> L'envoi **automatique** de la DSN à l'URSSAF (API Machine-to-Machine, sans manipuler le fichier CSV) est prévu à terme. En l'état, Batistack génère et **suit** le fichier DSN ; son dépôt sur Net-Entreprises reste une étape manuelle.

> [!TIP]
> **Sécurité et Clôture**
> Une fois vos exports générés et envoyés, utilisez la fonction de **Clôture**. Elle verrouille définitivement les bulletins et les pointages du mois, empêchant toute modification rétroactive accidentelle.
