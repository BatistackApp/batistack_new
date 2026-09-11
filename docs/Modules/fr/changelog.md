---
title: Changelog (Mises à jour)
icon: heroicon-o-clipboard-document-check
order: 1000
---

# 🆕 Changelog & Notes de Version

Bienvenue dans le journal des modifications (Changelog) de Batistack.
Vous retrouverez ici les nouveautés, améliorations et corrections apportées à votre ERP et documentées au fil des versions. Les versions récentes sont regroupées par module (voir la note « Historique » ci-dessous).

---

> [!NOTE]
> **Historique** : les versions **v0.34.0 → v0.52.0** sont regroupées ci-dessous par module (synthèse des nouveautés cumulées). Le détail version par version reste disponible dans les notes de release GitHub. Les versions antérieures (jusqu'à la **v0.33.0**) sont détaillées plus bas.

## 📌 Versions 0.34.0 → 0.52.0 (Août – Septembre 2026)

Synthèse des grandes nouveautés livrées depuis la v0.33, regroupées par module.

### 🌟 Nouveaux modules

*   **⚡ Découpe Laser** : module complet dédié à l'activité de découpe — **référentiel matériaux**, **devis avec calcul automatique du prix et import de fichiers DXF**, **commandes**, **bons de livraison**, et **factures/avoirs**. Le cycle Devis → Commande → Livraison → Facture est entièrement automatisé, avec facturation partielle et gestion des avoirs plafonnés. *(voir le module [Découpe Laser](./Laser/index.md))*
*   **📊 Comptabilité** : plan comptable général (PCG), **génération automatique des écritures** issues du lettrage bancaire, et **exports FEC, Sage 50 et Cegid Flow** pour l'expert-comptable. *(voir le module [Comptabilité](./Accounting/index.md))*

### 🌐 Portails self-service

*   **Espace Client** : suivi des devis (signature en ligne), commandes, livraisons, situations, factures et avoirs, solde financier et documents.
*   **Espace Salarié** : contrat, congés et soldes, pointages, notes de frais, équipements, déclaration de casse et documents personnels.
*   **Espace Sous-Traitant** : appels d'offres, signature des marchés, dépôt de situations, suivi des paiements et documents de vigilance.
*   **Espace Technicien** : tableau de bord, planning des interventions et travail **hors-ligne** avec synchronisation.
*   **Espace Conducteur de Travaux** : portail mobile de chantier (journal, pointage d'équipe, scan matériel, état des lieux, réserves/OPR, maquettes).

### 🧩 Par module

*   **Core** : **Gestion Documentaire (GED)** indexant tous les PDF générés ; **signature multi-destinataires** ; **Panel Signatures** dédié ; nouveau **Dashboard Core** ; fiabilisation de la génération PDF (Browsershot).
*   **Commerce** : **exports PDF harmonisés** et standardisés ; **envoi des factures en masse** et **relevés de compte client**.
*   **Articles & Stocks** : **emplacements de stockage** (bin-picking) ; **prévision des ruptures** (croisement historique + besoins planifiés) ; impression d'**étiquettes PDF** (QR / code-barres).
*   **Immobilisations** : **cycle complet des cessions d'actifs** (calcul de plus/moins-value, PV et écritures comptables automatiques) ; traitement des **subventions d'investissement**.
*   **RH** : **workflow de rupture de CDI** (préavis, documents de fin de contrat) ; **suspension** d'un contrat sans le clôturer.
*   **GPAO** : module de **tickets de maintenance machine**.
*   **3DVision** : sur plans **DXF** — **calques**, **mesure de distances**, **sélection/masquage** d'éléments, **rendu des textes** ; miniatures automatiques des maquettes.
*   **Tiers** : **automatisation du suivi juridique** (rafraîchissement périodique du statut) ; **collecte automatique des documents légaux** (API Entreprise / e-Attestations).
*   **Interventions** : **suivi GPS** des véhicules d'intervention.

## 📌 Version 0.33.0 (Août 2026)

### 🌟 Meilleure Feature : Score de Solvabilité / Risque Financier (Issue #294)
Batistack interroge désormais l'API publique ouverte `recherche-entreprises.api.gouv.fr` pour afficher le **statut juridique** de vos tiers et **bloquer la contractualisation** avec les entreprises à risque.
*   **Statut juridique granulaire** : Sauvegarde, Redressement judiciaire, Liquidation judiciaire, Cessation ou Sain (badge coloré sur la fiche et dans la liste des Tiers).
*   **Garde-fou de contractualisation** : blocage dur (notification rouge) pour les entreprises en redressement ou liquidation lors de la génération d'un contrat de sous-traitance, de la création d'un bon de commande ou de l'affectation d'un sous-traitant à un chantier. Avertissement (orange) pour les situations à surveiller (sauvegarde, cessation, statut non vérifié).

---

## 📌 Version 0.32.0 (Août 2026)

### 🌟 Meilleure Feature : Portail Client SAV & Maintenance
Cette version introduit un **Espace Client dédié** permettant une interaction directe et transparente avec vos bénéficiaires.
*   **Parc Matériel :** Les clients peuvent désormais consulter la liste de leurs équipements (marque, numéro de série, date d'installation).
*   **Signalement de panne :** Un bouton "Signaler une panne" permet au client de créer instantanément une demande d'intervention avec description, sans passer par un appel téléphonique.
*   **Suivi en temps réel :** Accès sécurisé pour suivre l'avancement des interventions et l'historique des maintenances.

---

### 📦 Modules

**Locations**
*   **Ajout :** Comparateur de prix fournisseurs permettant de choisir le loueur le plus économique selon la durée (jour/semaine/mois).
*   **Ajout :** Gestion des "Locations Sortantes" pour facturer la location de votre propre matériel à des tiers.
*   **Ajout :** Système d'alertes automatiques (J-1) avant la fin d'un contrat et application de pénalités de retard journalières paramétrables.

**Interventions**
*   **Ajout :** Formulaires d'Intervention Dynamiques (Check-lists sur-mesure) — créez des modèles de rapport par type d'intervention (Régie/Forfait) avec blocs de champs (texte, nombre, case à cocher, liste, date, photo). Le technicien renseigne le rapport depuis son espace, et la clôture est **bloquée** tant que les champs obligatoires ne sont pas complétés.

**Immobilisations & Actifs**
*   **Ajout :** Module de transfert inter-chantiers pour suivre les mouvements du gros matériel avec génération automatique de **Bons de Transport (PDF)**.
*   **Ajout :** Interface d'audit d'inventaire optimisée pour le scan mobile (QR Code) afin de valider la présence physique des actifs sur le terrain.
*   **Ajout :** Nouveau statut "En location (Externe)" pour les actifs loués à des tiers.

**GPAO (Gestion de production)**
*   **Ajout :** Module complet de gestion des machines (suivi opérationnel, compteurs d'heures et intervalles de maintenance).
*   **Ajout :** Gestion des rebuts de fabrication permettant de déclarer des composants perdus avec motifs (erreur humaine, défaut matière).

**Administration & RH**
*   **Ajout :** Interface de gestion des Rôles et Permissions pour affiner les accès utilisateurs.
*   **Ajout :** Simulateur de paie pour estimer les coûts employeurs et le net salarié.
*   **Correction :** Amélioration de l'OCR pour la lecture automatique des dates et montants sur les notes de frais.

---

### 🛠 Fix Général
*   **Traduction :** Harmonisation complète des interfaces avec l'application systématique des libellés en français sur l'ensemble des champs (Référence, Statut, Montant, Créé le, etc.).
*   **Facturation :** Ajout d'une sécurité anti-doublon via une clé de facturation unique (`billing_key`) pour les contrats récurrents.
*   **Performance :** Mise à jour des moteurs de rendu PDF et des composants de tableaux de bord pour une meilleure fluidité.
