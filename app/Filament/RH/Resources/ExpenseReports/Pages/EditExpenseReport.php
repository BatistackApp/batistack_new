<?php

namespace App\Filament\RH\Resources\ExpenseReports\Pages;

use App\Enums\RH\ExpenseReportStatus;
use App\Filament\RH\Resources\ExpenseReports\ExpenseReportResource;
use App\Services\RH\ExpenseWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditExpenseReport extends EditRecord
{
    protected static string $resource = ExpenseReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('Soumettre')
                ->color('primary')
                ->icon('heroicon-o-paper-airplane')
                ->action(function ($record) {
                    app(ExpenseWorkflowService::class)->submit($record);
                    Notification::make()->success()->title('Note soumise avec succès')->send();
                })
                ->visible(fn ($record) => $record->status === ExpenseReportStatus::DRAFT),

            Action::make('validate')
                ->label('Valider (Manager)')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->action(function ($record) {
                    try {
                        app(ExpenseWorkflowService::class)->validate($record);
                        Notification::make()->success()->title('Note validée')->send();
                    } catch (\Exception $e) {
                        Notification::make()->danger()->title('Erreur')->body($e->getMessage())->send();
                    }
                })
                ->visible(fn ($record) => $record->status === ExpenseReportStatus::SUBMITTED),

            Action::make('reject')
                ->label('Refuser')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->form([
                    Textarea::make('reason')->label('Motif')->required(),
                ])
                ->action(function ($record, array $data) {
                    app(ExpenseWorkflowService::class)->reject($record, $data['reason']);
                    Notification::make()->success()->title('Note refusée')->send();
                })
                ->visible(fn ($record) => $record->status === ExpenseReportStatus::SUBMITTED),

            Action::make('pay')
                ->label('Marquer Payée')
                ->color('success')
                ->icon('heroicon-o-currency-euro')
                ->action(function ($record) {
                    app(ExpenseWorkflowService::class)->pay($record);
                    Notification::make()->success()->title('Note payée')->send();
                })
                ->visible(fn ($record) => $record->status === ExpenseReportStatus::VALIDATED),

            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
