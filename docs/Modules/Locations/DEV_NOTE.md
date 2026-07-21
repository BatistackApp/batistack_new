# Notes de Développement - Module Locations

## Ce qui est fait

- **Modélisation de la base de données** :
  - Création de la table `rental_contracts` pour gérer les contrats de location.
  - Création de la table `rental_contract_lines` pour détailler le matériel loué (désignation, quantité, prix).
  - Implémentation des énumérations `RentalStatus` (Brouillon, Actif, Terminé) et `RentalBillingPeriod` (Journalier, Hebdomadaire, Mensuel, Annuel).

- **Backend & Logique Métier** :
  - `RentalCostService` : Permet de calculer dynamiquement le coût analytique cumulé d'une location en fonction de sa durée de vie et de son coût journalier.
  - `RentalBillingService` : Génère automatiquement (ou manuellement) une facture fournisseur brouillon (`SupplierInvoice` en état `DRAFT`) basée sur la période de facturation, tout en liant automatiquement le bon taux de TVA.
  - Intégration analytique : Le service `ChantierAnalyticService` a été modifié pour agréger en temps réel les coûts de location dans le calcul global de la rentabilité d'un chantier (Total Cost = Material + Labor + **Rental**).

- **Tâches d'Automatisation (Cron & Observers)** :
  - `RentalContractObserver` : Assure la cohérence des statuts lors des mises à jour des dates.
  - `ProcessRecurringRentalsCommand` : Commande journalière qui analyse les contrats échus et lance la facturation périodique automatiquement.
  - `RentalExpirationAlertJob` : Logique de notification asynchrone (pour alerter des échéances de fin de location dans moins de 3 jours).

- **Interface Filament** :
  - Panel dédié `LocationsPanelProvider` mis en place pour dissocier ce référentiel.
  - Widget `ActiveRentalsWidget` offrant des KPI sur l'état des locations.
  - Ressource complète `RentalContractResource` incluant Formulaire (avec `Repeater` de lignes), Tableau de bord (avec filtres avancés) et une Infolist (vue détaillée) intégrant des actions rapides (Générer facture, Terminer location).

- **Tests** :
  - Des tests unitaires/fonctionnels exhaustifs couvrent l'analytique et la génération de facture. Tous passent avec succès.

- **Réception des factures automatisées** :
  - `RentalBillingService` a été étendu pour inclure la validation automatique du brouillon (passage en `VALIDATED`) si le montant TTC de la facture est inférieur ou égal à 500 € (configurable via `locations.auto_validate_threshold`).

---

## Ce qu'il reste à faire

- (Aucune tâche en attente)

---

## Idées d'amélioration ou nouvelles fonctionnalités

1. **Intégration d'un calendrier visuel** :
   - Ajouter un composant FullCalendar dans Filament pour visualiser les dates de début et de fin de location sur l'ensemble des chantiers (idéal pour le conducteur de travaux qui doit organiser des relèves de grues, bases de vie).
2. **Suivi géolocalisé** :
   - Si du gros équipement (ex: pelles, grues) est loué avec des capteurs GPS, pouvoir remonter leur position via une API externe directement sur la fiche d'information du `RentalContract`.
3. **Mise en commun des ressources matérielles** :
   - Lier le module `Locations` au module `Immobilisation` ou `Flottes` pour que, dans la vue "Chantier", on ait un seul tableau de bord unifié "Ressources Déployées" (combinant Matériel Loué + Matériel en propre de l'entreprise).
4. **Scoring fournisseurs** :
   - Permettre de noter le fournisseur en fin de contrat (état du matériel, respect des délais de livraison) pour générer un "Score" fournisseur visible par les acheteurs lors de futures locations.
