---
title: Suivi Physique & Inventaire
icon: heroicon-o-qr-code
order: 3
---

# 📍 Suivi Physique & Inventaire

Posséder du matériel c'est bien, savoir où il se trouve et dans quel état, c'est mieux. Le module Immobilisations connecte la valeur financière au terrain.

## 1. Transfert Inter-Chantiers

Vos machines lourdes passent d'un chantier à l'autre.
- Les conducteurs de travaux peuvent effectuer des **demandes de transfert** de matériel.
- Lors de l'approbation du mouvement, un **Bon de Transport (PDF)** est généré.
- **Imputation Analytique** : Dès qu'une machine arrive sur un nouveau chantier, la charge d'amortissement (quotidienne) lui est facturée. Vous savez exactement combien vous a coûté l'utilisation de votre grue sur le chantier X.

## 2. Étiquetage QR Code

Chaque immobilisation est dotée d'une **Fiche PDF générée automatiquement** par l'ERP, comprenant ses caractéristiques techniques et, surtout, un **QR Code unique**.
Imprimez ces plaquettes et collez-les sur vos équipements.

## 3. Audit d'Inventaire PWA (Smartphone)

Pour réaliser l'inventaire annuel ou vérifier qu'un équipement est bien à sa place :
1. Lancez le portail **Audit d'Inventaire** (optimisé pour smartphone / Progressive Web App).
2. Utilisez l'appareil photo pour scanner le QR code de la machine.
3. Validez l'état (Présent, Endommagé, Manquant).

Le gestionnaire de parc dispose d'un tableau de bord de conformité pour suivre l'avancement de l'inventaire global.

## 4. Alertes VGP (Vérification Générale Périodique)

Pour les engins de levage et autres matériels réglementés, l'outil suit les dates de **VGP**. 
Le tableau de bord central affiche les alertes critiques des contrôles à venir ou en retard, permettant de ne jamais passer à côté d'une obligation légale de sécurité.

## 5. Portail de Déclaration de Casse / Sinistre (PWA Salarié)

Un outil cassé ou perdu sur chantier ? Les ouvriers peuvent le déclarer en quelques secondes depuis leur smartphone, directement depuis leur espace Salarié.

> [!TIP]
> Cette fonctionnalité couvre à la fois les **immobilisations** (Fixed Assets) et le **matériel RH / EPI** (Équipements), identifiés par leurs étiquettes QR.

### 5.1 Déclaration côté Salarié

1. Dans l'espace **Espace Employé** (PWA), ouvrez **« Déclarer une casse »**.
2. **Scannez le QR code** apposé sur l'outil (ou saisissez son numéro de série) — l'outil est détecté automatiquement.
3. Renseignez la **gravité** (Faible / Moyenne / Élevée / Critique), le **chantier** concerné (optionnel) et une **description** du sinistre.
4. Joignez des **photos** du dégât.
5. Validez : le ticket est créé, l'actif passe en statut **« En maintenance / En réparation »** et le **dépôt est notifié instantanément**.

### 5.2 Traitement côté Dépôt (panel Immobilisations)

Les tickets sont centralisés dans le menu **« Déclarations de casse »** (groupe *Gestion des Actifs*) :

- Chaque ticket est identifié par une **référence unique** (`TK-AAAA-NNNN`).
- Le dépôt peut **prendre en charge** un ticket, puis le **résoudre**.
- À la **résolution**, un enregistrement de **maintenance curative** est automatiquement créé sur l'actif (avec coût HT et prestataire si renseignés), et l'actif retrouve son statut d'origine.
- Un ticket peut aussi être **annulé** (retour au statut actif de l'outil).

### 5.3 Étiquettes QR

Depuis la liste des immobilisations ou le détail d'un salarié (onglet Matériel & EPI), l'action **« Imprimer QR »** génère une étiquette PDF unique à apposer sur l'outil. Le QR code scanné sert aussi bien à l'audit d'inventaire qu'à la déclaration de casse.
