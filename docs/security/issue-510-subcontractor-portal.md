# Sécurité du portail sous-traitant — Issue #510

## Objectif

Documenter les contrôles d'accès couverts par les changements de l'issue #510. Cette page décrit le comportement attendu et les vérifications d'exploitation ; elle ne remplace pas les tests automatisés ni la revue de code.

## Accès aux factures et paiement Stripe

- Le portail doit associer le compte connecté à un tiers de type **sous-traitant**.
- Avant de créer une session Stripe, le serveur vérifie que la facture appartient au tiers associé au compte connecté.
- Seules les factures dans les statuts autorisés par le contrôleur de paiement peuvent être payées.
- Le paiement du portail porte sur le **montant restant dû** ; une facture dont le solde est nul ou négatif est refusée.
- Le navigateur ne constitue pas une preuve de paiement. Le statut de paiement est mis à jour à partir des événements Stripe reçus par le webhook, dont la signature est vérifiée.
- La logique de paiement doit rester idempotente : la réception répétée d'un événement ne doit pas créer un second paiement pour la même référence Stripe.

## Documents privés

Les documents du portail sous-traitant ne doivent pas être exposés uniquement parce qu'une URL ou un identifiant est connu.

- Les routes de documents nécessitent une session authentifiée et le middleware du portail sous-traitant.
- Le contrôleur vérifie que le document ou la situation appartient au sous-traitant associé au compte courant avant de lire le fichier.
- Les fichiers sont diffusés depuis leur disque de stockage, avec des en-têtes de réponse privés et sans cache.
- Les aperçus administratifs suivent une route distincte du portail sous-traitant.

### Migration des fichiers déjà présents sur un disque public

La commande Artisan `subcontractors:secure-documents` sert à migrer les documents identifiés vers le disque privé local.

1. Commencer par une simulation, sans modifier les fichiers :

   ```bash
   php artisan subcontractors:secure-documents --dry-run
   ```

2. Examiner la sortie et vérifier que les chemins et le périmètre des documents sont corrects.
3. Avant l'exécution réelle, sauvegarder les fichiers et la base de données, puis confirmer que le disque `local` est configuré et dispose de suffisamment d'espace.
4. Exécuter la migration :

   ```bash
   php artisan subcontractors:secure-documents
   ```

5. Vérifier le résumé final : nombre de documents migrés, nombre de factures/médias migrés et nombre d'erreurs. Toute erreur doit être investiguée avant de considérer l'opération terminée.
6. Vérifier dans l'application que les utilisateurs autorisés peuvent consulter/télécharger les documents et qu'un autre sous-traitant ne peut pas y accéder.

La commande refuse notamment de remplacer un fichier cible déjà présent. Ne relancer pas aveuglément une migration après des erreurs : analyser d'abord les messages et l'état des fichiers.

## Intégrité du rattachement compte / entreprise

La migration `2026_10_10_000001_enforce_single_company_per_user_contacts.php` vise à garantir qu'un compte utilisateur soit rattaché à une seule entreprise.

Avant de la déployer :

- sauvegarder la base de données ;
- rechercher et résoudre les comptes déjà liés à plusieurs entreprises ;
- ne pas supprimer ou rattacher automatiquement des contacts conflictuels sans décision métier explicite ;
- lancer les migrations sur un environnement de préproduction et contrôler les données après exécution.

La migration échoue volontairement si elle détecte des utilisateurs rattachés à plusieurs entreprises, afin de ne pas choisir arbitrairement une entreprise.

## Tests de régression

Les tests ciblés à exécuter avant fusion sont :

```bash
php artisan test tests/Feature/Api/Issue510AuthorizationRegressionTest.php --no-coverage
php artisan test tests/Feature/Filament/ChantierAssignmentsResourceAuthorizationTest.php --no-coverage
php artisan test tests/Feature/Filament/ConsultationResourceAuthorizationTest.php --no-coverage
php artisan test tests/Feature/Filament/SubcontractorAssignedTaskAuthorizationTest.php --no-coverage
php artisan test tests/Feature/Filament/SubcontractorAssignedTaskReadOnlyTest.php --no-coverage
php artisan test tests/Feature/Filament/SubcontractorInvoiceResourceAuthorizationTest.php --no-coverage
php artisan test tests/Feature/Filament/SubcontractorPaymentTrackingInvoicesTest.php --no-coverage
php artisan test tests/Feature/Filament/SubcontractorPrivateDocumentAccessTest.php --no-coverage
php artisan test tests/Feature/Modules/Core/GeneratedDocumentTest.php --no-coverage
php artisan test tests/Feature/Tiers/ContactAccountCompanyIntegrityTest.php --no-coverage
```

La CI de la PR exécute également le contrôle de style et le test de régression de suivi des paiements. Vérifier les résultats de la dernière exécution sur le commit exact avant la fusion.

## Checklist de mise en production

- [ ] CI verte sur le dernier commit de la PR.
- [ ] Revue du diff complet, notamment les changements hors du flux de paiement.
- [ ] Sauvegarde base de données et documents avant migration.
- [ ] Simulation de migration des documents examinée et validée.
- [ ] Conflits de rattachement compte/entreprise résolus explicitement.
- [ ] Configuration Stripe et secret du webhook vérifiés dans l'environnement cible.
- [ ] Migration de la base exécutée selon la procédure de déploiement habituelle.
- [ ] Migration des documents exécutée et son résultat contrôlé.
- [ ] Tests manuels d'accès autorisé et de refus d'accès croisé entre sous-traitants.
