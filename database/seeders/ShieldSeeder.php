<?php

namespace Database\Seeders;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $tenants = '[]';
        $users = '[]';
        $userTenantPivot = '[]';
        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":["ViewAny:Setting","View:Setting","Create:Setting","Update:Setting","Delete:Setting","DeleteAny:Setting","Restore:Setting","ForceDelete:Setting","ForceDeleteAny:Setting","RestoreAny:Setting","Replicate:Setting","Reorder:Setting","ViewAny:Unit","View:Unit","Create:Unit","Update:Unit","Delete:Unit","DeleteAny:Unit","Restore:Unit","ForceDelete:Unit","ForceDeleteAny:Unit","RestoreAny:Unit","Replicate:Unit","Reorder:Unit","ViewAny:VatRate","View:VatRate","Create:VatRate","Update:VatRate","Delete:VatRate","DeleteAny:VatRate","Restore:VatRate","ForceDelete:VatRate","ForceDeleteAny:VatRate","RestoreAny:VatRate","Replicate:VatRate","Reorder:VatRate","ViewAny:Role","View:Role","Create:Role","Update:Role","Delete:Role","DeleteAny:Role","Restore:Role","ForceDelete:Role","ForceDeleteAny:Role","RestoreAny:Role","Replicate:Role","Reorder:Role","ViewAny:QueueMonitor","View:QueueMonitor","Create:QueueMonitor","Update:QueueMonitor","Delete:QueueMonitor","DeleteAny:QueueMonitor","Restore:QueueMonitor","ForceDelete:QueueMonitor","ForceDeleteAny:QueueMonitor","RestoreAny:QueueMonitor","Replicate:QueueMonitor","Reorder:QueueMonitor","ViewAny:CibtpDeclaration","View:CibtpDeclaration","Create:CibtpDeclaration","Update:CibtpDeclaration","Delete:CibtpDeclaration","DeleteAny:CibtpDeclaration","Restore:CibtpDeclaration","ForceDelete:CibtpDeclaration","ForceDeleteAny:CibtpDeclaration","RestoreAny:CibtpDeclaration","Replicate:CibtpDeclaration","Reorder:CibtpDeclaration","ViewAny:Employee","View:Employee","Create:Employee","Update:Employee","Delete:Employee","DeleteAny:Employee","Restore:Employee","ForceDelete:Employee","ForceDeleteAny:Employee","RestoreAny:Employee","Replicate:Employee","Reorder:Employee","ViewAny:ExpenseAdvance","View:ExpenseAdvance","Create:ExpenseAdvance","Update:ExpenseAdvance","Delete:ExpenseAdvance","DeleteAny:ExpenseAdvance","Restore:ExpenseAdvance","ForceDelete:ExpenseAdvance","ForceDeleteAny:ExpenseAdvance","RestoreAny:ExpenseAdvance","Replicate:ExpenseAdvance","Reorder:ExpenseAdvance","ViewAny:ExpenseReport","View:ExpenseReport","Create:ExpenseReport","Update:ExpenseReport","Delete:ExpenseReport","DeleteAny:ExpenseReport","Restore:ExpenseReport","ForceDelete:ExpenseReport","ForceDeleteAny:ExpenseReport","RestoreAny:ExpenseReport","Replicate:ExpenseReport","Reorder:ExpenseReport","ViewAny:PayrollExport","View:PayrollExport","Create:PayrollExport","Update:PayrollExport","Delete:PayrollExport","DeleteAny:PayrollExport","Restore:PayrollExport","ForceDelete:PayrollExport","ForceDeleteAny:PayrollExport","RestoreAny:PayrollExport","Replicate:PayrollExport","Reorder:PayrollExport","ViewAny:TimeEntry","View:TimeEntry","Create:TimeEntry","Update:TimeEntry","Delete:TimeEntry","DeleteAny:TimeEntry","Restore:TimeEntry","ForceDelete:TimeEntry","ForceDeleteAny:TimeEntry","RestoreAny:TimeEntry","Replicate:TimeEntry","Reorder:TimeEntry","View:CompetencyMatrix","View:ExportDNA","View:PayrollSimulator","View:ScanEquipementPage","View:OvertimeVarianceWidget","View:LegalComplianceGoalWidget","View:ContractTypeCompositionWidget","View:PendingHrActionsDetailWidget","View:PlanningCalendarWidget","View:ExpiringContractsWidget","View:ExpiringMedicalVisitsWidget","View:ExpiringQualificationsWidget","View:SubrogationTrackerWidget","View:ManageCompany","View:Inbox","View:ViewMessage","View:SentMessages","View:StarredMessages","View:Trash","View:ViewSentMessage","View:LogTable","View:LatestChantiersWidget","View:StatsOverview","View:InboxStatsWidget","View:CoreStatsOverview","View:ApiUsageLimitsWidget","View:SignatureTrendWidget","View:SystemHealthWidget","View:SystemActivityRecentItemsWidget","View:CompanyOnboardingGoalWidget","View:AppVersionWidget","ViewAny:InventoryCycle","View:InventoryCycle","Create:InventoryCycle","Update:InventoryCycle","Delete:InventoryCycle","DeleteAny:InventoryCycle","Restore:InventoryCycle","ForceDelete:InventoryCycle","ForceDeleteAny:InventoryCycle","RestoreAny:InventoryCycle","Replicate:InventoryCycle","Reorder:InventoryCycle","ViewAny:Item","View:Item","Create:Item","Update:Item","Delete:Item","DeleteAny:Item","Restore:Item","ForceDelete:Item","ForceDeleteAny:Item","RestoreAny:Item","Replicate:Item","Reorder:Item","ViewAny:Warehouse","View:Warehouse","Create:Warehouse","Update:Warehouse","Delete:Warehouse","DeleteAny:Warehouse","Restore:Warehouse","ForceDelete:Warehouse","ForceDeleteAny:Warehouse","RestoreAny:Warehouse","Replicate:Warehouse","Reorder:Warehouse","View:Dashboard","View:ArticlesStatsOverview","View:LowStockAlertWidget","View:InventoryValueVarianceWidget","View:LowStockTableWidget","View:StockMovementsChart","View:StockCompositionWidget","View:WarehouseDistributionChart","View:LatestStockMouvementsWidget","View:StockRotationTrendWidget","View:ExpectedDeliveriesWidget","ViewAny:BankAccount","View:BankAccount","Create:BankAccount","Update:BankAccount","Delete:BankAccount","DeleteAny:BankAccount","Restore:BankAccount","ForceDelete:BankAccount","ForceDeleteAny:BankAccount","RestoreAny:BankAccount","Replicate:BankAccount","Reorder:BankAccount","ViewAny:BankTransaction","View:BankTransaction","Create:BankTransaction","Update:BankTransaction","Delete:BankTransaction","DeleteAny:BankTransaction","Restore:BankTransaction","ForceDelete:BankTransaction","ForceDeleteAny:BankTransaction","RestoreAny:BankTransaction","Replicate:BankTransaction","Reorder:BankTransaction","ViewAny:TransactionCategory","View:TransactionCategory","Create:TransactionCategory","Update:TransactionCategory","Delete:TransactionCategory","DeleteAny:TransactionCategory","Restore:TransactionCategory","ForceDelete:TransactionCategory","ForceDeleteAny:TransactionCategory","RestoreAny:TransactionCategory","Replicate:TransactionCategory","Reorder:TransactionCategory","View:MonthlyClosing","View:GlobalBalanceVarianceWidget","View:UncategorizedTransactionsWidget","View:HighValueAnomaliesWidget","View:ReconciliationGoalWidget","View:BanqueStatsOverview","View:ManualPaidCustomerInvoicesWidget","View:CashFlowComparisonWidget","View:ManualPaidSupplierInvoicesWidget","View:ExpensesCompositionWidget","View:CashFlowForecastChart","View:PendingTransactionsTable","View:BankAccountsStatusList","ViewAny:ChantierLog","View:ChantierLog","Create:ChantierLog","Update:ChantierLog","Delete:ChantierLog","DeleteAny:ChantierLog","Restore:ChantierLog","ForceDelete:ChantierLog","ForceDeleteAny:ChantierLog","RestoreAny:ChantierLog","Replicate:ChantierLog","Reorder:ChantierLog","ViewAny:Chantier","View:Chantier","Create:Chantier","Update:Chantier","Delete:Chantier","DeleteAny:Chantier","Restore:Chantier","ForceDelete:Chantier","ForceDeleteAny:Chantier","RestoreAny:Chantier","Replicate:Chantier","Reorder:Chantier","ViewAny:ChecklistTemplate","View:ChecklistTemplate","Create:ChecklistTemplate","Update:ChecklistTemplate","Delete:ChecklistTemplate","DeleteAny:ChecklistTemplate","Restore:ChecklistTemplate","ForceDelete:ChecklistTemplate","ForceDeleteAny:ChecklistTemplate","RestoreAny:ChecklistTemplate","Replicate:ChecklistTemplate","Reorder:ChecklistTemplate","View:FillChecklistPage","View:ResourcePlanner","View:GlobalMarginVarianceWidget","View:ChantierPipelineFunnelWidget","View:HoursConsumptionGoalWidget","View:ChantierAlertsDetailWidget","View:GlobalSafetyHealthWidget","View:BudgetConsumptionChart","View:ChantierMapWidget","View:ActiveChantiersTable","View:LatestActivityWidget","ViewAny:CustomerCreditNote","View:CustomerCreditNote","Create:CustomerCreditNote","Update:CustomerCreditNote","Delete:CustomerCreditNote","DeleteAny:CustomerCreditNote","Restore:CustomerCreditNote","ForceDelete:CustomerCreditNote","ForceDeleteAny:CustomerCreditNote","RestoreAny:CustomerCreditNote","Replicate:CustomerCreditNote","Reorder:CustomerCreditNote","ViewAny:CustomerDeliveryNote","View:CustomerDeliveryNote","Create:CustomerDeliveryNote","Update:CustomerDeliveryNote","Delete:CustomerDeliveryNote","DeleteAny:CustomerDeliveryNote","Restore:CustomerDeliveryNote","ForceDelete:CustomerDeliveryNote","ForceDeleteAny:CustomerDeliveryNote","RestoreAny:CustomerDeliveryNote","Replicate:CustomerDeliveryNote","Reorder:CustomerDeliveryNote","ViewAny:CustomerInvoice","View:CustomerInvoice","Create:CustomerInvoice","Update:CustomerInvoice","Delete:CustomerInvoice","DeleteAny:CustomerInvoice","Restore:CustomerInvoice","ForceDelete:CustomerInvoice","ForceDeleteAny:CustomerInvoice","RestoreAny:CustomerInvoice","Replicate:CustomerInvoice","Reorder:CustomerInvoice","ViewAny:CustomerOrder","View:CustomerOrder","Create:CustomerOrder","Update:CustomerOrder","Delete:CustomerOrder","DeleteAny:CustomerOrder","Restore:CustomerOrder","ForceDelete:CustomerOrder","ForceDeleteAny:CustomerOrder","RestoreAny:CustomerOrder","Replicate:CustomerOrder","Reorder:CustomerOrder","ViewAny:CustomerQuote","View:CustomerQuote","Create:CustomerQuote","Update:CustomerQuote","Delete:CustomerQuote","DeleteAny:CustomerQuote","Restore:CustomerQuote","ForceDelete:CustomerQuote","ForceDeleteAny:CustomerQuote","RestoreAny:CustomerQuote","Replicate:CustomerQuote","Reorder:CustomerQuote","ViewAny:CustomerSituation","View:CustomerSituation","Create:CustomerSituation","Update:CustomerSituation","Delete:CustomerSituation","DeleteAny:CustomerSituation","Restore:CustomerSituation","ForceDelete:CustomerSituation","ForceDeleteAny:CustomerSituation","RestoreAny:CustomerSituation","Replicate:CustomerSituation","Reorder:CustomerSituation","ViewAny:Payment","View:Payment","Create:Payment","Update:Payment","Delete:Payment","DeleteAny:Payment","Restore:Payment","ForceDelete:Payment","ForceDeleteAny:Payment","RestoreAny:Payment","Replicate:Payment","Reorder:Payment","ViewAny:PurchaseOrder","View:PurchaseOrder","Create:PurchaseOrder","Update:PurchaseOrder","Delete:PurchaseOrder","DeleteAny:PurchaseOrder","Restore:PurchaseOrder","ForceDelete:PurchaseOrder","ForceDeleteAny:PurchaseOrder","RestoreAny:PurchaseOrder","Replicate:PurchaseOrder","Reorder:PurchaseOrder","ViewAny:PurchaseRequest","View:PurchaseRequest","Create:PurchaseRequest","Update:PurchaseRequest","Delete:PurchaseRequest","DeleteAny:PurchaseRequest","Restore:PurchaseRequest","ForceDelete:PurchaseRequest","ForceDeleteAny:PurchaseRequest","RestoreAny:PurchaseRequest","Replicate:PurchaseRequest","Reorder:PurchaseRequest","ViewAny:ReceiptNote","View:ReceiptNote","Create:ReceiptNote","Update:ReceiptNote","Delete:ReceiptNote","DeleteAny:ReceiptNote","Restore:ReceiptNote","ForceDelete:ReceiptNote","ForceDeleteAny:ReceiptNote","RestoreAny:ReceiptNote","Replicate:ReceiptNote","Reorder:ReceiptNote","ViewAny:SubcontractorSituation","View:SubcontractorSituation","Create:SubcontractorSituation","Update:SubcontractorSituation","Delete:SubcontractorSituation","DeleteAny:SubcontractorSituation","Restore:SubcontractorSituation","ForceDelete:SubcontractorSituation","ForceDeleteAny:SubcontractorSituation","RestoreAny:SubcontractorSituation","Replicate:SubcontractorSituation","Reorder:SubcontractorSituation","ViewAny:SupplierCreditNote","View:SupplierCreditNote","Create:SupplierCreditNote","Update:SupplierCreditNote","Delete:SupplierCreditNote","DeleteAny:SupplierCreditNote","Restore:SupplierCreditNote","ForceDelete:SupplierCreditNote","ForceDeleteAny:SupplierCreditNote","RestoreAny:SupplierCreditNote","Replicate:SupplierCreditNote","Reorder:SupplierCreditNote","ViewAny:SupplierInvoice","View:SupplierInvoice","Create:SupplierInvoice","Update:SupplierInvoice","Delete:SupplierInvoice","DeleteAny:SupplierInvoice","Restore:SupplierInvoice","ForceDelete:SupplierInvoice","ForceDeleteAny:SupplierInvoice","RestoreAny:SupplierInvoice","Replicate:SupplierInvoice","Reorder:SupplierInvoice","View:MonthlyRevenueVarianceWidget","View:RevenueGoalProgressWidget","View:SalesPipelineFunnelWidget","View:OverdueInvoicesDetailWidget","View:MonthlyOrderVolumeChart","View:PurchasesStatsWidget","View:TopCustomersWidget","View:WelcomeCustomer","ViewAny:TrafficFine","View:TrafficFine","Create:TrafficFine","Update:TrafficFine","Delete:TrafficFine","DeleteAny:TrafficFine","Restore:TrafficFine","ForceDelete:TrafficFine","ForceDeleteAny:TrafficFine","RestoreAny:TrafficFine","Replicate:TrafficFine","Reorder:TrafficFine","ViewAny:VehicleAssignment","View:VehicleAssignment","Create:VehicleAssignment","Update:VehicleAssignment","Delete:VehicleAssignment","DeleteAny:VehicleAssignment","Restore:VehicleAssignment","ForceDelete:VehicleAssignment","ForceDeleteAny:VehicleAssignment","RestoreAny:VehicleAssignment","Replicate:VehicleAssignment","Reorder:VehicleAssignment","ViewAny:Vehicle","View:Vehicle","Create:Vehicle","Update:Vehicle","Delete:Vehicle","DeleteAny:Vehicle","Restore:Vehicle","ForceDelete:Vehicle","ForceDeleteAny:Vehicle","RestoreAny:Vehicle","Replicate:Vehicle","Reorder:Vehicle","View:RoutingOptimization","View:FleetStatsOverview","View:LeasingUsageLimitsWidget","View:TcoVarianceWidget","View:FleetUtilizationRate","View:FleetCompositionWidget","View:FleetFinancialsChart","View:FleetAlertsDetailWidget","View:FraudVigilanceWidget","View:ActiveAssignmentsTable","ViewAny:ManufacturingOrder","View:ManufacturingOrder","Create:ManufacturingOrder","Update:ManufacturingOrder","Delete:ManufacturingOrder","DeleteAny:ManufacturingOrder","Restore:ManufacturingOrder","ForceDelete:ManufacturingOrder","ForceDeleteAny:ManufacturingOrder","RestoreAny:ManufacturingOrder","Replicate:ManufacturingOrder","Reorder:ManufacturingOrder","View:ProductionCalendar","View:TrsVarianceWidget","View:QualityGoalProgressWidget","View:ProductionChart","View:ProductionFunnelWidget","View:BlockedOrdersDetailWidget","ViewAny:AssetCategory","View:AssetCategory","Create:AssetCategory","Update:AssetCategory","Delete:AssetCategory","DeleteAny:AssetCategory","Restore:AssetCategory","ForceDelete:AssetCategory","ForceDeleteAny:AssetCategory","RestoreAny:AssetCategory","Replicate:AssetCategory","Reorder:AssetCategory","ViewAny:AssetMaintenance","View:AssetMaintenance","Create:AssetMaintenance","Update:AssetMaintenance","Delete:AssetMaintenance","DeleteAny:AssetMaintenance","Restore:AssetMaintenance","ForceDelete:AssetMaintenance","ForceDeleteAny:AssetMaintenance","RestoreAny:AssetMaintenance","Replicate:AssetMaintenance","Reorder:AssetMaintenance","ViewAny:FixedAsset","View:FixedAsset","Create:FixedAsset","Update:FixedAsset","Delete:FixedAsset","DeleteAny:FixedAsset","Restore:FixedAsset","ForceDelete:FixedAsset","ForceDeleteAny:FixedAsset","RestoreAny:FixedAsset","Replicate:FixedAsset","Reorder:FixedAsset","View:VncVarianceWidget","View:VgpGoalProgressWidget","View:AssetCategoryCompositionWidget","View:AssetAlertsDetailWidget","ViewAny:ClientEquipment","View:ClientEquipment","Create:ClientEquipment","Update:ClientEquipment","Delete:ClientEquipment","DeleteAny:ClientEquipment","Restore:ClientEquipment","ForceDelete:ClientEquipment","ForceDeleteAny:ClientEquipment","RestoreAny:ClientEquipment","Replicate:ClientEquipment","Reorder:ClientEquipment","ViewAny:Intervention","View:Intervention","Create:Intervention","Update:Intervention","Delete:Intervention","DeleteAny:Intervention","Restore:Intervention","ForceDelete:Intervention","ForceDeleteAny:Intervention","RestoreAny:Intervention","Replicate:Intervention","Reorder:Intervention","ViewAny:InterventionReportTemplate","View:InterventionReportTemplate","Create:InterventionReportTemplate","Update:InterventionReportTemplate","Delete:InterventionReportTemplate","DeleteAny:InterventionReportTemplate","Restore:InterventionReportTemplate","ForceDelete:InterventionReportTemplate","ForceDeleteAny:InterventionReportTemplate","RestoreAny:InterventionReportTemplate","Replicate:InterventionReportTemplate","Reorder:InterventionReportTemplate","View:InterventionProfitabilityWidget","View:InterventionSlaGoalWidget","View:InterventionPipelineFunnelWidget","View:UrgentInterventionsDetailWidget","ViewAny:RentalContract","View:RentalContract","Create:RentalContract","Update:RentalContract","Delete:RentalContract","DeleteAny:RentalContract","Restore:RentalContract","ForceDelete:RentalContract","ForceDeleteAny:RentalContract","RestoreAny:RentalContract","Replicate:RentalContract","Reorder:RentalContract","View:RentalCalendarWidget","View:RentalGlobalCostVarianceWidget","View:RentalContractStatusSegmentWidget","View:RentalSupplierCompositionWidget","View:ImminentRentalEndsDetailWidget","ViewAny:AdvancePayment","View:AdvancePayment","Create:AdvancePayment","Update:AdvancePayment","Delete:AdvancePayment","DeleteAny:AdvancePayment","Restore:AdvancePayment","ForceDelete:AdvancePayment","ForceDeleteAny:AdvancePayment","RestoreAny:AdvancePayment","Replicate:AdvancePayment","Reorder:AdvancePayment","ViewAny:PayrollContributionProfile","View:PayrollContributionProfile","Create:PayrollContributionProfile","Update:PayrollContributionProfile","Delete:PayrollContributionProfile","DeleteAny:PayrollContributionProfile","Restore:PayrollContributionProfile","ForceDelete:PayrollContributionProfile","ForceDeleteAny:PayrollContributionProfile","RestoreAny:PayrollContributionProfile","Replicate:PayrollContributionProfile","Reorder:PayrollContributionProfile","ViewAny:Payslip","View:Payslip","Create:Payslip","Update:Payslip","Delete:Payslip","DeleteAny:Payslip","Restore:Payslip","ForceDelete:Payslip","ForceDeleteAny:Payslip","RestoreAny:Payslip","Replicate:Payslip","Reorder:Payslip","View:PayrollCostVarianceWidget","View:PayrollGenerationGoalWidget","View:PayrollCostCompositionWidget","View:PendingPaymentsDetailWidget","ViewAny:Abscence","View:Abscence","Create:Abscence","Update:Abscence","Delete:Abscence","DeleteAny:Abscence","Restore:Abscence","ForceDelete:Abscence","ForceDeleteAny:Abscence","RestoreAny:Abscence","Replicate:Abscence","Reorder:Abscence","ViewAny:Equipement","View:Equipement","Create:Equipement","Update:Equipement","Delete:Equipement","DeleteAny:Equipement","Restore:Equipement","ForceDelete:Equipement","ForceDeleteAny:Equipement","RestoreAny:Equipement","Replicate:Equipement","Reorder:Equipement","View:AtelierProduction","View:MonProfil","View:VehicleInspection","View:CurrentVehicleWidget","View:SalarieStatsOverview","ViewAny:ChantierTask","View:ChantierTask","Create:ChantierTask","Update:ChantierTask","Delete:ChantierTask","DeleteAny:ChantierTask","Restore:ChantierTask","ForceDelete:ChantierTask","ForceDeleteAny:ChantierTask","RestoreAny:ChantierTask","Replicate:ChantierTask","Reorder:ChantierTask","ViewAny:Consultation","View:Consultation","Create:Consultation","Update:Consultation","Delete:Consultation","DeleteAny:Consultation","Restore:Consultation","ForceDelete:Consultation","ForceDeleteAny:Consultation","RestoreAny:Consultation","Replicate:Consultation","Reorder:Consultation","View:ManageDocuments","View:OfflineInterventions","View:SaisieHeuresCollective","ViewAny:ThirdParty","View:ThirdParty","Create:ThirdParty","Update:ThirdParty","Delete:ThirdParty","DeleteAny:ThirdParty","Restore:ThirdParty","ForceDelete:ThirdParty","ForceDeleteAny:ThirdParty","RestoreAny:ThirdParty","Replicate:ThirdParty","Reorder:ThirdParty","View:ClientAcquisitionVarianceWidget","View:DatabaseQualityGoalWidget","View:PortfolioCompositionWidget","View:ComplianceAlertDetailWidget","ViewAny:BimModel","View:BimModel","Create:BimModel","Update:BimModel","Delete:BimModel","DeleteAny:BimModel","Restore:BimModel","ForceDelete:BimModel","ForceDeleteAny:BimModel","RestoreAny:BimModel","Replicate:BimModel","Reorder:BimModel"]},{"name":"Chef de Chantier","guard_name":"web","permissions":["View:Employee","View:ExpenseAdvance","Create:ExpenseAdvance","Update:ExpenseAdvance","Delete:ExpenseAdvance","ViewAny:ExpenseReport","View:ExpenseReport","Create:ExpenseReport","Update:ExpenseReport","Delete:ExpenseReport","DeleteAny:ExpenseReport","Restore:ExpenseReport","ForceDelete:ExpenseReport","ForceDeleteAny:ExpenseReport","RestoreAny:ExpenseReport","Replicate:ExpenseReport","Reorder:ExpenseReport","ViewAny:TimeEntry","View:TimeEntry","Create:TimeEntry","Update:TimeEntry","Delete:TimeEntry","DeleteAny:TimeEntry","Restore:TimeEntry","ForceDelete:TimeEntry","ForceDeleteAny:TimeEntry","RestoreAny:TimeEntry","Replicate:TimeEntry","Reorder:TimeEntry","View:Item","View:Warehouse","ViewAny:ChantierLog","View:ChantierLog","Create:ChantierLog","Update:ChantierLog","Delete:ChantierLog","DeleteAny:ChantierLog","Restore:ChantierLog","ForceDelete:ChantierLog","ForceDeleteAny:ChantierLog","RestoreAny:ChantierLog","Replicate:ChantierLog","Reorder:ChantierLog","ViewAny:Chantier","View:Chantier","Update:Chantier","View:ChecklistTemplate","Update:ChecklistTemplate","View:CustomerQuote","Create:CustomerQuote","Update:CustomerQuote","View:PurchaseRequest","Create:PurchaseRequest","Update:PurchaseRequest","View:TrafficFine","View:VehicleAssignment","View:Vehicle","View:ManufacturingOrder","Create:ManufacturingOrder","Update:ManufacturingOrder","View:ClientEquipment","Create:ClientEquipment","Update:ClientEquipment","Delete:ClientEquipment","ViewAny:Intervention","View:Intervention","Create:Intervention","Update:Intervention","Delete:Intervention","View:RentalContract","View:AdvancePayment","View:Abscence","Create:Abscence","Update:Abscence","Delete:Abscence","ViewAny:Equipement","View:Equipement","Update:Equipement","ViewAny:ChantierTask","View:ChantierTask","Create:ChantierTask","Update:ChantierTask","Delete:ChantierTask","DeleteAny:ChantierTask","Restore:ChantierTask","ForceDelete:ChantierTask","ForceDeleteAny:ChantierTask","RestoreAny:ChantierTask","Replicate:ChantierTask","Reorder:ChantierTask","View:Consultation","View:ThirdParty","Update:ThirdParty","View:BimModel","Create:BimModel","Update:BimModel"]}]';
        $directPermissions = '[]';

        // 1. Seed tenants first (if present)
        if (! blank($tenants) && $tenants !== '[]') {
            static::seedTenants($tenants);
        }

        // 2. Seed roles with permissions
        static::makeRolesWithPermissions($rolesWithPermissions);

        // 3. Seed direct permissions
        static::makeDirectPermissions($directPermissions);

        // 4. Seed users with their roles/permissions (if present)
        if (! blank($users) && $users !== '[]') {
            static::seedUsers($users);
        }

        // 5. Seed user-tenant pivot (if present)
        if (! blank($userTenantPivot) && $userTenantPivot !== '[]') {
            static::seedUserTenantPivot($userTenantPivot);
        }

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function seedTenants(string $tenants): void
    {
        if (blank($tenantData = json_decode($tenants, true))) {
            return;
        }

        $tenantModel = '';
        if (blank($tenantModel)) {
            return;
        }

        foreach ($tenantData as $tenant) {
            $tenantModel::firstOrCreate(
                ['id' => $tenant['id']],
                $tenant
            );
        }
    }

    protected static function seedUsers(string $users): void
    {
        if (blank($userData = json_decode($users, true))) {
            return;
        }

        $userModel = 'App\Models\User';
        $tenancyEnabled = false;

        foreach ($userData as $data) {
            // Extract role/permission data before creating user
            $roles = $data['roles'] ?? [];
            $permissions = $data['permissions'] ?? [];
            $tenantRoles = $data['tenant_roles'] ?? [];
            $tenantPermissions = $data['tenant_permissions'] ?? [];
            unset($data['roles'], $data['permissions'], $data['tenant_roles'], $data['tenant_permissions']);

            $user = $userModel::firstOrCreate(
                ['email' => $data['email']],
                $data
            );

            // Handle tenancy mode - sync roles/permissions per tenant
            if ($tenancyEnabled && (! empty($tenantRoles) || ! empty($tenantPermissions))) {
                foreach ($tenantRoles as $tenantId => $roleNames) {
                    $contextId = $tenantId === '_global' ? null : $tenantId;
                    setPermissionsTeamId($contextId);
                    $user->syncRoles($roleNames);
                }

                foreach ($tenantPermissions as $tenantId => $permissionNames) {
                    $contextId = $tenantId === '_global' ? null : $tenantId;
                    setPermissionsTeamId($contextId);
                    $user->syncPermissions($permissionNames);
                }
            } else {
                // Non-tenancy mode
                if (! empty($roles)) {
                    $user->syncRoles($roles);
                }

                if (! empty($permissions)) {
                    $user->syncPermissions($permissions);
                }
            }
        }
    }

    protected static function seedUserTenantPivot(string $pivot): void
    {
        if (blank($pivotData = json_decode($pivot, true))) {
            return;
        }

        $pivotTable = '';
        if (blank($pivotTable)) {
            return;
        }

        foreach ($pivotData as $row) {
            $uniqueKeys = [];

            if (isset($row['user_id'])) {
                $uniqueKeys['user_id'] = $row['user_id'];
            }

            $tenantForeignKey = 'team_id';
            if (! blank($tenantForeignKey) && isset($row[$tenantForeignKey])) {
                $uniqueKeys[$tenantForeignKey] = $row[$tenantForeignKey];
            }

            if (! empty($uniqueKeys)) {
                DB::table($pivotTable)->updateOrInsert($uniqueKeys, $row);
            }
        }
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            return;
        }

        /** @var class-string<Model> $roleModel */
        $roleModel = Utils::getRoleModel();
        /** @var class-string<Model> $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        $tenancyEnabled = false;
        $teamForeignKey = 'team_id';

        foreach ($rolePlusPermissions as $rolePlusPermission) {
            $tenantId = $rolePlusPermission[$teamForeignKey] ?? null;

            // Set tenant context for role creation and permission sync
            if ($tenancyEnabled) {
                setPermissionsTeamId($tenantId);
            }

            $roleData = [
                'name' => $rolePlusPermission['name'],
                'guard_name' => $rolePlusPermission['guard_name'],
            ];

            // Include tenant ID in role data (can be null for global roles)
            if ($tenancyEnabled && ! blank($teamForeignKey)) {
                $roleData[$teamForeignKey] = $tenantId;
            }

            $role = $roleModel::firstOrCreate($roleData);

            if (! blank($rolePlusPermission['permissions'])) {
                $permissionModels = collect($rolePlusPermission['permissions'])
                    ->map(fn ($permission) => $permissionModel::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => $rolePlusPermission['guard_name'],
                    ]))
                    ->all();

                $role->syncPermissions($permissionModels);
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (blank($permissions = json_decode($directPermissions, true))) {
            return;
        }

        /** @var class-string<Model> $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        foreach ($permissions as $permission) {
            if ($permissionModel::whereName($permission['name'])->doesntExist()) {
                $permissionModel::create([
                    'name' => $permission['name'],
                    'guard_name' => $permission['guard_name'],
                ]);
            }
        }
    }
}
