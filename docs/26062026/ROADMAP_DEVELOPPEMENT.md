# 🗺️ ROADMAP DE DÉVELOPPEMENT - BATISTACK ERP

**Date :** 26 juin 2026  
**Horizon :** 3 mois (Juillet → Septembre 2026)  
**Objectif :** Achèvement complet de l'ERP (15 modules + frontend)

---

## 🎯 OBJECTIFS STRATÉGIQUES

### Vision MVP (fin Juillet 2026)
✅ 7 modules de base 100% complets (Core, Tiers, Articles, RH, Flottes, Chantiers, Commerce)  
✅ Frontend Filament fonctionnel sur ces 7 modules  
✅ Couverture tests > 85%  
✅ Premiers utilisateurs internes

### Vision V1.0 (fin Septembre 2026)
✅ 15 modules complets  
✅ Conformité fiscale française (facturation électronique)  
✅ Tests E2E des workflows critiques  
✅ Documentation complète  
✅ Mise en production

---

## 📅 PLANNING GLOBAL (12 semaines)

```
SEMAINE  │ PHASE      │ FOCUS PRINCIPAL                          │ LIVRABLE
─────────┼────────────┼──────────────────────────────────────────┼─────────────────
S01-S02  │ Phase 1    │ Stabilisation (tests + bugs)             │ MVP testé
S03-S04  │ Phase 2    │ Complétion Frontend (modules existants)  │ MVP visuel
S05-S06  │ Phase 3    │ Module Paie + Banque                     │ Compta basique
S07-S08  │ Phase 4    │ Modules Notes Frais + Locations          │ Gestion frais
S09-S10  │ Phase 5    │ Modules Immobilisations + GPAO           │ Industrie
S11-S12  │ Phase 6    │ Interventions + 3D Visions + Finalisation│ V1.0 complète
```

---

## 🚀 PHASE 1 : STABILISATION (Semaines 1-2)

**Durée :** 10 jours ouvrés  
**Effort :** 2 développeurs  
**Objectif :** Garantir la qualité du code existant

### Sprint 1.1 : Tests manquants (5 jours)

#### Lundi-Mardi : Tests Core (CRITIQUE)
- [ ] Tests Observers : `CompanyObserverTest`, `SettingObserverTest`, `UnitObserverTest`, `VatRateObserverTest`
- [ ] Tests Jobs : `CreateDocumentJobTest`, `RefreshCoreCacheJobTest`
- [ ] Tests Services manquants : `VatServiceTest`, `GoogleMapsServiceTest`, `DocumentServiceTest`, `CompanyServiceTest`
- **Output :** Core passe de 4 à ~15 tests

#### Mercredi : Tests Commerce Models (URGENT)
- [ ] `CustomerInvoiceModelTest`, `CustomerQuoteModelTest`, `CustomerOrderModelTest`
- [ ] `PaymentModelTest`, `PaymentAllocationModelTest`
- [ ] `SupplierInvoiceModelTest`, `PurchaseOrderModelTest`
- [ ] `CustomerSituationModelTest` (BTP critique)
- **Output :** +8 tests Commerce

#### Jeudi : Tests Commerce Observers
- [ ] `CustomerDeliveryNoteObserverTest`, `CustomerInvoiceObserverTest`
- [ ] `CustomerOrderObserverTest`, `CustomerQuoteObserverTest`
- [ ] `SupplierInvoiceObserverTest`
- **Output :** +5 tests Commerce

#### Vendredi : Tests Chantiers
- [ ] Tests Models : 5 fichiers
- [ ] Tests Observers : 3 fichiers
- [ ] Tests Jobs critiques : `CompileDoeJobTest`, `RecalculateChantierProgressJobTest`
- **Output :** +10 tests Chantiers

### Sprint 1.2 : Refactoring & Bugs (5 jours)

#### Lundi-Mardi : Refactoring noms RH (DETTE TECHNIQUE)
- [ ] Migration : `abscence_*` → `absence_*`
- [ ] Migration : `equipement_*` → `equipment_*`
- [ ] Renommer Model `Abscence` → `Absence`
- [ ] Renommer Model `Equipement` → `Equipment`
- [ ] Mise à jour de tous les fichiers (Services, Observers, Tests, Enums, Notifications, Commands)
- [ ] Tests : tous doivent passer après refactoring
- **Risque :** Élevé (impact transverse)
- **Mitigation :** Faire en branche dédiée, tests CI obligatoires

