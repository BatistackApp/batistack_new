# 🔍 AUDIT DÉTAILLÉ PAR MODULE - BATISTACK ERP

**Date :** 26 juin 2026  
**Méthode :** Analyse statique du repo + comparaison avec roadmap initiale

---

## 📋 TABLE DES MATIÈRES

1. [Module Core](#1-module-core)
2. [Module Tiers](#2-module-tiers)
3. [Module Articles](#3-module-articles)
4. [Module RH & Pointage](#4-module-rh--pointage)
5. [Module Flottes](#5-module-flottes)
6. [Module Chantiers](#6-module-chantiers)
7. [Module Commerce](#7-module-commerce)
8. [Modules non démarrés](#8-modules-non-démarrés)

---

## 1. MODULE CORE

### Statut : 🟡 **80% - À COMPLÉTER (Tests)**

### Composants présents

**Models (5/5)** ✅
- `Company` - Entité société (multi-société)
- `Setting` - Configuration système
- `Signature` - Signatures électroniques
- `Unit` - Unités de mesure
- `VatRate` - Taux de TVA
- `User` (racine app)

**Services (8)** ✅ Excellent
- `CompanyService`
- `DeviceDetectorService` - Détection appareils
- `DocumentService` - Génération documents
- `GoogleMapsService` - Géocodage
- `SettingService`
- `SignatureService`
- `SirenService` - Validation SIREN/SIRET français
- `VatService` - Calculs TVA

**Enums (3)** ✅
- `SignatureStatus`
- `SignatureType`
- `UnitType`

**Observers (4)** ✅
- `CompanyObserver`
- `SettingObserver`
- `UnitObserver`
- `VatRateObserver`

**Jobs (2)** ✅
- `CreateDocumentJob`
- `RefreshCoreCacheJob`

**Notifications (1)** ✅
- `ConfigurationChangedNotification`

**Commands (1)** ✅
- `CheckCoreSettingsCommand`

**Tests (4)** 🔴 **INSUFFISANT**
- `CoreServiceTest`
- `DeviceDetectorServiceTest`
- `SignatureServiceTest`
- `SirenServiceTest`

**Filament Resources (3)**
- `Settings`
- `Units`
- `VatRates`

### Forces 💪
- Excellents services (8 services pour 5 models)
- SirenService spécifique BTP français
- Multi-société géré
- Filament Core panel opérationnel

### Faiblesses ⚠️
- **Tests insuffisants** : 4 tests pour 8 services + 5 models = ratio faible
- Pas de tests sur Observers (4 observers, 0 tests)
- Pas de tests sur Jobs (2 jobs, 0 tests)
- VatService non testé

### Recommandations 🎯
1. **URGENT** : Créer tests Observers (CompanyObserverTest, SettingObserverTest, UnitObserverTest, VatRateObserverTest)
2. Tester les Jobs (CreateDocumentJob, RefreshCoreCacheJob)
3. Ajouter test pour VatService, GoogleMapsService, DocumentService
4. Couverture cible : 80%+ → ajouter ~10 tests

### Effort estimé pour 100% : **1-2 jours**

---

## 2. MODULE TIERS

### Statut : 🟢 **90% - QUASI COMPLET**

### Composants présents

**Models (4)** ✅
- `Address` - Adresses polymorphiques
- `Category` - Catégorisation tiers
- `Contact` - Contacts associés
- `ThirdParty` - Tiers (clients/fournisseurs/sous-traitants)

**Services (4)** ✅
- `ContactService`
- `ThirdPartyService`
- `TiersDocumentService` - Génération PDF
- `VigilanceService` - Vérification attestations vigilance (BTP)

**Enums (2)** ✅
- `AddressType`
- `ThirdPartyType`

**Observers (4)** ✅ Tous présents
- `AddressObserver`
- `CategoryObserver`
- `ContactObserver`
- `ThirdPartyObserver`

**Jobs (3)** ✅
- `GeocodeAddressJob` - Géocodage automatique
- `SynchronizeSirenJob` - Sync SIREN
- `VerifyGloabVigilanceJob` - Vérif vigilance globale

**Notifications (2)** ✅
- `VigilanceExpirationNotification`
- `WelcomeCustomerNotification`

**Commands (1)** ✅
- `VerifyVigilanceCommand`

**Tests (12)** ✅ **TRÈS BON**
- Models : Address, Contact, ThirdParty
- Observers : Address, Category, Contact, ThirdParty
- Services : Contact, ThirdParty, TiersDocument, Vigilance
- Notifications : WelcomeCustomer

**Filament Resources (1)** 🟡 **À DÉVELOPPER**
- `ThirdParties` uniquement
- Manque : Addresses, Contacts, Categories

### Forces 💪
- **Couverture tests excellente** (12 tests pour 4 models)
- Vigilance Service spécifique BTP français
- SIREN sync automatique
- Géocodage automatique des adresses

### Faiblesses ⚠️
- Frontend Filament minimal (1/4 resources)
- Pas de tests pour Jobs (3 jobs, 0 tests)

### Recommandations 🎯
1. Développer Filament Resources pour Addresses, Contacts, Categories
2. Ajouter tests Jobs (Geocode, SynchronizeSiren, VerifyVigilance)
3. RelationManagers (Contacts dans ThirdParty, Addresses dans ThirdParty)

### Effort estimé pour 100% : **2-3 jours** (Filament essentiellement)

---

## 3. MODULE ARTICLES

### Statut : 🟢 **90% - QUASI COMPLET**

### Composants présents

**Models (5)** ✅
- `Item` - Articles/produits
- `ItemComposition` - Compositions (ouvrages BTP)
- `Stock` - Stocks
- `StockMouvement` - Mouvements de stock
- `Warehouse` - Entrepôts/dépôts

**Services (3)** ✅
- `InventoryService`
- `ItemService`
- `StockService`

**Enums (3)** ✅
- `ItemType`
- `StockMouvementSource`
- `StockMouvementType`

**Observers (4)** ✅
- `BarcodeObserver` - Génération code-barres auto
- `ItemObserver`
- `StockMouvementObserver`
- `StockObserver`

**Jobs (1)** ✅
- `RecalculateWorkCostsJob` - Recalcul coût ouvrages

**Notifications (1)** ✅
- `LowStockNotification`

**Commands (1)** ✅
- `CheckLowStockCommand`

**Tests (10)** ✅ **EXCELLENT**
- Models : Item, Stock
- Observers : Barcode, Item, StockMouvement, Stock
- Services : Inventory, Item, Stock
- Jobs : RecalculateWorkCosts

**Filament Resources (2)** 🟡 **À COMPLÉTER**
- `Items` (avec Pages, RelationManagers, Schemas, Tables)
- `Warehouses` (avec Pages, RelationManagers, Schemas, Tables)
- Widgets : 2

### Forces 💪
- Couverture tests **excellente**
- Génération auto code-barres
- Ouvrages BTP via ItemComposition
- Job recalcul coûts ouvrages

### Faiblesses ⚠️
- Pas de Resource pour Stock/StockMouvement (visualisation directe)
- 1 seul Job (gestion légère)

### Recommandations 🎯
1. Ajouter Filament Resource pour `Stock` (vue inventaire global)
2. Ajouter Resource `StockMouvement` (historique mouvements)
3. Widgets de stocks (top low-stock, valeur stock, etc.)

### Effort estimé pour 100% : **1-2 jours** (Filament uniquement)

---

## 4. MODULE RH & POINTAGE

### Statut : 🟢 **85% - TRÈS AVANCÉ**

### Composants présents

**Models (7)** ✅
- `Abscence` ⚠️ (faute typo : devrait être `Absence`)
- `Contract`
- `Employee`
- `Equipement` ⚠️ (typo : devrait être `Equipment`)
- `MedicalVisit`
- `Qualification`
- `TimeEntry`

**Services (6)** ✅
- `ComplianceService` - Conformité (permis, médical, etc.)
- `LeaveBalanceService` - Soldes congés
- `PayrollVariableService` - Variables paie
- `RHDocumentService` - Documents RH
- `TimeEntryService` - Pointage
- `TimeSignatureService` - Signatures pointages

**Enums (12)** ✅ Riche
- `AbsenceType`, `CacesSymbol`, `CertificationSymbol`, `ContractType`
- `ElectricalCertification`, `EquipementType`, `MedicalAptitude`
- `MedicalVisiteType`, `QualificationType`, `SafetyAidSymbol`
- `TimeEntryStatus`, `TimeEntryType`

**Observers (7)** ✅ Tous présents
- AbscenceObserver, ContractObserver, EmployeeObserver
- EquipementObserver, MedicalVisitObserver, QualificationObserver, TimeEntryObserver

**Jobs (6)** ✅
- `CheckEquipementMaintenanceJob`
- `RecalculateEmployeeHoursJob`
- `ScanExpiringMedicalVisitsJob`
- `ScanExpiringQualificationsJob`
- `SendAbsenceRemindersJob`
- `TrialPeriodAlerterJob`

**Notifications (8)** ✅ Complet
- AbsencePending, EquipementExpiring, EquipementMaintenance
- MedicalVisitReminder, QualificationExpiring, TimeEntryStatus
- TrialPeriodEnding, WelcomeEmployee

**Commands (7)** ✅
- CheckEquipement, CheckMedicalVisit, CheckQualifications
- CheckTrialPeriods, CleanupObsoleteData, GeneratePayroll, SyncEmployeeHours

**Tests (13)** ✅ **BON**
- Models : Absence, Contract, Employee, Equipement, MedicalVisit, Qualification, TimeEntry
- Services : Compliance, LeaveBalance, PayrollVariable, RHDocument, TimeEntry, TimeSignature

**Filament Resources (2)** 🟡 **À COMPLÉTER**
- `Employees`
- `TimeEntries`
- Widgets : 5

**Panels dédiés** : 2 panels
- `RHPanelProvider` (admin RH)
- `EmployeePanelProvider` (portail employé)

### Forces 💪
- Module très riche (12 enums = grande couverture métier)
- 6 jobs d'automatisation
- 8 notifications
- 7 commandes CLI
- Portail employé dédié

### Faiblesses ⚠️
- **Typos** dans les noms : `Abscence` (Absence) et `Equipement` (Equipment) - dette technique
- Pas de tests sur Observers (7 observers, 0 test)
- Pas de tests sur Jobs (6 jobs, 0 test)
- Filament minimal (2/7 resources)

### Recommandations 🎯
1. **Refactoring des noms** : Abscence → Absence, Equipement → Equipment (migration nécessaire)
2. Tests Observers (~7 fichiers)
3. Tests Jobs (~6 fichiers)
4. Filament Resources manquantes :
   - Contracts
   - Qualifications
   - MedicalVisits
   - Equipements
   - Absences
5. Portail Employee à enrichir

### Effort estimé pour 100% : **4-5 jours**

---

## 5. MODULE FLOTTES

### Statut : 🟢 **95% - MODULE PHARE**

### Composants présents

**Models (9)** ✅
- `FleetExpense`
- `FuelTransaction`
- `TrafficFine`
- `Vehicle`
- `VehicleAssignment`
- `VehicleConditionReport`
- `VehicleContract`
- `VehicleInventory`
- `VehicleMaintenance`

**Services (8)** ✅
- `FleetCostService` - TCO calculations
- `FleetDocumentService` - PDFs
- `FleetExpenseService` - Frais véhicules
- `TrafficFineService` - Amendes
- `VehicleAlertService` - Alertes
- `VehicleAssignmentService` - Affectations
- `VehicleConditionService` - États des lieux
- `VehicleFuelService` - Carburant + anti-fraude

**Enums (6)** ✅
- AssignmentStatus, ConditionReportType, FineStatus
- FleetExpenseType, VehicleStatus, VehicleType

**Observers (5)** ✅
- TrafficFineObserver, VehicleAssignmentObserver
- VehicleContractObserver, VehicleMaintenanceObserver, VehicleObserver

**Jobs (9)** ✅ Excellent
- AnalyzeFuelConsumptionTrends
- CheckExpiringContracts
- CheckVehicleMaintenanceMilestones
- DetectOverdueAssignments
- GenerateFleetReports
- ProcessExternalFuelCardImport
- RecalculateVehicleTco
- ScanExpiringFines
- SyncVehicleStatus

**Notifications (10)** ✅ Très complet
- ContractExpiring, FleetExpenseAnomaly, FuelAnomalyAlert
- MaintenanceScheduled, MilestoneMaintenance, OverdueAssignment
- TrafficFineReceived, TrafficFineReminder
- VehicleAssignmentStarting, VulPollutionControlAlert

**Commands (8)** ✅
- CleanupOldData, ExportFleetData, FixConsumptionData
- FleetReport, FleetSupervisor, RemindTomorrowAssignments
- SeedFleet, VehicleStatusCheck

**Tests (24)** ✅ **MEILLEUR DU PROJET**
- Models : 9 tests (tous models)
- Observers : 5 tests
- Services : 8 tests (Cost, Document, Expense, TrafficFine, Alert, Assignment, Condition, Fuel)
- Integration : 2 tests (ExpenseAudit, VehicleLifecycle)

**Filament Resources (3)** 🟡 **À COMPLÉTER**
- `Vehicles`
- `VehicleAssignments`
- `TrafficFines`
- Widgets : 7 (le plus dans le projet)

### Forces 💪
- **Module modèle pour qualité**
- 24 tests (record du projet)
- Anti-fraude avancé (carburant, dépenses)
- Compliance ZFE/Crit'Air
- TCO calculé et caché
- Jobs très matures

### Faiblesses ⚠️
- Frontend Filament : 3/9 models (manque VehicleMaintenance, VehicleContract, FleetExpense, FuelTransaction, VehicleConditionReport, VehicleInventory)
- Tests Jobs absents (9 jobs)
- Tests Commands absents (8 commands)

### Recommandations 🎯
1. **Phase 2 Tests** (déjà planifiée) :
   - Tests pour les 9 Jobs
   - Tests pour les 2 Commands principales
2. Filament Resources manquantes :
   - VehicleMaintenance
   - VehicleContract (alertes contrats)
   - FleetExpense
   - FuelTransaction
   - VehicleConditionReport (état des lieux)

### Effort estimé pour 100% : **3-4 jours** (Phase 2 tests + Filament)

---

## 6. MODULE CHANTIERS

### Statut : 🟢 **75% - BIEN AVANCÉ**

### Composants présents

**Models (5)** ✅
- `Chantier`
- `ChantierLog` - Journaux de chantier
- `ChantierPhase` - Phases
- `ChantierTask` - Tâches
- `DoeDocument` - DOE (Documents Ouvrages Exécutés)

**Services (6)** ✅ Bonne couverture
- `ChantierAnalyticService` - Analytics
- `ChantierComplianceService` - Conformité chantier
- `ChantierDocumentService` - Documents
- `ChantierLogService` - Journaux
- `ChantierWorkflowService` - Workflow chantier
- `DoeDocumentService` - DOE management

**Enums (2)** ✅
- `ChantierStatus`
- `DoeDocumentCategory`

**Observers (3)** ✅
- ChantierLogObserver, ChantierObserver, ChantierTaskObserver

**Jobs (6)** ✅ Excellent
- CompileDoe (compilation DOE en fin de chantier)
- GenerateChantierDocument
- GeocodeChantierAddress
- InitializeChantierPhases
- ProcessChantierIncident
- RecalculateChantierProgress

**Notifications (5)** ✅
- ChantierBudgetAlert
- ChantierIncident
- ChantierMissingLog
- ChantierOverdue
- ChantierStartReminder

**Commands (5)** ✅
- AlertMissingLogs, CheckChantierCompliance
- CheckLateChantiers, RemindUpcomingStarts, SyncChantierMetrics

**Tests (6)** 🔴 **INSUFFISANT vu la taille**
- Services : 6 tests (Analytic, Compliance, Document, Log, Workflow, DoeDocument)
- **Aucun test Models, Observers, Jobs**

**Filament Resources (2)** ✅
- `Chantiers` (avec Pages, RelationManagers, Schemas, Tables, Widgets dédiés)
- `ChantierLogs`
- Widgets : 6

### Forces 💪
- 6 services bien structurés
- 6 jobs d'automatisation
- DOE management (spécifique BTP)
- Filament avec widgets dédiés
- Géocodage auto adresses

### Faiblesses ⚠️
- **Tests largement insuffisants** : 6 tests pour 5 models + 6 services + 3 observers + 6 jobs
- Manque tests sur Models (5)
- Manque tests sur Observers (3)
- Manque tests sur Jobs (6)
- Frontend : ChantierPhases, ChantierTasks, DoeDocuments pas en Filament

### Recommandations 🎯
1. **URGENT** : Ajouter tests Models (5 fichiers)
2. Tests Observers (3 fichiers)
3. Tests Jobs (6 fichiers - notamment CompileDoe critique)
4. Filament Resources manquantes :
   - ChantierPhases
   - ChantierTasks
   - DoeDocuments

### Effort estimé pour 100% : **5-6 jours** (tests + Filament)

---

## 7. MODULE COMMERCE

### Statut : 🟢 **80% - MODULE LE PLUS GROS**

### Composants présents

**Models (23) 🔥 MASSIF**
- **Customer** (Vente) :
  - `CustomerQuote` + `CustomerQuoteItem`
  - `CustomerOrder` + `CustomerOrderItem`
  - `CustomerDeliveryNote` + `CustomerDeliveryNoteItem`
  - `CustomerInvoice` + `CustomerInvoiceItem`
  - `CustomerSituation` + `CustomerSituationItem`
  - `CustomerCreditNote`
- **Supplier** (Achat) :
  - `SupplierInvoice` + `SupplierInvoiceItem`
  - `SupplierCreditNote`
  - `PurchaseOrder` + `PurchaseOrderItem`
  - `PurchaseRequest` + `PurchaseRequestItem`
  - `ReceiptNote` + `ReceiptNoteItem`
- **Paiements** :
  - `Payment`
  - `PaymentAllocation` (allocation polymorphique)
- **Sous-traitance** :
  - `SubcontractorSituation`

**Services (13)** ✅ Très riche
- `CommerceAnalyticService`
- `CommerceDocumentationService`
- `CustomerOrderService`
- `DeliveryNoteService`
- `DuePaymentService` - Échéances paiements
- `InvoiceLegalizationService` - Légalisation factures (DGFiP)
- `PaymentRecordingService`
- `PaymentService`
- `PurchaseService`
- `QuoteService`
- `RetentionGuaranteeService` - Retenues de garantie BTP
- `SituationService` - Situations de travaux (BTP)
- `SupplierInvoiceAuditService` - Audit factures fournisseurs

**Enums (8)** ✅
- DeliveryStatus, InvoiceStatus, InvoiceType, OrderStatus
- PaymentMethod, PaymentStatus, PaymentType, QuoteStatus

**Observers (5)** ✅
- CustomerDeliveryNoteObserver, CustomerInvoiceObserver
- CustomerOrderObserver, CustomerQuoteObserver, SupplierInvoiceObserver

**Jobs (4)** ✅
- CheckExpiredQuotes
- CheckOverdueInvoices
- GenerateDocument
- SendCustomerInvoiceEmail

**Notifications (11)** ✅ Complet
- InvoiceGenerated, InvoicePaid, InvoiceSendingFailed
- OrderShipped, PaymentReminder
- QuoteAccepted, QuoteExpired, QuoteRejected, QuoteSent
- SupplierInvoiceAuditFailed, SupplierInvoiceReadyForPayment

**Commands (1)** 🟡 **INSUFFISANT**
- `CommerceOrchestratorCommand`

**Tests (14)** 🟡 **Proportionnellement faibles**
- Services : Analytics, Documentation, CustomerOrder, DeliveryNote, DuePayment, InvoiceLegalization, PaymentRecording, Payment, Purchase, Quote, RetentionGuarantee, Situation, SupplierInvoiceAudit
- Jobs : CommerceJobTest
- **Aucun test Models (23 models)**
- **Aucun test Observers (5)**

**Filament Resources (6)** ✅ Bon avancement
- CustomerDeliveryNotes, CustomerInvoices, CustomerOrders
- CustomerQuotes, Payments, SupplierInvoices

### Forces 💪
- **Module le plus complet** en services métier
- Spécificités BTP : Situations travaux, Retenue garantie
- Audit fournisseurs (anti-fraude)
- Légalisation factures (DGFiP)
- Allocation paiements polymorphique
- Sous-traitance gérée

### Faiblesses ⚠️
- **23 models, AUCUN test de model** ⚠️
- Aucun test sur 5 Observers
- 1 seule Command (vs 8 dans Flottes)
- Manque commands : recurring invoices, payment reminders CLI, etc.
- Filament : 6 resources sur 23 models = beaucoup à faire
- Pas de notifications sur Subcontractor

### Recommandations 🎯
1. **URGENT** : Tests Models (au moins les principaux : CustomerInvoice, Payment, PaymentAllocation, CustomerQuote, SupplierInvoice)
2. **URGENT** : Tests Observers (5)
3. Ajouter Commands :
   - `commerce:check-overdue-invoices`
   - `commerce:send-payment-reminders`
   - `commerce:generate-recurring-invoices`
4. Filament Resources manquantes :
   - CustomerCreditNote
   - SupplierCreditNote
   - PurchaseOrder + Items
   - PurchaseRequest + Items
   - ReceiptNote
   - CustomerSituation (BTP critique)
   - SubcontractorSituation
5. Préparer **facturation électronique 2026** (PDP/PPF)

### Effort estimé pour 100% : **7-10 jours** (gros module)

---

## 8. MODULES NON DÉMARRÉS

### ⏳ Modules manquants (8)

#### 8.1 Module Paie
- **Dépend de** : RH ✅ (prêt)
- **Effort** : 5-6 jours
- **Priorité** : ⭐⭐⭐ HAUTE (suite logique RH)
- **Contenu attendu** :
  - PayrollRun, PayslipLine, Deduction
  - Cotisations sociales (URSSAF, retraite, etc.)
  - Bulletins de paie (PDF)
  - DSN (Déclaration Sociale Nominative)

#### 8.2 Module Banque
- **Dépend de** : Commerce 🟢 (prêt)
- **Effort** : 4-5 jours
- **Priorité** : ⭐⭐⭐ HAUTE
- **Contenu attendu** :
  - BankAccount, BankTransaction
  - Rapprochement bancaire
  - Imports CSV/CAMT
  - Lien avec Payment

#### 8.3 Module Notes de Frais
- **Dépend de** : RH ✅, Tiers ✅
- **Effort** : 3-4 jours
- **Priorité** : ⭐⭐ MOYENNE
- **Contenu attendu** :
  - ExpenseReport, ExpenseLine
  - Workflow validation
  - OCR tickets

#### 8.4 Module Locations
- **Dépend de** : Articles ✅, Commerce 🟢
- **Effort** : 2-3 jours
- **Priorité** : ⭐ BASSE
- **Contenu attendu** :
  - RentalContract, RentalLine
  - Factures périodiques automatiques

#### 8.5 Module Immobilisations
- **Dépend de** : Core ✅, Commerce 🟢
- **Effort** : 3-4 jours
- **Priorité** : ⭐⭐ MOYENNE (compta)
- **Contenu attendu** :
  - Asset, Depreciation, Disposal
  - VNC, dotations

#### 8.6 Module GPAO
- **Dépend de** : Articles ✅, Chantiers 🟢
- **Effort** : 5-6 jours
- **Priorité** : ⭐⭐ MOYENNE
- **Contenu attendu** :
  - ManufacturingOrder, MrpSuggestion
  - Planning, achats automatiques

#### 8.7 Module Interventions
- **Dépend de** : Articles ✅, Chantiers 🟢, Commerce 🟢
- **Effort** : 4-5 jours
- **Priorité** : ⭐⭐ MOYENNE
- **Contenu attendu** :
  - Intervention, WorkOrder
  - Forfait / Régie
  - Mobile (TerrainPanel à compléter)

#### 8.8 Module 3D Visions
- **Dépend de** : Core ✅, Chantiers 🟢
- **Effort** : 3-4 jours
- **Priorité** : ⭐ BASSE (innovation)
- **Contenu attendu** :
  - BIM/IFC viewer
  - Intégration plans

---

## 📊 RÉCAPITULATIF GLOBAL

### Modules terminés (90%+)
1. ✅ **Tiers** (90%)
2. ✅ **Articles** (90%)
3. ✅ **Flottes** (95%) - Module phare

### Modules très avancés (75-89%)
4. 🟢 **Core** (80% - tests à compléter)
5. 🟢 **RH** (85%)
6. 🟢 **Chantiers** (75% - tests à compléter)
7. 🟢 **Commerce** (80% - tests + Filament)

### Modules non démarrés (0%)
8. ⏳ Paie
9. ⏳ Banque
10. ⏳ Notes de Frais
11. ⏳ Locations
12. ⏳ Immobilisations
13. ⏳ GPAO
14. ⏳ Interventions
15. ⏳ 3D Visions

### Effort total pour finition

| Phase | Modules | Effort |
|-------|---------|--------|
| **Phase A** : Compléter existant | Core, RH, Chantiers, Commerce (tests + Filament) | 17-21 jours |
| **Phase B** : Modules manquants haute priorité | Paie, Banque, Notes Frais | 12-15 jours |
| **Phase C** : Modules manquants moyenne priorité | Locations, Immobilisations, GPAO, Interventions | 14-18 jours |
| **Phase D** : Innovation | 3D Visions | 3-4 jours |
| **TOTAL** | 15 modules complets | **46-58 jours** |

**Avec 2 développeurs en parallèle :** ~25-30 jours.

---

**Fin de l'audit par module. Voir ROADMAP_DEVELOPPEMENT.md pour la planification.**
