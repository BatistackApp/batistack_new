---
title: Changelog (Mises à jour)
icon: heroicon-o-clipboard-document-check
order: 1000
---

# 🆕 Changelog & Notes de Version

Bienvenue dans le journal des modifications (Changelog) de Batistack.
Vous retrouverez ici la liste exhaustive des nouveautés, améliorations et corrections apportées à votre ERP au fil du temps.

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