#### Mercredi : Tests RH Observers + Jobs
- [ ] 7 tests Observers RH
- [ ] 6 tests Jobs RH
- **Output :** +13 tests RH

#### Jeudi-Vendredi : Tests Flottes Phase 2
- [ ] 9 tests Jobs Flottes (déjà planifiés)
- [ ] 2 tests Commands principales
- **Output :** Flottes 100% testé

### Livrables Phase 1
- ✅ +60 tests ajoutés
- ✅ Refactoring noms RH propre
- ✅ Couverture globale > 85%
- ✅ CI vert sur tous les commits

---

## 🎨 PHASE 2 : FRONTEND FILAMENT (Semaines 3-4)

**Durée :** 10 jours ouvrés  
**Effort :** 2 développeurs (1 backend, 1 frontend)  
**Objectif :** Compléter le frontend des 7 modules existants

### Sprint 2.1 : Frontend Tiers + Articles (3 jours)

#### Tiers (1.5 jours)
- [ ] Resource `Addresses` (avec RelationManager Polymorphic)
- [ ] Resource `Contacts` (avec RelationManager ThirdParty)
- [ ] Resource `Categories` (tree view)
- [ ] Widgets : Top customers, Vigilance dashboard

#### Articles (1.5 jours)
- [ ] Resource `Stocks` (vue inventaire global)
- [ ] Resource `StockMouvements` (historique)
- [ ] Resource `ItemCompositions` (ouvrages BTP)
- [ ] Widgets : Low stock alerts, Stock value, Top moves

### Sprint 2.2 : Frontend RH (3 jours)

- [ ] Resource `Contracts` (contrats employés)
- [ ] Resource `Qualifications` (avec workflow expiration)
- [ ] Resource `MedicalVisits` (avec calendrier)
- [ ] Resource `Equipments` (post-refactoring)
- [ ] Resource `Absences` (post-refactoring + calendrier)
- [ ] Compléter `EmployeePanelProvider` (portail employé) :
  - Profil employé
  - Pointage
  - Demandes congés
  - Documents personnels

### Sprint 2.3 : Frontend Chantiers + Flottes (2 jours)

#### Chantiers (1 jour)
- [ ] Resource `ChantierPhases` (timeline)
- [ ] Resource `ChantierTasks` (Kanban + Gantt)
- [ ] Resource `DoeDocuments` (avec viewer PDF)

#### Flottes (1 jour)
- [ ] Resource `VehicleMaintenances`
- [ ] Resource `VehicleContracts` (avec alertes visuelles)
- [ ] Resource `FleetExpenses`
- [ ] Resource `FuelTransactions` (avec graphiques)
- [ ] Resource `VehicleConditionReports` (avec photos)

### Sprint 2.4 : Frontend Commerce (2 jours - GROS MORCEAU)

- [ ] Resource `CustomerCreditNotes`
- [ ] Resource `SupplierCreditNotes`
- [ ] Resource `PurchaseOrders` (avec items)
- [ ] Resource `PurchaseRequests`
- [ ] Resource `ReceiptNotes` (réception marchandise)
- [ ] Resource `CustomerSituations` (BTP - critique)
- [ ] Resource `SubcontractorSituations`
- [ ] Dashboard Commerce complet (widgets analytics)

### Livrables Phase 2
- ✅ Tous les modules existants ont leur Filament complet
- ✅ Portail employé fonctionnel
- ✅ Portail client (Customer) opérationnel
- ✅ Dashboard analytics par module

---

## 💰 PHASE 3 : MODULES COMPTABLES (Semaines 5-6)

**Durée :** 10 jours ouvrés  
**Effort :** 2 développeurs  
**Objectif :** Modules Paie + Banque (cœur compta)

### Sprint 3.1 : Module Paie (5-6 jours)

