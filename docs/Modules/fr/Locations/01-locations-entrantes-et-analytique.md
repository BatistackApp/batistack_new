---
title: Locations Entrantes (Fournisseurs)
icon: heroicon-o-arrow-down-on-square-stack
order: 2
---

# 📥 Locations Entrantes & Analytique

La location de matériel auprès de fournisseurs externes représente souvent un poste de dépense majeur sur les chantiers. Batistack vous aide à maîtriser ces coûts.

## 1. Contrats et Facturation Automatisée

Lorsque vous louez du matériel, vous définissez une période de facturation (Journalière, Hebdomadaire, Mensuelle).
L'ERP va générer **automatiquement** une Facture Fournisseur Brouillon à chaque échéance du contrat. 
*(Astuce : Les petites factures de moins de 500 € peuvent même être validées automatiquement par le système, vous faisant gagner un temps précieux).*

## 2. L'Imputation Analytique en Temps Réel

Dès qu'une machine est louée pour un chantier spécifique, le système calcule son coût analytique au jour le jour.
La rentabilité globale de votre chantier est donc actualisée en temps réel, sans attendre de recevoir la facture finale du loueur.

## 3. Dépassements et Pénalités de Retard

Oublier de rendre une pelleteuse à temps coûte très cher. 
- Lors de la saisie du contrat, vous renseignez la **Date de fin prévue** et le **Taux de pénalité journalière**.
- À J-3, le système envoie une **Alerte** (Notification) au conducteur de travaux.
- En cas de dépassement, le moteur calcule les majorations et les intègre automatiquement à votre coût analytique, vous avertissant de l'hémorragie financière.

## 4. Facturation Interne Automatique (Refacturation)

Possédez-vous du matériel que vous déployez sur vos **propres chantiers** ? Plutôt que de le laisser "gratuit" dans l'analyse du chantier, Batistack peut lui appliquer un **coût d'usage interne** et l'imputer au budget.

### 4.1. Activer la refacturation sur une immobilisation

Depuis le module **Immobilisations**, ouvrez la fiche d'une machine et renseignez la section **"Refacturation interne"** :
- **Coût journalier interne** : le tarif HT facturé au chantier par jour d'utilisation.
- **Périodicité de refacturation** : Journalière, Hebdomadaire, Mensuelle ou Annuelle.

### 4.2. Affecter l'actif à un chantier

Sélectionnez le **Chantier d'imputation analytique** sur la fiche de l'immobilisation. Dès l'affectation :
- Une **Facture Interne de Location** est immédiatement générée pour la période en cours (montant = nombre de jours × tarif journalier).
- Le coût est automatiquement imputé au poste **budget matériel** du chantier et intégré à sa rentabilité.

### 4.3. Suivi des factures internes

Le panel **Locations** contient la ressource **"Factures internes (Refacturation)"** qui liste toutes les factures générées (actif, chantier, période, jours, montant, statut). Les factures sont **générées automatiquement** à chaque échéance de période sans doublon (clé de facturation unique).

> [!TIP]
> Pour facturer une période passée manquée (ex: au moment de l'activation du tarif), la commande `php artisan locations:bill-internal-rentals` régénère les factures dues pour la période en cours.
