# 🚀 BATISTACK - Notes de Développement & Améliorations par Module

Suite à l'analyse approfondie de la base de code, de l'architecture backend et du passage réussi à 100% de la suite de tests PestPHP (dont les modules Tiers, Chantiers et Commerce !), voici la note de développement détaillée pour chaque module.

---

## 1. ⚙️ Module Core (Architecture & Configuration)
*   **Ce qui est fait :** Architecture robuste basée sur le multi-tenancy (Scopes par entreprise). Le backend est solidement configuré, la gestion des unités, de la TVA et de la sécurité de base est testée et validée (100%). Les bases des panels Filament sont posées. **Nouveautés :** 
    *   Migration de l'environnement de développement vers **Laravel Herd** pour des performances natives sous Windows.
    *   Implémentation d'un Pipeline CI/CD complet (GitHub Actions) avec versionnement sémantique automatique et déploiement via Webhook sur AAPanel.
    *   Création du widget `AppVersionWidget` sur le tableau de bord pour afficher la version en cours.
*   **Ce qu'il reste à faire :** Paramétrage final de Filament (Filament Shield) pour la gestion granulaire des rôles et permissions par entreprise. Personnalisation du Dashboard de base.
*   **💡 Idées d'amélioration :**
    *   **Activity Logging poussé :** Intégrer les packages Spatie Activitylog & Log Viewer (déjà présents dans `composer.json`) sur chaque modèle sensible.
    *   **Tableau de bord Super-Admin :** Une vue globale inter-entreprises pour gérer vos licences / abonnements SaaS.

---

## 2. 👷 Module RH & Pointage
*   **Ce qui est fait :** Modèles backend ultra-complets et testés à 100% : Employés, Contrats, Temps pointés, Absences, Visites médicales. Synergies renforcées avec Flottes et Chantiers. 
    *   **Nouveautés :** La **Pointeuse Biométrique** et l'**Onboarding Digitalisé** autonome des candidats ont été implémentés ! La correction du système de téléversement Livewire en production (gestion des Reverse Proxies / HTTPS) est validée, et le recalcul asynchrone des heures validées est fiabilisé.
*   **Ce qu'il reste à faire :** Créer des interfaces Filament agréables pour la visualisation des plannings (vues calendriers / Calendar Widget) et la gestion quotidienne.
*   **💡 Idées d'amélioration :**
    *   **Pointeuse Mobile Avancée :** Coupler la pointeuse avec la géolocalisation GPS sur le chantier.
    *   **Alertes automatisées :** Notifications push (via le package WebPush) ou emails automatiques aux RH 30 jours avant l'expiration d'une visite médicale ou d'une certification.

---

## 3. 📦 Module Articles & Stocks
*   **Ce qui est fait :** Gestion fine de l'inventaire : multi-entrepôts, transferts de stocks, numéros de série, et le système de "recettes". L'enregistrement automatique et robuste des mouvements de stock (Audit Log avec PUMP recalculé dynamiquement) est en place.
*   **Ce qu'il reste à faire :** Finaliser la couche visuelle (Panels Filament) et notamment la liaison avec le frontend pour les entrées/sorties manuelles.
*   **💡 Idées d'amélioration :**
    *   **Génération de Bons de Réapprovisionnement :** Créer un job qui analyse les "stocks minimums" et génère des brouillons de Commandes d'Achat automatiquement.
    *   **Lecteur Code-barres :** Utiliser le plugin `filament-barcode-scanner-field` pour scanner le matériel directement depuis l'application avec la caméra du téléphone lors du retour de matériel.

---

## 4. 🚐 Module Flottes (Véhicules)
*   **Ce qui est fait :** Assignations sécurisées, suivi des dépenses (carburant, amendes), contrôles de conformité RH. Import CSV des données de carburant avec détection intelligente des anomalies (siphonnage), et intégration des coûts de flotte dans l'analyse financière des chantiers.
*   **Ce qu'il reste à faire :** Mettre en place l'interface de gestion de flotte Filament.
*   **💡 Idées d'amélioration :**
    *   **Alertes Environnementales :** Connecter la brique d'alertes météo aux conducteurs (historique de conduite vs météo) pour justifier d'éventuels sinistres.
    *   **Carte Interactive (Live) :** Lier les trackers GPS des véhicules au système avec `filament-leaflet` pour avoir une carte en direct de votre flotte.

---

## 5. 🤝 Module Tiers (CRM & Annuaire)
*   **Ce qui est fait :** Le backend (Clients, Fournisseurs, Sous-traitants, Banques) est entièrement couvert. Le système de Scoring Fournisseurs est en place (basé sur délais, qualité, litiges). Le portail externe Filament pour les Sous-traitants est configuré et sécurisé.
*   **Ce qu'il reste à faire :** Terminer les autres pages Filament (ex: auto-complétion SIREN/SIRET). L'intégration CRM a été écartée suite aux choix stratégiques.
*   **💡 Idées d'amélioration :**
    *   **Portail Externe "Clients" :** Fournir un accès pour que vos clients voient leurs factures.

---

## 6. 🏗️ Module Chantiers
*   **Ce qui est fait :** Logique métier terminée et couvrant 100% des tests. L'architecture supporte les coûts, tâches et DOE. Ajout de l'aperçu financier (Widget) indiquant le budget vendu, les coûts engagés (incluant la Flotte) et la marge réelle. Inclusion des fiches techniques des articles dans le DOE et archivage en ZIP fiabilisé.
*   **Ce qu'il reste à faire :** Finaliser l'UI globale qui sera le cœur de l'application.
*   **💡 Idées d'amélioration :**
    *   **Suivi de Chantier Vocal / Photo (PWA) :** Permettre au conducteur de chantier d'ouvrir l'app, de prendre des photos de malfaçons, de dicter son rapport au micro (Speech-to-Text), et que ça remplisse le `ChantierLog` automatiquement.
    *   **Gantt Interactif :** Intégrer un diagramme de Gantt interactif pour planifier les tâches.

---

## 7. 💶 Module Commerce / Facturation
*   **Ce qui est fait :** Le tunnel de facturation est backend-ready et validé avec 100% de succès sur **182 tests** : devis, bons de commandes, situations, factures. Le workflow de changement de statuts et de validation est parfaitement fonctionnel et stable. Node.js et Browsershot sont désormais prêts (en local et via Docker) pour la génération PDF.
*   **Ce qu'il reste à faire :** La génération et le design final des fichiers PDF. Création des interfaces Filament du module Commerce.
*   **💡 Idées d'amélioration :**
    *   **Signature Électronique (DocuSeal) :** Rendre les devis signables électroniquement avec valeur légale.
    *   **Paiement en ligne / Prélèvements SEPA :** Ajouter un lien de paiement Stripe sur la facture pour accélérer le règlement client.

---

## 8. ⏳ Modules Futurs (Phase 2)

*   **Notes de Frais :** Utiliser une API externe d'OCR pour que les employés prennent juste leur ticket de restaurant ou de péage en photo et que Batistack remplisse le montant et la TVA tout seul.
*   **GPAO (Atelier / Production) :** Si l'entreprise fabrique sur-mesure (ex: charpente, menuiserie), interfacer le module stock avec les commandes chantiers.
*   **Vision 3D (BIM) :** Intégrer la visionneuse "xeokit" ou "IFC.js" directement dans Filament pour pouvoir naviguer dans la maquette 3D du bâtiment depuis le chantier.
