---
title: Roadmap (Feuille de Route)
icon: heroicon-o-map
order: 999
---

# 🚀 Roadmap & Améliorations Futures

Batistack est un ERP en constante évolution. Afin de vous offrir toujours plus d'automatisation et de simplicité, notre équipe de développement travaille en continu sur de nouvelles fonctionnalités. 

Voici un aperçu de la feuille de route des prochains mois, regroupée par module, directement issue de nos retours d'expérience et des suggestions de nos utilisateurs.

---

## 📦 Articles & Stocks (Inventaire)
- **Traçabilité des Lots et Dates de Péremption (Qualité)** : Gérer les numéros de lots (`batch_number`) et les dates de péremption (`expiration_date`) sur les mouvements de stock pour les matériaux sensibles (chimie, EPI) et alerter avant péremption.
- **Extraction BIM vers Panier d'Achat (BOM)** : Créer une passerelle avec le module Vision 3D pour extraire les quantitatifs d'une maquette IFC et générer automatiquement une liste de courses ou un bon de commande en déduisant le stock actuel.

## 🏦 Banque & Trésorerie
- **Suivi Analytique par Chantier (Project Accounting)** : Possibilité de ventiler ou d'affecter une transaction bancaire (dépense ou recette) directement à un Chantier pour alimenter le tableau de rentabilité financière en temps réel.
- **Tableau de Bord : Prévisionnel de Trésorerie (Cash-flow Forecast)** : Graphique prévisionnel à 3 mois croisant le solde bancaire avec les échéances des factures (clients/fournisseurs) et le planning des appels de fonds des chantiers.

## 🏗️ Chantiers (Suivi de Travaux)
- **Levée des Réserves & OPR (Snagging)** : Création d'un outil mobile-first pour signaler les défauts (photos, plans), les assigner aux sous-traitants concernés, et suivre leur résolution jusqu'à la levée de la réserve par le client.
- **Génération Automatisée du PPSPS (Sécurité)** : Compiler automatiquement le Plan Particulier de Sécurité et de Protection de la Santé en croisant les tâches prévues, le matériel alloué et les fiches de sécurité des produits utilisés.

## 🤝 Commerce (Ventes & Achats)
- **Gestion Structurée des Avenants (Travaux Supplémentaires / TS)** : Créer un flux spécifique de "Devis d'Avenant" rattaché à une commande principale pour faire évoluer le budget du chantier en cours de route et l'intégrer aux situations de travaux.

## 🏭 GPAO (Gestion de Production)
- **Traçabilité Ascendante & Descendante (Généalogie de Production)** : Fusionner les données de l'Ordre de Fabrication, du contrôle qualité et des numéros de lots (Articles) pour créer un "Passeport Produit" permettant de retracer l'origine exacte des matériaux de chaque produit fini.

## 🏗️ Immobilisations (Matériel & Engins)
- **Portail de Déclaration de Casse / Sinistre (PWA Salarié)** : Workflow mobile simplifié permettant aux ouvriers sur chantier de scanner le QR Code d'un outil cassé et de soumettre une déclaration (photo) qui déclenche immédiatement un ticket de maintenance au dépôt.

## 🛠️ Interventions (SAV & Maintenance)
- **Contrats d'Entretien Récurrents (Maintenance Préventive)** : Génération automatique des interventions à planifier et envoi de rappels (SMS/Email) aux clients pour leurs échéances annuelles.
- **Formulaires d'Intervention Dynamiques (Check-lists sur-mesure)** : Création de modèles de rapports exigeant la saisie de champs obligatoires spécifiques par le technicien selon le type d'intervention avant sa clôture.
- **Devis et Facturation sur Place (Mobilité)** : Possibilité pour le technicien de générer un devis sur mobile pour une pièce défectueuse, de le faire signer, et d'émettre la facture directement chez le client.

## 🚜 Location (Gestion du Matériel)
- **Facturation Interne Automatique (Refacturation)** : Génération automatique de factures de location internes lorsqu'une immobilisation de l'entreprise est affectée à un chantier, afin d'imputer le coût exact au budget de ce chantier.
- **État des Lieux Mobile (Protection Litiges Fournisseurs)** : Workflow PWA permettant au chef de chantier de prendre des photos horodatées à la réception et à la restitution du matériel loué pour se protéger contre les facturations abusives de casse.
- **Comparateur de Prix Fournisseurs Intelligent** : Assistant croisant le besoin matériel d'un chantier avec les grilles tarifaires fournisseurs enregistrées pour suggérer automatiquement le loueur le plus compétitif.

## 💶 Paie (Payroll)
- **Paiement des Salaires par API Bancaire (Open Banking)** : Intégration via l'API Bridge (Bankin') ou Qonto pour déclencher les virements SEPA des salaires en un clic directement depuis l'ERP, sans avoir à exporter/importer de fichiers XML vers la banque.

## 👥 RH & QSE (Ressources Humaines)
- **Tableau de Bord Sécurité (AT/MP)** : Calcul en temps réel et automatique du Taux de Fréquence (TF) et du Taux de Gravité (TG) des accidents du travail pour les dossiers d'appels d'offres publics.

## 🤝 Tiers (CRM & SRM)
- **Score de Solvabilité / Risque Financier (API Ouverte)** : Intégration d'une API publique (type Pappers ou data.gouv.fr) pour récupérer et afficher automatiquement le statut juridique (sauvegarde, redressement, liquidation) des sous-traitants afin de bloquer la contractualisation avec des entreprises à risque.

## ⚙️ Core (Fondations)
- **Gestion Documentaire (GED) Complète** : Interface d'arborescence pour visualiser, classer et archiver facilement tous les PDF générés par l'ERP.
- **Workflows d'Approbation Multiples** : Permettre d'avoir plusieurs signataires (ex: Conducteur de travaux + Directeur technique) sur un même document avant sa validation finale.

## 👥 Ressources Humaines & Paie
- **Télétransmission DSN Automatique** : Connexion M2M (Machine-to-Machine) avec Net-Entreprises pour télétransmettre vos déclarations sociales en un clic sans générer de fichier CSV.

## 🤝 Tiers (CRM)
- **Collecte Automatique de Conformité** : Connexion aux plateformes tierces (e-Attestations, Provigis) pour récupérer et mettre à jour automatiquement les Kbis et attestations URSSAF de vos sous-traitants.

## 🏭 Production & Logistique (GPAO, Locations)
- **Connexion IoT (Machines Ateliers)** : Remontée directe des temps de cycle et des quantités produites depuis les machines numériques (OPC-UA) vers l'ERP.
- **Géolocalisation du Gros Matériel (GPS)** : Pour les engins lourds loués ou en propre, remonter leur position en temps réel sur la carte du chantier via API.

---

> [!TIP]
> **Participez à l'évolution !**
> Une fonctionnalité vous manque cruellement ? N'hésitez pas à nous faire remonter votre besoin pour que nous puissions l'étudier et l'intégrer à cette feuille de route.
