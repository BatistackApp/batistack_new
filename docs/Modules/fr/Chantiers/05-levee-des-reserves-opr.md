---
title: Levée des Réserves & OPR
icon: heroicon-o-clipboard-document-check
order: 6
---

# 📋 Levée des Réserves & OPR (Snagging)

Lors de la réception des travaux, il est fréquent que le client constate des **défauts** ou des **fini-sions** à corriger. Batistack permet de **signaler ces réserves depuis le terrain**, de **les assigner** et de **suivre leur levée** jusqu'à l'acceptation par le client.

## 1. Le cycle de vie d'une réserve

Chaque réserve suit un cycle de vie simplifié :

1. **Ouverte** — un défaut vient d'être signalé.
2. **En cours** — la réserve a été assignée à un employé (avec une échéance éventuelle).
3. **Résolue** — le travail de correction est terminé (horodaté).
4. **Levée** — le **client** accepte la correction (nom + signature), la réserve est clôturée.

Une réserve porte une **gravité** (Informatif, Mineur, Majeur, Critique), une **description**, des **photos**, un **plan** éventuel, un **responsable assigné** et une **date d'échéance**.

## 2. Signaler une réserve depuis le terrain

Depuis l'**Espace Terrain** (mobile / PWA), le conducteur de travaux ouvre l'écran **Réserve / OPR** :

- Il sélectionne le **chantier** (limité à ceux qu'il gère),
- renseigne l'**objet**, la **description** et la **gravité**,
- joint des **photos** du défaut,
- et valide. Une **entrée est ajoutée au Journal de Chantier** et une **référence** (`RS-2026-XXXX`) est générée automatiquement.

## 3. Suivi et traitement (fiche chantier)

Sur la **fiche chantier**, un onglet **Réserves / OPR** liste toutes les réserves (filtres par statut et gravité). Depuis ce tableau :

- **Assigner** — choisir l'employé responsable + une échéance (la réserve passe « En cours »).
- **Marquer résolue** — horodate la correction.
- **Levée par le client** — saisir le nom du client et faire **signer** électroniquement (le bloc de signature réutilise celui des Checklists QSE).
- Le widget **« Réserves »** en haut de la fiche affiche les compteurs (ouvertes, en cours, levées, critiques).

## 4. Intégration au PV de Réception

Lors de la génération du **Procès-Verbal de Réception**, Batistack insère automatiquement une **annexe « Liste des réserves »** : toutes les réserves **résolues ou levées** y figurent (référence, objet, gravité, statut, assigné, date de levée), pour servir d'appui contractuel à la levée des réserves.

> [!NOTE]
> Le PV de Réception est le **document contractuel de la levée des réserves**. Une fois signé par le client, il marque le point de départ des garanties légales (parfait achèvement, bon fonctionnement, décennale).