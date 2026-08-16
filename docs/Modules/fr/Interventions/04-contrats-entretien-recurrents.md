---
title: Contrats d'entretien récurrents
icon: heroicon-o-arrow-path
order: 4
---

# 📅 Contrats d'Entretien Récurrents (Maintenance Préventive)

Cette fonctionnalité automatise les interventions de maintenance préventive pour vos clients sous contrat d'entretien.

## 🎯 Principe

Un **contrat d'entretien** définit une relation de maintenance récurrente sur un **équipement client** (fréquence, prix forfaitaire, période de validité). Le système génère automatiquement une intervention planifiée à chaque échéance et relance le client par e-mail avant la visite.

## 📂 Contrats d'Entretien

L'écran **Maintenance préventive → Contrats d'entretien** (panel Admin) permet de créer et suivre les contrats.

### Informations du contrat

| Champ | Description |
|---|---|
| **Client** | Le client propriétaire de l'équipement (obligatoire). |
| **Équipement client** | Le matériel couvert par le contrat (obligatoire, filtré par client). |
| **Chantier** | Lien optionnel vers un chantier. |
| **Nom du contrat** | ex : « Contrat annuel groupe frigorifique ». |
| **Fréquence** | Mensuelle, Trimestrielle, Semestrielle ou Annuelle. |
| **Prix forfaitaire HT** | Montant facturé à chaque intervention générée (type **Forfait**). |
| **Début / Fin** | Période de validité du contrat. La **fin** est optionnelle. |
| **Prochaine échéance** | Date de la prochaine visite. Tant qu'elle n'est pas renseignée, aucune intervention n'est générée. |
| **Statut** | Actif, En pause, Terminé, Annulé. |

### Références

Chaque contrat reçoit une référence unique `MC-AAAA-NNNN` (ex : `MC-2026-0001`).

## ⚙️ Génération automatique

Chaque matin à **06:00** (heure de Paris), la commande `interventions:generate-maintenance` :

1. Sélectionne les contrats **Actifs** dont la **prochaine échéance est atteinte**.
2. Crée une intervention **Planifiée** de type **Forfait** (avec le prix du contrat, l'équipement et le client).
3. Avance la prochaine échéance selon la fréquence (mensuelle → +1 mois, etc.).
4. Si la nouvelle échéance dépasse la **fin du contrat**, le contrat passe au statut **Terminé**.

> [!TIP]
> L'action **« Générer maintenant »** sur la liste permet de déclencher manuellement une génération, même avant l'échéance.

> [!NOTE]
> Les contrats **en pause** sont ignorés. Reprendre le contrat (action **Pause / Reprendre**) le rend de nouveau éligible.

## ✉️ Rappels client automatiques

Chaque matin à **07:00** (heure de Paris), la commande `interventions:remind-maintenance` envoie un e-mail au client aux échéances **J-30, J-15 et J-7** avant la prochaine visite.

- Chaque rappel n'est envoyé **qu'une seule fois** par échéance (journal de déduplication).
- Le destinataire est l'**e-mail du contact principal** du client, sinon l'e-mail du client.
- Les délais sont configurables dans `config/interventions.php` (`maintenance_reminder_days_before`).

## 🔧 Commandes artisan

| Commande | Rôle | Planification |
|---|---|---|
| `interventions:generate-maintenance` | Génère les interventions arrivées à échéance | Tous les jours à 06:00 |
| `interventions:remind-maintenance` | Envoie les rappels J-30/J-15/J-7 | Tous les jours à 07:00 |

Les deux commandes acceptent un paramètre optionnel `--date=YYYY-MM-DD` pour un traitement à une date de référence (utile en test).