# Statut des modules pour BATISTACK 1.0.0

> Mise à jour : 01/10/2026. Ce document est la référence synthétique du périmètre fonctionnel de la release candidate.

## Légende

- **Validé** : périmètre livré et couvert par les validations disponibles.
- **À valider** : fonctionnalité livrée, mais preuve de release ou documentation à compléter.
- **Reporté** : explicitement hors périmètre 1.0.0.

## Périmètre

| Module | Statut 1.0.0 | Points à vérifier avant RC |
|---|---|---|
| Core | À valider | permissions, audit, GED, signatures, configuration |
| Tiers / CRM | À valider | isolation des données, conformité, portails sous-traitants |
| Chantiers | À valider | portails terrain, journaux, documents et permissions |
| Commerce | À valider | devis, commandes, factures, paiements, avoirs et PDF |
| Articles / Stocks | À valider | mouvements, réservations, inventaires et stock négatif |
| Banque | À valider | synchronisation, rapprochement, paiements et trésorerie |
| Comptabilité | À valider | écritures, lettrage, FEC et exports comptables |
| RH | À valider | contrats, absences, pointages, documents et portail salarié |
| Paie | À valider | bulletins, écritures, SEPA, exports et portail salarié |
| Flottes | À valider | affectations, états des lieux, coûts et permissions |
| Locations | À valider | locations entrantes, sortantes, facturation et pénalités |
| Immobilisations | À valider | amortissements, cessions, inventaires et écritures |
| GPAO | À valider | ordres de fabrication, stocks, rebuts et maintenance |
| Interventions | À valider | tickets, facturation, portails et contrats récurrents |
| Vision 3D | À valider | import, miniatures, annotations et permissions |
| Laser | À valider | conversion devis-commande et chaîne de facturation |

## Reporté après 1.0.0

- Télétransmission DSN avancée.
- Collecte automatique avancée des documents légaux.
- OCR avancé.
- Signature AES / eIDAS avancée.
- Connexion IoT / OPC-UA.

## Preuves communes

- Suite complète de tests validée au vert par l'équipe.
- CI, lint et Codecov validés après la PR #480.
- Avertissements PSR-4 corrigés.
- Tests ciblés de conversion des devis Laser ajoutés.

## Validation restante

Chaque module doit encore être vérifié dans le scénario de release : installation propre, migration, sauvegarde/restauration, rollback, permissions et documentation opérationnelle.

## Documentation opérationnelle

- [Installation](./installation.md)
- [Mise à niveau](./upgrade.md)
- [Sauvegarde et restauration](./backup-restore.md)
- [Rollback](./rollback.md)
- [Déploiement](./deployment.md)
- [Limitations connues](./known-limitations.md)
