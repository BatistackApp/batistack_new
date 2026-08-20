---
title: Roadmap (Feuille de Route)
icon: heroicon-o-map
order: 999
---

# 🚀 Roadmap & Améliorations Futures

Batistack est un ERP en constante évolution. Afin de vous offrir toujours plus d'automatisation et de simplicité, notre équipe de développement travaille en continu sur de nouvelles fonctionnalités.

> [!NOTE]
> **Trajectoire v1.0.0** : la version courante est la **v0.38.0**. La feuille de route détaillée de passage à la **1.0.0** (critères, issues bloqueuses, lots de correction, estimation) est disponible dans [docs/avancement/v1.0.0-roadmap.md](../../avancement/v1.0.0-roadmap.md) et suivie sur GitHub via le milestone **v1.0.0**.
> Les fonctionnalités livrées au fil des versions sont détaillées dans le [Changelog](changelog.md).

---

Voici un aperçu de la feuille de route des prochains mois, regroupée par module, directement issue de nos retours d'expérience et des suggestions de nos utilisateurs.

## 📦 Articles & Stocks (Inventaire)
- **Traçabilité des Lots et Dates de Péremption (Qualité)** : Gérer les numéros de lots (`batch_number`) et les dates de péremption (`expiration_date`) sur les mouvements de stock pour les matériaux sensibles (chimie, EPI) et alerter avant péremption.
- **Extraction BIM vers Panier d'Achat (BOM)** : Créer une passerelle avec le module Vision 3D pour extraire les quantitatifs d'une maquette IFC et générer automatiquement une liste de courses ou un bon de commande en déduisant le stock actuel.
- **Gestion des emplacements de stockage (bin-picking)** : Affecter et localiser les articles par emplacement physique dans les entrepôts.

## 🏦 Banque & Trésorerie
- **Suivi Analytique par Chantier (Project Accounting)** : Possibilité de ventiler ou d'affecter une transaction bancaire (dépense ou recette) directement à un Chantier pour alimenter le tableau de rentabilité financière en temps réel.
- **Tableau de Bord : Prévisionnel de Trésorerie (Cash-flow Forecast)** : Graphique prévisionnel à 3 mois croisant le solde bancaire avec les échéances des factures (clients/fournisseurs) et le planning des appels de fonds des chantiers.
- **Module Comptabilité complet** : Écritures depuis les transactions bancaires et exports FEC / Sage / Cegid.

## 🛠️ Interventions (SAV & Maintenance)
- **Devis et Facturation sur Place (Mobilité)** : Possibilité pour le technicien de générer un devis sur mobile pour une pièce défectueuse, de le faire signer, et d'émettre la facture directement chez le client.
- **Tracking GPS des camions techniques** : Persister et afficher la position des véhicules d'intervention remontée par l'application mobile.

## 💶 Paie (Payroll)
- **Télétransmission DSN Automatique** : Connexion M2M (Machine-to-Machine) avec Net-Entreprises pour télétransmettre vos déclarations sociales en un clic sans générer de fichier CSV.

## 🤝 Tiers (CRM)
- **Collecte Automatique de Conformité** : Connexion aux plateformes tierces (e-Attestations, Provigis) pour récupérer et mettre à jour automatiquement les Kbis et attestations URSSAF de vos sous-traitants.
- **Rafraîchissement périodique du statut juridique** : Job planifié de mise à jour automatique du `legal_status` des sous-traitants pour fiabiliser le garde-fou de contractualisation.

## 🏭 Production & Logistique (GPAO, Locations)
- **Connexion IoT (Machines Ateliers)** : Remontée directe des temps de cycle et des quantités produites depuis les machines numériques (OPC-UA) vers l'ERP.
- **Géolocalisation du Gros Matériel (GPS)** : Pour les engins lourds loués ou en propre, remonter leur position en temps réel sur la carte du chantier via API.

## 🚜 Location (Gestion du Matériel)
- **Suivi Géolocalisé du matériel loué** : Remonter la position du gros matériel équipé de capteurs GPS (via API externe) sur la fiche `RentalContract`.

## ⚙️ Core (Fondations)
- **Gestion Documentaire (GED) Complète** : Interface d'arborescence pour visualiser, classer et archiver facilement tous les PDF générés par l'ERP.
- **Workflows d'Approbation Multiples** : Permettre d'avoir plusieurs signataires (ex: Conducteur de travaux + Directeur technique) sur un même document avant sa validation finale.

---

> [!TIP]
> **Participez à l'évolution !**
> Une fonctionnalité vous manque cruellement ? N'hésitez pas à nous faire remonter votre besoin pour que nous puissions l'étudier et l'intégrer à cette feuille de route.