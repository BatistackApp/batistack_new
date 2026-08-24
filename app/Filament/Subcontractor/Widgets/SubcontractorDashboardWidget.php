<?php

namespace App\Filament\Subcontractor\Widgets;

use App\Enums\Chantiers\ChantierStatus;
use App\Enums\Commerce\InvoiceStatus;
use App\Models\Chantiers\ChantierTask;
use App\Models\Commerce\SubcontractorSituation;
use App\Models\Tiers\Consultation;
use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\VigilanceService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SubcontractorDashboardWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $thirdParty = $this->getSubcontractor();

        if (! $thirdParty) {
            return [];
        }

        $vigilanceService = app(VigilanceService::class);
        $compliance = $vigilanceService->scanCompliance($thirdParty);
        $isCompliant = $compliance['compliant'];

        $activeChantiersCount = $thirdParty->chantiers()
            ->where('status', ChantierStatus::IN_PROGRESS)
            ->count();

        $pendingInvoices = SubcontractorSituation::where('subcontractor_id', $thirdParty->id)
            ->whereIn('status', [InvoiceStatus::DRAFT, InvoiceStatus::VALIDATED])
            ->count();

        $pendingAmount = SubcontractorSituation::where('subcontractor_id', $thirdParty->id)
            ->whereIn('status', [InvoiceStatus::DRAFT, InvoiceStatus::VALIDATED])
            ->sum('total_ht');

        $upcomingTasks = ChantierTask::whereHas('allocations', function ($query) use ($thirdParty) {
            $query->where('allocatable_type', ThirdParty::class)
                ->where('allocatable_id', $thirdParty->id);
        })
            ->where('end_date', '>=', now())
            ->orderBy('end_date', 'asc')
            ->count();

        $openConsultations = Consultation::where('status', 'published')
            ->whereHas('offers', function ($query) use ($thirdParty) {
                $query->where('third_party_id', $thirdParty->id);
            }, '<')
            ->count();

        $lastInvoice = SubcontractorSituation::where('subcontractor_id', $thirdParty->id)
            ->latest('created_at')
            ->first();

        $lastInvoiceText = $lastInvoice
            ? 'Facture '.$lastInvoice->reference.' — '.number_format($lastInvoice->total_ht, 2, ',', ' ').' €'
            : 'Aucune facture';

        return [
            Stat::make('Conformité', $isCompliant ? 'Conforme' : 'Non conforme')
                ->description($isCompliant ? 'Documents légaux à jour' : 'Documents manquants ou expirés')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color($isCompliant ? 'success' : 'danger')
                ->url(route('filament.sous-traitant.pages.manage-documents')),

            Stat::make('Chantiers actifs', $activeChantiersCount)
                ->description($activeChantiersCount > 1 ? 'chantiers en cours' : 'chantier en cours')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('primary'),

            Stat::make('Factures en attente', $pendingInvoices)
                ->description(number_format($pendingAmount, 2, ',', ' ').' € HT')
                ->descriptionIcon('heroicon-o-document-text')
                ->color($pendingInvoices > 0 ? 'warning' : 'gray'),

            Stat::make('Tâches à venir', $upcomingTasks)
                ->description($upcomingTasks > 0 ? 'prochaines échéances' : 'Aucune tâche planifiée')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color($upcomingTasks > 0 ? 'info' : 'gray'),

            Stat::make('Consultations ouvertes', $openConsultations)
                ->description($openConsultations > 0 ? 'appels d\'offres disponibles' : 'Aucune consultation')
                ->descriptionIcon('heroicon-o-chat-bubble-left-ellipsis')
                ->color($openConsultations > 0 ? 'info' : 'gray'),

            Stat::make('Dernière facture', $lastInvoiceText)
                ->description($lastInvoice?->created_at?->diffForHumans() ?? '')
                ->descriptionIcon('heroicon-o-clock')
                ->color('gray'),
        ];
    }

    private function getSubcontractor(): ?ThirdParty
    {
        $user = auth()->user();

        if (! $user || ! $user->contact || ! $user->contact->thirdParty) {
            return null;
        }

        return $user->contact->thirdParty;
    }
}
