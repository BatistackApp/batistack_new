# DevNote - Module Paie
*Dernière mise à jour : 18/07/2026*

---

## ✅ Ce qui a été fait

### Base de données & Modèles
- Création des tables et modèles pour les profils de cotisation (`PayrollContributionProfile`) et les taux de cotisations associés (`PayrollContributionRate`).
- Création des tables et modèles pour les acomptes (`AdvancePayment`).
- Création des tables et modèles pour les fiches de paies (`Payslip`) et leurs lignes détaillées (`PayslipLine`).
- Ajout de la colonne `pas_rate` sur la table `employees` (Migration `2026_07_18_171851_add_pas_rate_to_employees_table`).

### Enums (`app/Enums/Paie/`)
- `PayslipStatus` : `draft` / `validated` / `paid`
- `AdvancePaymentStatus` : `pending` / `approved` / `paid` / `deducted`
- `AdvancePaymentType` : `classic` / `grand_deplacement`
- `ContributionBaseFormula` : `gross_salary` / `csg_base` / `oppbtp_base`
- Tous les Enums implémentent `\Filament\Support\Contracts\HasLabel` pour une intégration native dans les composants Filament (Select, Badge).
- Tous les modèles (`Payslip`, `AdvancePayment`, `PayrollContributionRate`) castent leurs colonnes statut/type vers les Enums correspondants.

### Logique de calcul (`PayrollCalculationService`)
- Calcul dynamique du salaire brut, des parts salariales et patronales.
- Calcul exact de la base CSG (avec réintégration de la prévoyance et mutuelle) et gestion de la CSG déductible vs non-déductible.
- Calcul du Prélèvement à la Source (PAS) depuis le champ `pas_rate` de la fiche employé.
- Déduction et rattachement automatiques des acomptes validés (`paid`).
- Utilisation des Enums dans toutes les comparaisons de statuts (fini les strings hardcodées).
- Initialisation des champs `net_social`, `taxable_net`, `net_payable`, `net_paid`, `employer_cost` à 0 lors du premier `save()` pour éviter l'erreur SQL `NOT NULL`.

### Génération PDF (`PayslipPdfService`)
- Intégration de la vue `pdf.payslip` pour la modélisation visuelle du bulletin.
- Génération des documents via `Browsershot` (via le `DocumentService` global du projet).
- **[FIXÉ]** Le `pdf_path` enregistré en base est maintenant un **chemin relatif** (`documents/payslips/...`) et non plus un chemin absolu Windows, ce qui corrigeait l'erreur 403 lors du téléchargement.

### Vue PDF (`resources/views/pdf/payslip.blade.php`)
- **[NOUVEAU]** Vue entièrement réécrite pour respecter la **norme française** du bulletin de salaire :
  - En-tête : Entreprise à gauche, bloc salarié à droite (Matricule, N° SS, Emploi, Ancienneté, Convention collective).
  - **[NOUVEAU]** Données de l'entreprise dynamiques, sourcées depuis le modèle `Company`.
  - **[NOUVEAU]** Données du salarié dynamiques, basées sur son contrat actif (`$employee->currentContract`) pour l'ancienneté, la qualification et la convention collective.
  - Tableau principal à 6 colonnes : Éléments de paie | Base | Taux | À déduire | À payer | Charges patronales.
  - Lignes groupées par catégorie avec bandeau bleu foncé.
  - Bloc de synthèse : Réintégration fiscale, Montant net social, Net à payer avant IR, PAS, Net payé, Acomptes.
  - **[NOUVEAU]** Deux tableaux de cumuls séparés : **Cumuls Mensuels** et **Cumuls Annuels**.
  - Encadré final : Net payé + mode et date de virement.
  - Mention légale obligatoire (conservation sans limitation de durée).