#### Backend (3 jours)
- [ ] Models : `PayrollRun`, `Payslip`, `PayslipLine`, `PayrollDeduction`
- [ ] Enums : `PayrollStatus`, `DeductionType`, `PayrollVariableCategory`
- [ ] Migrations : 4 tables principales + indexes
- [ ] Factories + Seeders
- [ ] Services :
  - `PayrollCalculationService` (calcul brut/net)
  - `PayslipGenerationService` (PDF bulletins)
  - `SocialContributionService` (URSSAF, retraite, etc.)
  - `DsnService` (Déclaration Sociale Nominative)
- [ ] Observers : `PayrollRunObserver`, `PayslipObserver`
- [ ] Jobs :
  - `RunPayrollJob` (calcul masse)
  - `GenerateDsnJob`
  - `ArchivePayslipsJob`
- [ ] Notifications : `PayslipReady`, `PayrollValidated`, `DsnSubmitted`
- [ ] Commands : `paie:run-monthly`, `paie:generate-dsn`, `paie:export-payslips`

#### Frontend (1 jour)
- [ ] Resource `PayrollRuns` (workflow validation)
- [ ] Resource `Payslips` (avec viewer PDF)
- [ ] Resource `PayrollVariables` (variables paie)
- [ ] Widgets : Masse salariale, Top variables

#### Tests (1-2 jours)
- [ ] Tests Models (4)
- [ ] Tests Services (4)
- [ ] Tests Observers (2)
- [ ] Tests Jobs (3)
- [ ] Tests Integration : workflow complet paie

### Sprint 3.2 : Module Banque (4-5 jours)

#### Backend (2-3 jours)
- [ ] Models : `BankAccount`, `BankTransaction`, `BankReconciliation`, `BankImport`
- [ ] Enums : `BankAccountType`, `TransactionType`, `ReconciliationStatus`
- [ ] Migrations : 4 tables + indexes
- [ ] Services :
  - `BankSyncService` (synchro multi-banques)
  - `BankReconciliationService` (rapprochement auto)
  - `BankImportService` (CSV, CAMT.053, OFX)
  - `BankAnalyticService`
- [ ] Observers : `BankTransactionObserver` (auto-allocation)
- [ ] Jobs : `ImportBankStatementJob`, `AutoReconcileJob`, `DetectAnomaliesJob`
- [ ] Notifications : `UnreconciledAlert`, `LowBalance`, `LargeTransaction`

#### Frontend (1 jour)
- [ ] Resource `BankAccounts`
- [ ] Resource `BankTransactions` (avec recherche avancée)
- [ ] Resource `BankReconciliations`
- [ ] Widgets : Trésorerie, Cashflow forecast

#### Tests (1 jour)
- [ ] Tests complets

### Livrables Phase 3
- ✅ Module Paie opérationnel
- ✅ Module Banque opérationnel
- ✅ DSN générable
- ✅ Rapprochement bancaire automatique

---

## 🧾 PHASE 4 : NOTES FRAIS + LOCATIONS (Semaines 7-8)

**Durée :** 10 jours ouvrés  
**Effort :** 2 développeurs

### Sprint 4.1 : Notes de Frais (3-4 jours)

#### Backend (2 jours)
- [ ] Models : `ExpenseReport`, `ExpenseLine`, `ExpenseCategory`
- [ ] Enums : `ExpenseReportStatus`, `ExpenseCategoryType`
- [ ] Services :
  - `ExpenseValidationService` (workflow validation)
  - `OcrTicketService` (OCR tickets de caisse)
  - `ExpenseReimbursementService` (lien Paie)
- [ ] Observers + Jobs (OCR async)
- [ ] Notifications validation

#### Frontend (1-2 jours)
- [ ] Resource `ExpenseReports`
- [ ] Resource mobile (photo ticket)
- [ ] Workflow validation hierarchique

### Sprint 4.2 : Module Locations (2-3 jours)

#### Backend (1-2 jours)
- [ ] Models : `RentalContract`, `RentalLine`, `RentalSchedule`
- [ ] Services : `RentalInvoiceService`, `RentalScheduleService`
- [ ] Job : `GenerateRecurringInvoicesJob`

#### Frontend (1 jour)
- [ ] Resources + workflows

### Sprint 4.3 : Refinements & Bug Fixes (3 jours)
- [ ] Buffer pour finitions
- [ ] Optimisations
- [ ] Bugs remontés par utilisateurs internes

---

