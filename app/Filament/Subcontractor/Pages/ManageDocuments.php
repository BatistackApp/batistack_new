<?php

namespace App\Filament\Subcontractor\Pages;

use App\Services\Tiers\VigilanceService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ManageDocuments extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Documents Vigilance';

    protected static ?string $title = 'Mes Documents Légaux';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.subcontractor.pages.manage-documents';

    public ?array $data = [];

    public bool $isCompliant = false;

    public array $issues = [];

    public function mount(): void
    {
        $this->checkCompliance();
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $thirdPartyId = auth()->user()->contact->third_party_id;
        $disk = Storage::disk('local');

        $this->form->fill([
            'vigilance_attestation' => $disk->exists("third_parties/{$thirdPartyId}/documents/vigilance_attestation.pdf")
                ? ["third_parties/{$thirdPartyId}/documents/vigilance_attestation.pdf"]
                : [],
            'decennale_insurance' => $disk->exists("third_parties/{$thirdPartyId}/documents/decennale_insurance.pdf")
                ? ["third_parties/{$thirdPartyId}/documents/decennale_insurance.pdf"]
                : [],
            'kbis' => $disk->exists("third_parties/{$thirdPartyId}/documents/kbis.pdf")
                ? ["third_parties/{$thirdPartyId}/documents/kbis.pdf"]
                : [],
        ]);
    }

    protected function checkCompliance(): void
    {
        $thirdParty = auth()->user()->contact->thirdParty;
        $vigilanceService = app(VigilanceService::class);
        $results = $vigilanceService->scanCompliance($thirdParty);
        $this->isCompliant = $results['compliant'];
        $this->issues = $results['issues'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Dépôt de Fichiers')
                    ->description('Veuillez fournir les documents légaux récents au format PDF. Le téléversement d\'un nouveau fichier écrasera l\'ancien.')
                    ->schema([
                        FileUpload::make('vigilance_attestation')
                            ->label('Attestation de Vigilance URSSAF (moins de 6 mois)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->disk('local')
                            ->directory(fn () => 'third_parties/'.auth()->user()->contact->third_party_id.'/documents')
                            ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => 'vigilance_attestation.pdf')
                            ->downloadable(),
                        FileUpload::make('decennale_insurance')
                            ->label('Attestation d\'Assurance Décennale (en cours de validité)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->disk('local')
                            ->directory(fn () => 'third_parties/'.auth()->user()->contact->third_party_id.'/documents')
                            ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => 'decennale_insurance.pdf')
                            ->downloadable(),
                        FileUpload::make('kbis')
                            ->label('Extrait Kbis (moins de 3 mois)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->disk('local')
                            ->directory(fn () => 'third_parties/'.auth()->user()->contact->third_party_id.'/documents')
                            ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => 'kbis.pdf')
                            ->downloadable(),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $this->form->getState();

        $this->checkCompliance();
        $this->fillForm();

        Notification::make()
            ->title('Documents enregistrés')
            ->body($this->isCompliant
                ? 'Vos documents ont bien été mis à jour. Votre dossier est conforme.'
                : 'Vos documents ont bien été mis à jour. Certains documents sont encore manquants.')
            ->success()
            ->send();
    }
}
