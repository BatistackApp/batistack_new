<?php

namespace App\Filament\Subcontractor\Pages;

use App\Services\Tiers\VigilanceService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ManageDocuments extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationLabel = 'Documents Vigilance';
    protected static string $view = 'filament.subcontractor.pages.manage-documents';
    protected static ?string $title = 'Mes Documents Légaux';

    public ?array $data = [];

    public bool $isCompliant = false;

    public function mount(): void
    {
        $this->checkCompliance();

        $thirdPartyId = auth()->user()->contact->third_party_id;
        $disk = Storage::disk('local');
        
        $this->form->fill([
            'vigilance_attestation' => $disk->exists("third_parties/{$thirdPartyId}/documents/vigilance_attestation.pdf") ? ["third_parties/{$thirdPartyId}/documents/vigilance_attestation.pdf"] : [],
            'decennale_insurance' => $disk->exists("third_parties/{$thirdPartyId}/documents/decennale_insurance.pdf") ? ["third_parties/{$thirdPartyId}/documents/decennale_insurance.pdf"] : [],
            'kbis' => $disk->exists("third_parties/{$thirdPartyId}/documents/kbis.pdf") ? ["third_parties/{$thirdPartyId}/documents/kbis.pdf"] : [],
        ]);
    }

    protected function checkCompliance(): void
    {
        $thirdParty = auth()->user()->contact->thirdParty;
        $vigilanceService = app(VigilanceService::class);
        $results = $vigilanceService->scanCompliance($thirdParty);
        $this->isCompliant = $results['compliant'];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Dépôt de Fichiers')
                    ->description('Veuillez fournir les documents légaux récents au format PDF. Le téléversement d\'un nouveau fichier écrasera l\'ancien.')
                    ->schema([
                        $this->createDocumentUploader('vigilance_attestation', 'Attestation de Vigilance URSSAF (moins de 6 mois)'),
                        $this->createDocumentUploader('decennale_insurance', 'Attestation d\'Assurance Décennale (en cours de validité)'),
                        $this->createDocumentUploader('kbis', 'Extrait Kbis (moins de 3 mois)'),
                    ])
            ])
            ->statePath('data');
    }

    protected function createDocumentUploader(string $name, string $label)
    {
        return FileUpload::make($name)
            ->label($label)
            ->acceptedFileTypes(['application/pdf'])
            ->disk('local')
            ->directory(fn () => 'third_parties/' . auth()->user()->contact->third_party_id . '/documents')
            ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => $name . '.pdf')
            ->downloadable();
    }

    public function submit(): void
    {
        $this->form->getState();
        
        $this->checkCompliance();
        
        Notification::make()
            ->title('Documents enregistrés')
            ->body('Vos documents ont bien été mis à jour.')
            ->success()
            ->send();
    }
}
