<?php

namespace App\Filament\Salarie\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Log;

class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('scanReceipt')
                ->label('Scanner un ticket (Notes de frais)')
                ->icon('heroicon-o-camera')
                ->color('primary')
                ->form([
                    FileUpload::make('receipt_image')
                        ->label('Photo du ticket')
                        ->image()
                        ->required(),
                    \Filament\Forms\Components\Select::make('vehicle_id')
                        ->label('Véhicule (Si carburant ou péage)')
                        ->options(\App\Models\Flottes\Vehicle::pluck('license_plate', 'id'))
                        ->searchable()
                        ->nullable(),
                ])
                ->action(function (array $data) {
                    $path = storage_path('app/public/' . $data['receipt_image']);
                    
                    // Call OCR
                    $ocrService = new \App\Services\RH\GoogleCloudVisionOcrService();
                    $extractedData = $ocrService->extractData($path);
                    
                    // Get current employee
                    $employee = auth()->user()->salarie;
                    if (!$employee) {
                        \Filament\Notifications\Notification::make()->danger()->title('Employé non trouvé')->send();
                        return;
                    }

                    // Get or create current month's report
                    $report = \App\Models\RH\ExpenseReport::firstOrCreate([
                        'employee_id' => $employee->id,
                        'month' => now()->month,
                        'year' => now()->year,
                    ], [
                        'status' => \App\Enums\RH\ExpenseReportStatus::DRAFT,
                        'total_amount' => 0,
                    ]);

                    // Use OCR category or fallback
                    $category = $extractedData['category'] ?? 'Autre';

                    // Add item
                    $item = $report->items()->create([
                        'category' => $category,
                        'date' => $extractedData['date'] ?? now(),
                        'amount_ttc' => $extractedData['amount_ttc'] ?? 0,
                        'amount_ht' => $extractedData['amount_ht'],
                        'vat_amount' => $extractedData['vat_amount'],
                        'merchant' => $extractedData['merchant'],
                        'vehicle_id' => $data['vehicle_id'] ?? null,
                        'status' => \App\Enums\RH\ExpenseItemStatus::PENDING,
                    ]);

                    try {
                        $item->addMedia($path)->toMediaCollection('receipts');
                    } catch (\Exception $e) {
                        Log::error('Media attach error: ' . $e->getMessage());
                    }

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Ticket ajouté avec succès')
                        ->body("Montant extrait: {$item->amount_ttc} €")
                        ->send();
                }),
        ];
    }
}
