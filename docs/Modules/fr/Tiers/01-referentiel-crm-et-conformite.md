---
title: CRM & Conformité Légale
icon: heroicon-o-identification
order: 2
---

# 🗂️ Référentiel CRM & Conformité Légale

La création et le suivi de vos partenaires commerciaux sont entièrement assistés par l'ERP pour limiter les risques financiers.

## 1. Création Intelligente (APIs de l'État)

Lorsque vous créez un nouveau Tiers (Client ou Fournisseur), il vous suffit de saisir son **numéro SIREN** ou de rechercher son nom.
L'ERP interroge immédiatement les bases de données gouvernementales (INSEE, Pappers) pour auto-compléter l'adresse du siège social, le numéro de TVA intracommunautaire, le code NAF et les noms des dirigeants.

## 2. Vérification de Solvabilité (Vigilance)

Ne prenez pas de risques avec des partenaires insolvables. Batistack intègre un service de **Vigilance** en temps réel.
- Le système interroge l'API publique ouverte `recherche-entreprises.api.gouv.fr` et détermine un **statut juridique granulaire** : *Sain*, *Sauvegarde*, *Redressement judiciaire*, *Liquidation judiciaire* ou *Cessation* (colonne `legal_status`).
- Ce statut est affiché sous forme de badge (vert/orange/rouge) sur la fiche du tiers (onglet *Informations Financières*) et dans le tableau des Tiers (colonne *Santé Financière*).
- Un bandeau/message d'avertissement renseigne la nature de la restriction sur chaque fiche.

## 3. Garde-Fou de Contractualisation

Batistack **bloque automatiquement la contractualisation** avec les entreprises à risque, sans jamais supprimer les données existantes :
- **Blocage dur (Redressement / Liquidation judiciaire)** : la génération du contrat de sous-traitance, la création de bons de commande et l'affectation d'un sous-traitant à un chantier sont **refusées** (notification rouge).
- **Avertissement (Sauvegarde, Cessation ou statut jamais vérifié)** : la contractualisation reste possible mais un **avertissement** (notification orange) est affiché pour inviter à vérifier avant d'engager.
- L'actualisation du statut se fait via l'action **« Actualiser Solvabilité »** sur la fiche ou la liste des Tiers.

> [!NOTE]
> Un tiers jamais synchronisé (`legal_status` vide) n'est **pas** bloqué : seul un simple avertissement « statut non vérifié » est émis.

## 4. Gestion des Documents Obligatoires

Travailler avec un sous-traitant nécessite de collecter des documents légaux (Kbis de moins de 3 mois, Attestation URSSAF de vigilance, Assurance Décennale).
- Le module Tiers intègre un gestionnaire documentaire.
- Vous renseignez la date de validité de chaque document. 
- **Le système vous alerte automatiquement** (Dashboard et email) à J-30 et J-7 avant l'expiration d'un document critique, afin que vous puissiez relancer votre partenaire.
