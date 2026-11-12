# Statut des modules pour BATISTACK 1.0.0

> Mise à jour : 12/11/2026. Ce tableau est une synthèse de périmètre, pas une certification de recette fonctionnelle.

## Légende

- **Validé** : périmètre livré et couvert par des preuves de validation représentatives.
- **À valider** : fonctionnalité présente, mais recette de release ou preuve opérationnelle à compléter.
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

## Preuves transverses déjà intégrées

Les PR #542 à #547 ont apporté des correctifs ciblés sur l'identité et l'isolation des portails, les migrations, les paiements, le stockage documentaire et les contrôles CI/CD. Les contrôles ciblés exécutés sur ces PR sont référencés dans leurs exécutions GitHub Actions.

Ces validations ciblées ne remplacent pas la recette métier de chaque module, les tests globaux sur le commit candidat, ni les tests des intégrations réelles.

## Validation restante

Chaque module doit encore être vérifié dans le scénario de release : installation propre, migration sur le moteur de production, sauvegarde/restauration, rollback, permissions, intégrations activées et documentation opérationnelle.

## Documentation opérationnelle

- [Installation](./installation.md)
- [Mise à niveau](./upgrade.md)
- [Sauvegarde et restauration](./backup-restore.md)
- [Rollback](./rollback.md)
- [Déploiement](./deployment.md)
- [Limitations connues](./known-limitations.md)
- [Checklist GO / NO-GO](../avancement/v1.0.0-go-no-go.md)
