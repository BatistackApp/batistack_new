---
title: Changelog (Mis à jour à jour)
icon: heroicon-o-clipboard-document-check
order: 1000
---

# 📜 Changelog & Notes de Version

Bienvenue dans le journal des modifications (Changelog) de Batistack.
Vous retrouverez ici les nouveautés, améliorations et corrections apportées à votre ERP et documentées au fil des versions (voir la note ⚠️ Historique partielle ⚠️ ci-dessous).

---

> [!NOTE]
> **Historique partiel** : ce journal détaille les versions jusqu'à la **v0.34.0**. Les versions suivantes (v0.35.0 → v0.42.0) sont suivies via les notes de release GitHub.

## 🚀 Version 0.36.0 (Août 2026)

### 🎉 Mise en avant : Panel Technicien (Dashboard, Planning & Équipements)
Cette version déploie le **Panel Technicien**, avec un tableau de bord dédié et des outils de suivi des interventions et des équipements (PR #376).
*   **Tableau de bord Technicien personnalisé** : remplace le tableau de bord générique par une vue dédiée avec widgets de KPI (interventions du jour, en cours, signatures en attente, heures travaillées ce mois) et liste des interventions récentes.
*   **Planning des interventions** : nouvelle page pour visualiser toutes les interventions attribuées, avec filtres avancés (date prévue, date de complétion, statut, type d'intervention).
*   **Équipements clients (lecture seule)** : nouvelle ressource « Équipements » pour consulter les équipements associés aux interventions du technicien (liste et détail) ; création, modification et suppression désactivées.

### 🔧 Corrections & Améliorations
*   **KPIs Technicien** : le calcul des « heures travaillées ce mois » est désormais basé sur la date de complétion de l'intervention ; le « Taux de signature » affiche « — » avec une description adaptée (et une couleur de statut ajustée) si aucune intervention n'est complétée ce mois, évitant un taux de 100 % trompeur.
*   **Widget d'activité récente** : exclusion des interventions en statut « Brouillon » ou « Annulée » pour une meilleure visibilité des activités pertinentes.
*   **Sécurité des équipements** : `ClientEquipmentResource` restreint l'affichage aux seuls équipements liés aux interventions du technicien connecté.
*   **Divers** : correction d'une coquille dans le libellé « Prévue » du widget du jour ; optimisation des imports dans `TechDashboard.php`.

---

## 🚀 Version 0.35.0 (Août 2026)

### 🎉 Mise en avant : Panel Terrain (Journal, BIM, RH)
Cette version déploie le **Panel Terrain**, l'interface opérationnelle dédiée aux chefs de chantier et aux équipes terrain (PR #374).
*   **Journal de chantier hors-ligne** : saisie des entrées (travaux, observations, météo, incidents) sans connexion, stockage local et synchronisation automatique ou manuelle via des API dédiées.
*   **Tableau de bord Terrain** : statistiques essentielles (chantiers actifs, réserves ouvertes, heures travaillées cette semaine, équipes non conformes), widgets de conformité des équipes, de progression des chantiers et d'activité quotidienne (heures pointées, réserves, entrées de journal, incidents du jour).
*   **Saisie des heures** : workflow amélioré avec gestion des brouillons, soumission, pré-remplissage basé sur la veille et historique des 5 derniers jours de pointage.
*   **Validation des pointages** : nouvelle interface d'approbation / refus (avec motif) des heures collaborateurs, validation en masse, filtres par chantier et période.
*   **Gestion des documents de chantier** : page « Documents » pour visualiser, générer et télécharger les documents clés (OS, Rentabilité, Journal, PPSPS, PV).
*   **Visionneuse BIM** : page « Maquettes 3D » pour consulter les maquettes BIM et plans des chantiers actifs, avec visionneuse dédiée et téléchargement.
*   **Ressource « Mes Chantiers »** : liste des chantiers où l'employé est gestionnaire ou membre, consultation détaillée en lecture seule.
*   **Sécurité** : nouveau scope `Chantier::forEmployee` pour restreindre la visibilité aux chantiers assignés.
*   **Qualité** : couverture complète par des tests d'intégration (API de sync, validation des pointages, accès BIM, dashboards).

### 🛠️ CI / Infrastructure
*   **Permissions de déploiement** : ajout de `chmod -R 777 storage/` au script de build pour garantir la persistance des logs, cache, sessions et fichiers téléchargés.
*   **Vite/BIM** : optimisation du bundle de la visionneuse et nettoyage de la configuration Vite.

---

## 🚀 Version 0.34.0 (Août 2026)

### 🎉 Mise en avant : Portail Salarié (Widgets, Documents & Suivi)
Cette version introduit le **Portail Salarié**, une interface centralisée permettant aux employés de consulter leurs données RH, suivre leur activité et gérer leurs documents en autonomie (PR #372).
*   **Tableau de bord (widgets)** :
    *   `LeaveBalanceWidget` : solde des congés, RTT et suivis des arrêts maladie.
    *   `TimeTrackingWidget` : récapitulatif des heures travaillées (total, validées, en attente) avec filtre par semaine/mois/année.
    *   `PlanningCalendarWidget` : calendrier interactif des absences et pointages avec codes couleur.
    *   `RecentActivityWidget` : flux chronologique des événements RH (pointages, absences, bulletins, qualifications).
    *   `PayslipDownloadWidget` & `DocumentWidget` : accès aux bulletins de paie et documents RH (attestations, contrats) avec statut "Disponible".
*   **Gestion des contrats** : nouvelle ressource `ContractResource` (consultation détaillée : dates clés, rémunération, statut de signature, téléchargement PDF). Accès strictement restreint à l'utilisateur authentifié, en lecture seule.
*   **Qualité** : couverture complète par des tests unitaires et d'intégration (calculs de soldes, isolation des données par utilisateur, permissions).

### 🔐 Sécurité
*   **Correction d'une faille IDOR critique sur le Panel Client** (PR #371) : `canView()` retournait `true` inconditionnellement sur toutes les ressources du Panel Client (factures, devis, commandes, avoirs, bons de livraison, situations, interventions, équipements). L'autorisation est désormais centralisée dans le trait `ScopesToAuthenticatedThirdParty` : accès unique si le `third_party_id` de l'enregistrement correspond au contact lié à l'utilisateur authentifié ; `canEdit()`/`canDelete()` sont désactivés (lecture seule).

### 🛠️ CI / Infrastructure
*   **Browsershot en CI** (PR #373) : ajout conditionnel de l'argument `--no-sandbox` (activé uniquement si la variable d'environnement `CI` est présente) pour résoudre l'échec de génération de miniatures Chromium dans les pipelines conteneurisés. Aucun impact en local ni en production.

---

## 🚀 Version 0.33.0 (Août 2026)

### 🎉 Mise en avant Feature : Scope de Solvabilité / Risque Financier (Issue #294)
Batistack introduit désormais l'API publique `recherche-entreprises.api.gouv.fr` pour afficher le **statut juridique** de vos tiers et **évaluer la contractualisation** avec les entreprises à risque.
*   **Statut juridique granulaire** : Sauvegarde, Redressement judiciaire, Liquidation judiciaire, Cessation ou Sain (badge coloré sur la fiche et dans la liste des Tiers).
*   **Garde-fou de contractualisation** : blocage dur (notification rouge) pour les entreprises en redressement ou liquidation judiciaire, lors de la génération d'un contrat de sous-traitance, de la création d'un bon de commande ou de l'affectation d'un sous-traitant à un chantier. Avertissement (orange) pour les situations à surveiller (sauvegarde, cessation, statut non vérifié).

---

## 🚀 Version 0.32.0 (Août 2026)

### 🎉 Mise en avant Feature : Portail Client SAV & Maintenance
Cette version introduit un **Espace Client dédié** permettant une interaction directe et transparente avec vos bénéficiaires.
*   **Parc Matériel** : Les clients peuvent consulter la liste de leurs équipements (marque, numéro de série, date d'installation).
*   **Signature de panne** : Un bouton "Signaler une panne" permet au client de créer instantanément une demande d'intervention avec description, sans passer par un appel téléphonique.
*   **Suivi en temps réel** : Accès sécurisé pour suivre l'avancement des interventions et l'historique des maintenances.

### 🧩 Modules

**Locations**
*   **Ajout** : Comparateur de prix fournisseurs permettant de choisir le loueur le plus économique selon la durée (jour/semaine/mois).
*   **Ajout** : Gestion des "Locations Sortantes" pour facturer la location de votre propre matériel à des tiers.
*   **Ajout** : Système d'alertes automatiques (J-1) avant la fin d'un contrat et application des pénalités de retard journalières paramétrables.

**Interventions**
*   **Ajout** : Formulaires d'Interventions Dynamiques (Checklists sur-mesure) — création de modèles de rapports par type d'intervention (Réglage/Forge) avec blocs de champs (texte, nombre, case à cocher, liste, date, photo). Le technicien renseigne le rapport depuis son espace, et la culture est **bloquée** tant que les champs obligatoires ne sont pas complétés.

**Immobilisations & Actifs**
*   **Ajout** : Module complet de gestion des machines (suivi opérationnel, compteurs d'heures et intervalles de maintenance).
*   **Ajout** : Gestion des rebuts de fabrication permettant de déclarer des composants perdus avec motifs (erreur humaine, défaut matière, etc.).

**GPAO (Gestion de production)**
*   **Ajout** : Module complet de gestion des machines (suivi opérationnel, compteurs d'heures et intervalles de maintenance).
*   **Ajout** : Gestion des rebuts de fabrication permettant de déclarer des composants perdus avec motifs (erreur humaine, défaut matière, etc.).

**Administration & RH**
*   **Ajout** : Interface de gestion des Rôles et Permissions pour affiner les accès utilisateurs.
*   **Ajout** : Simulateur de paie pour estimer les coûts employeurs et le net salarial.
*   **Correction** : Amélioration de l'OCR pour la lecture automatique des dates et mois sur les notes de frais.

### 🐛 Fix Général
*   **Traduction** : Harmonisation complète des interfaces avec l'application systématique des libellés en français sur l'ensemble des champs des formulaires (Référence, Statut, Montant, Créé le, etc.).
*   **Facturation** : Ajout d'une sécurité anti-doublon via une clé de facturation unique (`billing_key`) pour les contrats récurrents.
*   **Performance** : Mise à jour des moteurs de rendu PDF et des composants de tableau de bord pour une meilleure fluidité.
