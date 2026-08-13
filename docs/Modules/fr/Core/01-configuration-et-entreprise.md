---
title: Configuration & Entreprise
icon: heroicon-o-cog-8-tooth
order: 2
---

# 🏢 Configuration et Entreprise

Le module Core porte l'identité de votre société et les référentiels de base utilisés partout ailleurs.

## 1. Gestion de l'Entreprise (Multi-Tenant)

La section **Entreprise** regroupe vos informations légales (SIREN, SIRET, Numéro de TVA Intracommunautaire, Capital, Adresse). 
L'ERP interroge automatiquement l'API gouvernementale Sirene pour vérifier et compléter les informations saisies.

> [!NOTE]  
> **Multi-Agences** : Batistack est conçu autour d'une architecture dite "Multi-Tenant". Si votre groupe possède plusieurs entités juridiques, vous pouvez créer plusieurs "Entreprises" dans le Core, et restreindre l'accès de certains utilisateurs à une entreprise spécifique.

## 2. Le Référentiel des Taux de TVA

La fiscalité évolue. Batistack ne hardcode aucun taux de TVA. C'est à vous de configurer les taux en vigueur dans la section **Taux de TVA** (ex: 20% Standard, 10% Intermédiaire, 5.5% Réduit).
Si un nouveau taux apparaît, il suffit de l'ajouter ici pour qu'il devienne instantanément sélectionnable dans les devis et factures.

## 3. Le Référentiel des Unités

Les articles et les tâches sont mesurés selon certaines unités (m², mètre linéaire, kg, tonne, heure). 
Dans la section **Unités**, vous définissez ces symboles. Le système permet même de déclarer des conversions (ex: "Combien de kg dans 1 tonne ?") qui seront utilisées par le moteur de gestion des stocks.

## 4. Paramètres Globaux (Settings)

C'est ici que vous définissez le comportement par défaut de l'ERP :
- Taux de TVA appliqué par défaut aux nouveaux articles.
- Durée de validité par défaut d'un devis (ex: 30 jours).
- Logo de l'entreprise utilisé sur les PDF.
