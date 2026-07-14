<?php

namespace App\Filament\RH\Resources\ExpenseReports\Pages;

use App\Filament\RH\Resources\ExpenseReports\ExpenseReportResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditExpenseReport extends EditRecord
{
    protected static string $resource = ExpenseReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('submit')
                ->label('Soumettre')
                ->color('primary')
                ->icon('heroicon-o-paper-airplane')
                ->action(function ($record) {
                    app(\App\Services\RH\ExpenseWorkflowService::class)->submit($record);
                    \Filament\Notifications\Notification::make()->success()->title('Note soumise avec succès')->send();
                })
                ->visible(fn ($record) => $record->status === \App\Enums\RH\ExpenseReportStatus::DRAFT),
                
            \Filament\Actions\Action::make('validate')
                ->label('Valider (Manager)')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->action(function ($record) {
                    try {
                        app(\App\Services\RH\ExpenseWorkflowService::class)->validate($record);
                        \Filament\Notifications\Notification::make()->success()->title('Note validée')->send();
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()->danger()->title('Erreur')->body($e->getMessage())->send();
                    }
                })
                ->visible(fn ($record) => $record->status === \App\Enums\RH\ExpenseReportStatus::SUBMITTED),
                
            \Filament\Actions\Action::make('reject')
                ->label('Refuser')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')->label('Motif')->required(),
                ])
                ->action(function ($record, array $data) {
                    app(\App\Services\RH\ExpenseWorkflowService::class)->reject($record, $data['reason']);
                    \Filament\Notifications\Notification::make()->success()->title('Note refusée')->send();
                })
                ->visible(fn ($record) => $record->status === \App\Enums\RH\ExpenseReportStatus::SUBMITTED),
                
            \Filament\Actions\Action::make('pay')
                ->label('Marquer Payée')
                ->color('success')
                ->icon('heroicon-o-currency-euro')
                ->action(function ($record) {
                    app(\App\Services\RH\ExpenseWorkflowService::class)->pay($record);
                    \Filament\Notifications\Notification::make()->success()->title('Note payée')->send();
                })
                ->visible(fn ($record) => $record->status === \App\Enums\RH\ExpenseReportStatus::VALIDATED),
                
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