## 🏭 PHASE 5 : IMMOBILISATIONS + GPAO (Semaines 9-10)

**Durée :** 10 jours ouvrés

### Sprint 5.1 : Immobilisations (3-4 jours)

#### Backend (2 jours)
- [ ] Models : `FixedAsset`, `Depreciation`, `AssetDisposal`
- [ ] Enums : `DepreciationMethod`, `AssetCategory`, `AssetStatus`
- [ ] Services :
  - `DepreciationCalculationService` (linéaire, dégressif)
  - `AssetValuationService` (VNC)
  - `AssetReportService`
- [ ] Jobs : `CalculateMonthlyDepreciationJob`

#### Frontend (1-2 jours)
- [ ] Resources + dashboard immobilisations
- [ ] État des amortissements

### Sprint 5.2 : GPAO (5-6 jours)

#### Backend (3-4 jours)
- [ ] Models : `ManufacturingOrder`, `MrpSuggestion`, `ProductionPlan`, `BillOfMaterial`
- [ ] Services :
  - `MrpService` (Material Requirements Planning)
  - `ProductionPlanningService`
  - `ProcurementSuggestionService`
  - `CapacityPlanningService`
- [ ] Jobs : `RunMrpJob`, `OptimizePlanningJob`

#### Frontend (1-2 jours)
- [ ] Resources + Gantt production
- [ ] Vues planning

---

## 🔧 PHASE 6 : INTERVENTIONS + 3D + FINALISATION (Semaines 11-12)

**Durée :** 10 jours ouvrés

### Sprint 6.1 : Interventions (4-5 jours)

#### Backend (2-3 jours)
- [ ] Models : `Intervention`, `InterventionLine`, `WorkOrder`, `ServiceContract`
- [ ] Services :
  - `InterventionPricingService` (forfait/régie)
  - `InterventionInvoicingService`
  - `RentabilityAnalysisService`

#### Frontend - TerrainPanel (1-2 jours) ⚠️ Actuellement vide
- [ ] **Compléter TerrainPanelProvider**
- [ ] Resources mobiles pour techniciens
- [ ] Signature client
- [ ] Photos avant/après
- [ ] Synchro offline

### Sprint 6.2 : 3D Visions (3-4 jours)

#### Backend
- [ ] Models : `Model3d`, `Viewer`, `Plan2d`
- [ ] Services : `BimIntegrationService`, `IfcParsingService`

#### Frontend
- [ ] Viewer 3D intégré
- [ ] Annotations sur plans
- [ ] Lien avec Chantiers

### Sprint 6.3 : Finalisation V1.0 (2-3 jours)

#### Conformité fiscale française
- [ ] Audit conformité **facturation électronique 2026 (PDP/PPF)**
- [ ] Format FactX-X
- [ ] Connecteur Chorus Pro / Plateformes
- [ ] Archivage légal 10 ans

#### Documentation
- [ ] Documentation utilisateur (par module)
- [ ] Documentation technique
- [ ] API documentation (Swagger)
- [ ] Vidéos tutorielles

#### Tests E2E
- [ ] Workflow complet : Devis → Commande → BL → Facture → Paiement
- [ ] Workflow Chantier : Création → Phases → Tâches → DOE
- [ ] Workflow RH : Embauche → Contrat → Paie → DSN
- [ ] Workflow Flottes : Achat → Affectation → Maintenance → Cession

#### Performance & Production
- [ ] Index DB optimisés
- [ ] Cache stratégies (Redis)
- [ ] Queue workers configurés
- [ ] Monitoring (logs, jobs, performances)
- [ ] Backups automatiques

---

## 📊 RÉPARTITION DE L'EFFORT PAR PROFIL

### Si 2 développeurs

**Développeur Backend (60% du temps)**
- Services métier
- Jobs & queues
- Tests backend
- Refactorings
- Optimisations DB

**Développeur Frontend (40% du temps)**
- Filament Resources
- Widgets dashboards
- Livewire components
- TailwindCSS theming
- Portails (Customer, Employee, Terrain)

### Si 3 développeurs (idéal)

**Backend Senior (30%)**
- Architecture
- Modules complexes (Paie, Banque, Commerce)
- Performance

**Backend Junior (35%)**
- Tests
- CRUDs
- Documentation

