# Politique de gel fonctionnel 1.0.0

> Cette politique s'applique à la branche `release/1.0.0-rc.1` et aux corrections destinées à la release candidate.

## Objectif

Le périmètre fonctionnel de BATISTACK 1.0.0 est gelé afin de prouver la stabilité, la reproductibilité et la sécurité du produit avant la release finale.

## Changements autorisés

Les pull requests vers la branche RC sont limitées aux catégories suivantes :

- correction d'un bug bloquant ou d'une régression ;
- correction de sécurité ou de permission ;
- correction de migration ou de compatibilité ;
- correction du build, de la CI ou du déploiement ;
- tests nécessaires à la validation d'un comportement existant ;
- documentation et procédures de release.

## Changements interdits

Les changements suivants sont reportés après la 1.0.0 :

- nouvelles fonctionnalités ;
- nouveaux modules ou workflows métier ;
- refonte d'architecture non nécessaire à une correction ;
- modification UX sans lien avec une régression ;
- nouvelle intégration externe non indispensable à la release ;
- mise à jour de dépendance sans justification de sécurité ou de compatibilité.

Les fonctionnalités reportées sont suivies dans la roadmap 1.x et ne doivent pas être ajoutées à la branche RC sous prétexte de finalisation.

## Processus de pull request

Chaque PR vers `release/1.0.0-rc.1` doit :

1. indiquer la catégorie du changement et le problème traité ;
2. référencer une issue ou un critère de release ;
3. fournir les tests ou la preuve de validation appropriés ;
4. passer les contrôles CI, sécurité et Codecov ;
5. obtenir au moins une review humaine ;
6. être fusionnée uniquement après résolution des conversations.

Une PR qui introduit une fonctionnalité doit être refusée ou transférée vers la roadmap 1.1.x.

## Vérification avant merge

La review doit notamment confirmer :

- absence de changement fonctionnel non justifié ;
- absence de migration destructive non documentée ;
- absence de régression sur les permissions ;
- couverture des nouveaux chemins de code ;
- mise à jour de la documentation si le comportement change.

## Sortie du gel

Le gel prend fin uniquement après :

- publication de `v1.0.0` ;
- ou décision explicite de l'équipe de reprendre le développement fonctionnel.

Les nouvelles fonctionnalités reprennent alors sur `develop` ou une branche dédiée, jamais directement sur la branche RC.
