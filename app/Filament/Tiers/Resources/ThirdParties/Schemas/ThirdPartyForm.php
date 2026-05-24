<?php

namespace App\Filament\Tiers\Resources\ThirdParties\Schemas;

use App\Enums\Tiers\ThirdPartyType;
use App\Services\Core\SirenService;
use App\Services\Tiers\ThirdPartyService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Intervention\Validation\Rules\Iban;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ThirdPartyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tiers')
                    ->tabs([
                        Tabs\Tab::make('Identité & Légal')
                            ->icon(Phosphor::IdentificationCard)
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('siret')
                                            ->label('Numéro SIRET')
                                            ->length(14)
                                            ->required()
                                            ->suffixAction(
                                                Action::make('importSiren')
                                                    ->icon(Phosphor::MagnifyingGlass)
                                                    ->color('info')
                                                    ->tooltip('Importer les informations SIREN')
                                                    ->action(function ($state, Set $set) {
                                                        if (blank($state)) {
                                                            return;
                                                        }
                                                        try {
                                                            $data = app(SirenService::class)->getInformation($state);
                                                            if ($data) {
                                                                $unite = $data['etablissement']['uniteLegale'];
                                                                $set('name', $unite['denominationUniteLegale'] ?? $unite['nomUniteLegale']);
                                                                $set('legal_name', $unite['denominationUniteLegale'] ?? null);
                                                                $set('siren', substr($state, 0, 9));
                                                                Notification::make()->title('Données SIREN importées')->success()->send();
                                                            }
                                                        } catch (\Exception $e) {
                                                            Notification::make()->title('Erreur API SIREN')->danger()->body($e->getMessage())->send();
                                                        }
                                                    })
                                            ),
                                        TextInput::make('name')
                                            ->label('Nom commercial')
                                            ->required(),
                                        TextInput::make('legal_name')
                                            ->label('Raison sociale'),
                                        Select::make('type')
                                            ->label('Type de partenaire')
                                            ->enum(ThirdPartyType::class)
                                            ->required()
                                            ->live()
                                            ->native(false),
                                        TextInput::make('vat_number')
                                            ->suffixAction(
                                                Action::make('num_tva')
                                                    ->icon(Phosphor::MagnifyingGlass)
                                                    ->color('info')
                                                    ->tooltip('Pré-calculer le Numéro de TVA')
                                                    ->action(function ($state, Set $set, Get $get) {
                                                        try {
                                                            $data = app(ThirdPartyService::class)->calculateVatNumber(substr($get('siret'), 0, 9));

                                                            if ($data) {
                                                                $set('vat_number', $data);
                                                                Notification::make()
                                                                    ->success()
                                                                    ->title('Numéro de TVA calculé')
                                                                    ->send();
                                                            }
                                                        } catch (\Exception $e) {
                                                            Notification::make()
                                                                ->danger()
                                                                ->title('Erreur de calcul du TVA')
                                                                ->send();
                                                        }
                                                    }),
                                            )
                                            ->label('N° TVA'),
                                        Toggle::make('is_active')
                                            ->label('Compte actif')
                                            ->default(true)
                                            ->onColor('success'),
                                    ]),
                            ]),

                        Tabs\Tab::make('Coordonnées & Finance')
                            ->icon(Phosphor::CurrencyEur)
                            ->schema([
                                Section::make('Contact Global')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('email')->email(),
                                        TextInput::make('phone')->tel(),
                                        TextInput::make('website')->url(),
                                    ]),
                                Section::make('Conditions Financières')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('payment_terms_days')
                                            ->label('Délai de règlement')
                                            ->numeric()
                                            ->suffix('jours'),
                                        TextInput::make('credit_limit')
                                            ->label('Encours autorisé')
                                            ->numeric()
                                            ->prefix('€'),
                                    ]),

                                Section::make('Coordonnées Bancaires')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('iban')
                                            ->label('IBAN')
                                            ->mask('aa99 9999 9999 9999 9999 9999 9999 9999')
                                            ->rule(new Iban),

                                        TextInput::make('bic')
                                            ->label('BIC'),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