### Interface Utilisateur (Filament)
- Création du `PaiePanelProvider` dédié à la paie.
- **[NOUVEAU]** Traduction complète en français des 3 ressources : labels, titres de navigation, groupes, options des formulaires et en-têtes de tableaux.
- **[NOUVEAU]** Widgets d'accueil : 
  - `PaieStatsOverview` : Chiffres clés (masse salariale, net payé, etc.).
  - `EvolutionMasseSalarialeChart` : Graphique d'évolution sur 12 mois.
  - `AdvancePendingTable` : Liste des acomptes en attente de validation.
- **Fiches de Paie (`PayslipResource`)** :
  - **[FIXÉ]** Signature `form(Schema $schema)` correcte (Filament 5).
  - **[NOUVEAU]** Formulaire intelligent : la sélection de l'employé **pré-remplit automatiquement** les heures de base et le taux horaire depuis le **contrat actif**.
  - **[NOUVEAU]** Action "Générer en masse" : Permet de générer les bulletins de tous les employés actifs pour un mois donné via un Job asynchrone (`GenerateMassPayslipsJob`).
  - **[NOUVEAU]** Infolist (`ViewPayslip`) : Affichage ultra détaillé du bulletin (Info, Grille de cotisations, Synthèse Brut/Net).
  - Action "Générer PDF" et "Télécharger PDF" dans le tableau.
  - **[FIXÉ]** `handleRecordCreation` surchargé dans `CreatePayslip` pour déléguer au `PayrollCalculationService`.
- **Acomptes (`AdvancePaymentResource`)** : Formulaire et tableau traduits, Enums utilisés.
- **Profils de Cotisation (`PayrollContributionProfileResource` + `RatesRelationManager`)** :
  - **[NOUVEAU]** `RatesRelationManager` enrichi avec toutes les colonnes (Taux salarial, Patronal, Base, Déductible).
  - Enum `ContributionBaseFormula` utilisé dans le Select du formulaire et le Badge du tableau.

### Module RH (Modifications liées)
- **[NOUVEAU]** Onglet **"Paie"** ajouté au formulaire employé (`EmployeeForm.php`) avec le champ `Taux PAS (%)` et un texte d'aide explicatif.
- Champ `pas_rate` ajouté au `$fillable` et `$casts` du modèle `Employee`.
- **[NOUVEAU]** Formulaire de Contrat (`ContractsRelationManager`) mis à jour avec le champ `payroll_contribution_profile_id` obligatoire (Select).
- Modèle `Contract` : Ajout du champ `payroll_contribution_profile_id` et de la relation `payrollContributionProfile()`.
- Migration effectuée pour lier la table `contracts` à `payroll_contribution_profiles`.

### Seeder (`PayrollContributionProfileSeeder`)
- **[NOUVEAU]** Seeder complet pour le profil **"Bâtiment (ETAM)"** avec 15 lignes de cotisations issues d'un vrai bulletin BTP :
  - Santé (SS + Prévoyance + Mutuelle)
  - Accidents du travail (7.39%)
  - Retraite (Plafonnée, Déplafonnée, Complémentaire T1)
  - Famille (5.25%) / Assurance Chômage (4.25%)
  - Congés Payés (20.70%) et OPP BTP (base OPPBTP)
  - Autres contributions employeur
  - CSG déductible (6.80%) et CSG/CRDS non déductible (2.90%)

### 🔗 Liaison automatisée avec le module RH
- **Heures Supplémentaires** : Le moteur de paie lit automatiquement les pointages approuvés (`TimeEntry`) du mois. Si la somme des heures dépasse la `base_hours` du bulletin, la différence est traitée en heures supplémentaires avec majoration de 25% et s'ajoute au salaire brut.
- **Primes de Panier (Automatique)** : Le profil de cotisations (`PayrollContributionProfile`) définit le montant du panier (ex: 11.20€). Le moteur compte les jours travaillés du mois (via `TimeEntry`), retire les jours `is_grand_deplacement` et multiplie par ce montant forfaitaire. Le résultat est ajouté en "Net" non soumis.
- **Grands Déplacements** : Les indemnités de grand déplacement (`gd_allowance_amount`) pointées sont récupérées et ajoutées automatiquement au **Net à payer** (non soumis à cotisations). (Remplace les paniers pour les jours concernés).
- **Primes Ponctuelles (Manuel)** : Un tableau ("Repeater") est présent dans le formulaire de création/modification du bulletin pour saisir des primes exceptionnelles (ex: Prime de bilan). L'utilisateur peut choisir si la prime est soumise à cotisations (Brut) ou non (Net).
- **Pointage au dépôt (Atelier)** : Ajout d'une case `is_workshop` sur les pointages RH. Les heures d'atelier donnent droit au panier, contrairement aux grands déplacements.