**Frontend (35%)**
- Filament panels
- UX/UI
- Mobile (TerrainPanel)

---

## 🎯 JALONS CLÉS (MILESTONES)

| Date | Milestone | Critères |
|------|-----------|----------|
| **Fin S02** | 🎯 **MVP stable** | Tests > 85%, refactor RH ok, 0 bugs bloquants |
| **Fin S04** | 🎯 **MVP visuel** | 7 modules avec frontend complet |
| **Fin S06** | 🎯 **MVP compta** | Paie + Banque opérationnels |
| **Fin S08** | 🎯 **Beta interne** | Notes Frais + Locations + utilisateurs test |
| **Fin S10** | 🎯 **Beta externe** | 12 modules complets + premiers clients |
| **Fin S12** | 🎯 **V1.0 GA** | 15 modules + conformité fiscale + prod |

---

## 🚨 RISQUES IDENTIFIÉS & MITIGATION

### Risque 1 : Refactoring noms RH (Abscence → Absence)
- **Impact :** Élevé (transverse)
- **Probabilité :** Moyenne
- **Mitigation :**
  - Branche dédiée
  - Migration DB testée sur copie prod
  - Tests CI obligatoires
  - Revert plan préparé

### Risque 2 : Conformité facturation électronique 2026
- **Impact :** Critique (légal)
- **Probabilité :** Élevée si non préparé
- **Mitigation :**
  - Audit dès Phase 1
  - Veille réglementaire active
  - Sprint dédié en Phase 6
  - Intégration PDP partenaire

### Risque 3 : Complexité module Paie
- **Impact :** Élevé
- **Probabilité :** Élevée
- **Mitigation :**
  - Expert paie en consultant
  - Phase 3 entièrement dédiée
  - Tests exhaustifs sur cas réels
  - Possibilité d'utiliser librairie externe (Silae, PayFit API)

### Risque 4 : Performance avec gros volumes
- **Impact :** Moyen
- **Probabilité :** Moyenne
- **Mitigation :**
  - Index DB dès maintenant
  - Cache stratégies
  - Tests de charge en Phase 6
  - Queue workers scalés

### Risque 5 : Adoption utilisateur (UX)
- **Impact :** Élevé
- **Probabilité :** Moyenne
- **Mitigation :**
  - Tests utilisateurs dès Phase 2
  - Feedback loops courts
  - Filament permet itération rapide
  - Formation utilisateurs intégrée

---

## 💼 BUDGET TEMPS ESTIMÉ

### Scénario 1 : 1 développeur solo
- **Durée totale :** 80-100 jours ouvrés
- **Calendrier :** ~4-5 mois
- **Coût estimé :** 60-80 K€

### Scénario 2 : 2 développeurs en parallèle (RECOMMANDÉ)
- **Durée totale :** 50-60 jours ouvrés
- **Calendrier :** ~3 mois (Juillet → Septembre 2026)
- **Coût estimé :** 80-100 K€

### Scénario 3 : 3 développeurs + Chef de projet
- **Durée totale :** 35-45 jours ouvrés
- **Calendrier :** ~2-2.5 mois
- **Coût estimé :** 120-150 K€

---

## ✅ PROCHAINES ACTIONS IMMÉDIATES

### Cette semaine (semaine du 30 juin)
1. [ ] **Valider l'audit** avec l'équipe
2. [ ] **Choisir le scénario** (1, 2 ou 3 développeurs)
3. [ ] **Créer les milestones GitHub** pour les 6 phases
4. [ ] **Démarrer Phase 1** : Tests Core (priorité absolue)

### Semaine prochaine (7 juillet)
1. [ ] Sprint 1.1 complet
2. [ ] Refactoring RH planifié (S02)
3. [ ] Setup CI plus strict (couverture obligatoire 85%)

---

## 📞 CONTACTS & RESPONSABILITÉS

À définir :
- [ ] Tech Lead
- [ ] Product Owner
- [ ] QA / Tests
- [ ] DevOps
- [ ] Expert métier BTP
- [ ] Expert comptable/paie

---

**Fin de la roadmap. Document vivant - à mettre à jour chaque fin de sprint.**

**Prochaine révision :** Fin Sprint 1.1 (5 juillet 2026)
