---
title: Changelog (Mises à jour)
icon: heroicon-o-clipboard-document-check
order: 1000
---

# 📋 Changelog & Notes de Version

Bienvenue dans le journal des modifications (Changelog) de Batistack.
Vous retrouverez ici les nouveautés, améliorations et corrections apportées à votre ERP et documentées au fil des versions (voir la note © Historique partie © ci-dessous).

---

> [!NOTE]
> **Historique partie** : ce journal détaille les versions jusqu'à la **v0.33.0**. Les versions suivantes (v0.34.0 ⬦ v0.38.0) sont suivies via les notes de release GitHub.

## 📦 Version 0.34.0 (Août 2026)

### 🚀 Mise en avant : Portail Salarié (Widgets, Documents & Suivi)
Cette version introduit le **Portail Salarié**, une interface centralisée permettant aux employés de consulter leurs données RH, suivre leur activité et gérer leurs documents en autonomie (PR #372).
*   **Tableau de bord (widgets)** :
    *   `LeaveBalanceWidget` : solde de congés, RTT et suivi des arrêts maladie.
    *   `TimeTrackingWidget` : récapitulatif des heures travaillées (total, validées, en attente) avec filtrage par semaine/mois/année.
    *   `PlanningCalendarWidget` : calendrier interactif des absences et pointages avec codes couleurs.
    *   `RecentActivityWidget` : flux chronologique des événements RH (pointages, absences, bulletins, qualifications).
    *   `PayslipDownloadWidget` & `DocumentWidget` : accès aux bulletins de paie et documents RH (attestations, contrats) avec statut "Digiposte".
*   **Gestion des contrats** : nouvelle ressource `ContractResource` (consultation détaillée : dates clés, rémunération, statut de signature, téléchargement PDF). Accès strictement restreint à l'utilisateur authentifié, en lecture seule.
*   **Qualité** : couverture complète par tests unitaires et d'intégration (calculs de soldes, isolation des données par utilisateur, permissions).

### 🔒 Sécurité
*   **Correction d'une faille IDOR critique sur le Panel Client** (PR #371) : `canView()` retournait `true` inconditionnellement sur toutes les ressources du Panel Client (factures, devis, commandes, avoirs, bons de livraison, situations, interventions, équipements). L'autorisation est désormais centralisée dans le trait `ScopesToAuthenticatedThirdParty` : accès uniquement si le `third_party_id` de l'enregistrement correspond au contact lié à l'utilisateur authentifié ; `canEdit()`/`canDelete()` désactivés (lecture seule).

### 🛠️ CI / Infrastructure
*   **Browsershot en CI** (PR #373) : ajout conditionnel de l'argument `--no-sandbox` (activé uniquement si la variable d'environnement `CI` est présente) pour résoudre l'échec de génération de miniatures Chromium dans les pipelines conteneurisés. Aucun impact en local ni en production.

---

## 📦 Version 0.33.0 (Août 2026)

### 🚀 Mise en avant Feature : Score de Solvabilité / Risque Financier (Issue #294)
Batistack interroge désormais l'API publique ouverte `recherche-entreprises.api.gouv.fr` pour afficher le **statut juridique** de vos tiers et **bloquer la contractualisation** avec les entreprises à risque.
*   **Statut juridique granulaire** : Sauvegarde, Redressement judiciaire, Liquidation judiciaire, Cessation ou Sain (badge coloré sur la fiche et dans la liste des Tiers).
*   **Garde-fou de contractualisation** : blocage dur (notification rouge) pour les entreprises en redressement ou liquidation judiciaire, perte de la génération d'un contrat de sous-traitance, de la création d'un bon de commande ou de l'affectation d'un sous-traitant à un chantier. Avertissement (orange) pour les situations à surveiller (sauvegarde, cessation, statut non vérifié).

---

## 📦 Version 0.32.0 (Août 2026)

### 🚀 Mise en avant Feature : Portail Client SAV & Maintenance
Cette version introduit un **Espace Client dédié** permettant une interaction directe et transparente avec vos bénéficiaires.
*   **Parc Matériel** : Les clients peuvent consulter la liste de leurs équipements (marque, numéro de série, date d'installation).
*   **Signalement de panne** : Un bouton "Signaler une panne" permet au client de créer instantanément une demande d'intervention avec description, sans passer par un appel téléphonique.
*   **Suivi en temps réel** : Accès sécurisé pour suivre l'avancement des interventions et l'historique des maintenances.

### 🧩 Modules

**Locations**
*   **Ajout** : Comparateur de prix fournisseurs permettant de choisir le loueur le plus économique selon la durée (jour/semaine/mois).
*   **Ajout** : Gestion des "Locations Sortantes" pour facturer la location de votre propre matériel à des tiers.
*   **Ajout** : Système d'alertes automatiques (J-1) avant la fin d'un contrat et application de pénalités de retard journalières paramétrables.

**Interventions**
*   **Ajout** : Formulaires d'Intervention Dynamiques (Checklists sur-mesure) ⬦ création de modèles de rapport par type d'intervention (Réparation/Forfait) avec blocs de champs (texte, nombre, case à cocher, liste, date, photo). Le technicien renseigne le rapport depuis son espace, et la couture est **bloquée** tant que les champs obligatoires ne sont pas complétés.

**Immobilisations & Actifs**
*   **Ajout** : Module de transfert inter-chantiers pour suivre les mouvements du gros matériel avec génération automatique de **Bons de Transport (PDF)**.
*   **Ajout** : Interface d'audit d'inventaire optimisée pour le scan mobile (QR Code) afin de valider la présence physique des actifs sur le terrain.
*   **Ajout** : Nouveau statut "En location (Externe)" pour les actifs loués à des tiers.

**GPAO (Gestion de production)**
*   **Ajout** : Module complet de gestion des machines (suivi opérationnel, compteurs d'heures et intervalles de maintenance).
*   **Ajout** : Gestion des rebuts de fabrication permettant de déclarer les composants perdus avec motifs (erreur humaine, défaut matière).

**Administration & RH**
*   **Ajout** : Interface de gestion des Rôles et Permissions pour affiner les accès utilisateurs.
*   **Ajout** : Simulateur de paie pour estimer les coûts employeurs et le net salarié.
*   **Correction** : Amélioration de l'OCR pour la lecture automatique des dates et montants sur les notes de frais.

### 🐛 Fix Général
*   **Traduction** : Harmonisation complète des interfaces avec l'application système des libellés en français sur l'ensemble des champs (Rénovation, Statut, Montant, Créé le, etc.).
*   **Facturation** : Ajout d'une sécurité anti-doublon via une clé de facturation unique (`billing_key`) pour les contrats récurrents.
*   **Performance** : Mise à jour des moteurs de rendu PDF et des composants de tableau de bord pour un meilleur rendu et plus de fluidité.