### 💰 Réintégration Fiscale Dynamique
- Ajout du booléen `is_fiscally_reintegrated` sur les taux de cotisation (`PayrollContributionRate`).
- Les parts patronales de la Mutuelle et de la Prévoyance (Incapacité/Décès) sont configurées à `true`.
- Lors du calcul, le `PayrollCalculationService` additionne automatiquement la part patronale de ces lignes pour l'intégrer à la base de calcul de la **CSG/CRDS** et l'ajouter au **Net Imposable**. (Fini les valeurs hardcodées !).

### Clôture et Verrouillage de la Paie
- Création du `PayslipLockService` pour orchestrer la validation d'un bulletin.
- Lors de la clôture :
  - Génération d'un **PDF définitif**.
  - Passage du bulletin au statut `validated`.
  - Verrouillage automatique des pointages (`TimeEntry`) associés à la période (`status` passe à `locked`).
  - Passage des acomptes (`AdvancePayment`) associés au statut `deducted`.
- Interface Filament :
  - Action "Clôturer" (unitaire et en masse).
  - Blocage de la modification et suppression des bulletins clôturés (`canEdit`, `canDelete`).

### Export Virement SEPA
- Ajout des champs `iban` et `bic` sur les modèles `Company` et `Employee` via migration.
- Intégration des champs bancaires dans `ManageCompany` et `EmployeeForm`.
- Création du `SepaExportService` utilisant `digitick/sepa-xml` pour générer le fichier normé `pain.001.001.03`.
- Ajout d'une action de masse "Générer fichier SEPA" dans `PayslipResource` (uniquement sur les bulletins clôturés avec montant > 0).

### Espace Salarié & Distribution
- Création du `PayslipResource` dédié dans le panel `/salarie` (lecture seule, réservé aux bulletins du salarié connecté).
- Seuls les bulletins au statut `VALIDATED` ou `PAID` y sont visibles.
- Ajout de l'action de masse "Publier & Notifier" dans le panel RH pour déclencher l'envoi.
- Implémentation du Job asynchrone `DistributePayslipJob` qui envoie une notification interne (Push) et un email sécurisé au salarié (contenant un lien vers l'espace plutôt que le PDF en pièce jointe).

---

## 🔲 Ce qu'il reste à faire

- **Export Comptable (OD de paie)** : Générer le fichier d'écritures comptables mensuel.
- **DADS / DSN** : Préparer la structure des données pour l'export DSN.
- **Cumuls annuels réels** : Actuellement une simple multiplication ×N mois, à remplacer par un calcul sur les vrais bulletins de l'année.

---

## 💡 Idées d'implémentation ou d'amélioration future

- **Versionnement des Taux de Cotisation** : Ajouter des dates de validité (`valid_from`, `valid_to`) sur les `PayrollContributionRate` pour conserver l'historique des taux sans dupliquer les profils.
- **Gestion des congés payés et absences** : Interfacer avec le module RH pour déduire ou indemniser automatiquement les jours d'absence sur le bulletin.
- **Heures Supplémentaires / Majorations** : Intégrer les données du module pointage chantier (heures sup, heures de nuit, primes grand déplacement).
- **Simulation de paie** : Outil Brut → Net ou Net → Brut pour prévoir le coût d'une embauche ou d'une augmentation.
- **Multi-conventions collectives** : Le système supporte déjà plusieurs profils de cotisations (Bâtiment Ouvriers, ETAM, Cadres) — il suffit de les créer.
