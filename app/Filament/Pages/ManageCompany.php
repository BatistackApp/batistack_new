<?php

namespace App\Filament\Pages;

use App\Models\Core\Company;
use Filament\Actions\Action;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ManageCompany extends Page
{
    protected string $view = 'filament.pages.manage-company';

    protected static string|null|\BackedEnum $navigationIcon = Phosphor::BuildingOffice;

    protected static string|null|\UnitEnum $navigationGroup = 'Configuration Système';

    protected static ?string $title = 'Identité de l\'entreprise';

    public ?array $data = [];

    public function mount(): void
    {
        $company = Company::first();

        if ($company) {
            $this->form->fill($company->toArray());
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations Légales')
                    ->schema([
                        TextInput::make('legal_name')
                            ->label('Raison Sociale')
                            ->required(),
                        TextInput::make('siret')
                            ->label('SIRET')
                            ->length(14),
                        TextInput::make('vat_number')
                            ->label('N° TVA Intracommunautaire'),
                        TextInput::make('share_capital')
                            ->label('Capital Social')
                            ->numeric()
                            ->prefix('€'),
                    ])->columns(2),

                Section::make('Coordonnées & Contact')
                    ->schema([
                        TextInput::make('address')->label('Adresse'),
                        TextInput::make('zip_code')->label('Code Postal'),
                        TextInput::make('city')->label('Ville'),
                        TextInput::make('phone')->label('Téléphone'),
                        TextInput::make('email')->label('Email')->email(),
                        TextInput::make('website')->label('Site Web')->url(),
                    ])->columns(2),

                Section::make('Identité Visuelle')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('logo')
                            ->label('Logo de l\'entreprise')
                            ->collection('core')
                            ->visibility('public')
                            ->directory('core')
                            ->downloadable()
                            ->openable()
                            ->live()
                            ->helperText('Utilisé pour les documents officiels (Factures, Devis).'),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Enregistrer les modifications')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $company = Company::first() ?? new Company;
        $company->fill($data);
        $company->save();

        Notification::make()
            ->title('Configuration mise à jour')
            ->success()
            ->send();
    }
}
