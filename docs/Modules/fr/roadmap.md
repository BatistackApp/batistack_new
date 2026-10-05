---
title: Roadmap (Feuille de Route)
icon: heroicon-o-map
order: 999
---

# 🚀 Roadmap & Améliorations Futures

Batistack est un ERP en constante évolution. Afin de vous offrir toujours plus d'automatisation et de simplicité, notre équipe de développement travaille en continu sur de nouvelles fonctionnalités.

> [!NOTE]
> **Trajectoire v1.0.0** : BATISTACK est en préparation de la release candidate **`v1.0.0-rc.1`**. Les critères de passage, les preuves et les blocages sont suivis dans [la roadmap 1.0.0](../../avancement/v1.0.0-roadmap.md), la [checklist GO / NO-GO](../../avancement/v1.0.0-go-no-go.md) et le [statut des modules](../../1.0.0/module-status.md).
> Les fonctionnalités livrées au fil des versions sont détaillées dans le [Changelog](changelog.md).

---

Voici les évolutions qui restent planifiées ou volontairement hors périmètre de la 1.0.0. Les fonctionnalités déjà livrées sont décrites dans les pages des modules et ne doivent pas être interprétées comme des éléments encore à développer.

## 📦 Articles & Stocks (Inventaire)
- **Traçabilité des Lots et Dates de Péremption (Qualité)** : Gérer les numéros de lots (`batch_number`) et les dates de péremption (`expiration_date`) sur les mouvements de stock pour les matériaux sensibles (chimie, EPI) et alerter avant péremption.
- **Extraction BIM vers Panier d'Achat (BOM)** : Créer une passerelle avec le module Vision 3D pour extraire les quantitatifs d'une maquette IFC et générer automatiquement une liste de courses ou un bon de commande en déduisant le stock actuel.
- **Traçabilité avancée des lots et péremptions** : compléter la gestion des lots et dates de péremption pour les matériaux sensibles.

## 🏦 Banque & Trésorerie
- **Suivi Analytique par Chantier (Project Accounting)** : Possibilité de ventiler ou d'affecter une transaction bancaire (dépense ou recette) directement à un Chantier pour alimenter le tableau de rentabilité financière en temps réel.
- **Tableau de Bord : Prévisionnel de Trésorerie (Cash-flow Forecast)** : Graphique prévisionnel à 3 mois croisant le solde bancaire avec les échéances des factures (clients/fournisseurs) et le planning des appels de fonds des chantiers.
- Le module Comptabilité et les exports FEC / Sage / Cegid sont livrés ; les améliorations futures concernent les contrôles et exports complémentaires.

## 🛠️ Interventions (SAV & Maintenance)
- **Devis et Facturation sur Place (Mobilité)** : Possibilité pour le technicien de générer un devis sur mobile pour une pièce défectueuse, de le faire signer, et d'émettre la facture directement chez le client.
- **Tracking GPS des camions techniques** : Persister et afficher la position des véhicules d'intervention remontée par l'application mobile.

## 💶 Paie (Payroll)
- **Télétransmission DSN Automatique** : Connexion M2M (Machine-to-Machine) avec Net-Entreprises pour télétransmettre vos déclarations sociales en un clic sans générer de fichier CSV.

## 🤝 Tiers (CRM)
- **Collecte Automatique de Conformité** : Connexion aux plateformes tierces (e-Attestations, Provigis) pour récupérer et mettre à jour automatiquement les Kbis et attestations URSSAF de vos sous-traitants.
- Le rafraîchissement périodique du statut juridique est livré ; les extensions de conformité restent à planifier.

## 🏭 Production & Logistique (GPAO, Locations)
- **Connexion IoT (Machines Ateliers)** : Remontée directe des temps de cycle et des quantités produites depuis les machines numériques (OPC-UA) vers l'ERP.
- **Géolocalisation du Gros Matériel (GPS)** : Pour les engins lourds **en propre**, remonter leur position en temps réel sur la carte du chantier via API.

## 🚜 Location (Gestion du Matériel)
- **Suivi Géolocalisé du matériel loué** : Remonter la position du gros matériel équipé de capteurs GPS (via API externe) sur la fiche `RentalContract`.

## ⚙️ Core (Fondations)
- **GED avancée** : poursuivre le classement, la recherche et l'archivage des documents selon les besoins clients.
- **Signature avancée AES / eIDAS** : fonctionnalité hors périmètre 1.0.0, planifiée en 1.1.x.

## 🚫 Hors périmètre 1.0.0

- Télétransmission DSN avancée — voir le module [Paie](./Paie/index.md).
- Collecte automatique avancée des documents légaux — voir le module [Tiers](./Tiers/index.md).
- Connexion IoT / OPC-UA — voir le module [GPAO](./GPAO/index.md).
- OCR avancé et signature AES / eIDAS — fonctionnalités 1.1.x.

---

> [!TIP]
> **Participez à l'évolution !**
> Une fonctionnalité vous manque cruellement ? N'hésitez pas à nous faire remonter votre besoin pour que nous puissions l'étudier et l'intégrer à cette feuille de route.
