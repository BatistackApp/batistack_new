<?php

namespace App\Filament\RH\Resources\Employees\RelationManagers;

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Enums\RH\ContractType;
use App\Models\RH\Contract;
use App\Services\Core\SignatureService;
use App\Services\RH\RHDocumentService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'contracts';

    protected static ?string $title = 'Historique des contrats';

    protected static string|BackedEnum|null $icon = Phosphor::FileText;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Détails du Contrat')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('type')
                            ->label('Type de contrat')
                            ->options(ContractType::class)
                            ->required()
                            ->native(false),
                        TextInput::make('job_title')
                            ->label('Intitulé du poste')
                            ->required()
                            ->placeholder('ex: Couvreur N3P2'),
                        DatePicker::make('start_date')
                            ->label('Date d\'entrée')
                            ->required()
                            ->native(false),
                        DatePicker::make('end_date')
                            ->label('Date de fin')
                            ->helperText('Laisser vide pour un CDI')
                            ->native(false),
                        TextInput::make('hourly_rate')
                            ->label('Taux horaire brut')
                            ->numeric()
                            ->prefix('€')
                            ->required()
                            ->step(0.0001),
                        TextInput::make('weekly_hours')
                            ->label('Heures hebdomadaires')
                            ->numeric()
                            ->default(35)
                            ->suffix('h'),
                        Select::make('payroll_contribution_profile_id')
                            ->label('Profil de cotisations')
                            ->relationship('payrollContributionProfile', 'name')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->helperText('Définit les taux de cotisations (ex: Bâtiment ETAM, Ouvrier).'),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('job_title')
            ->columns([
                TextColumn::make('job_title')
                    ->label('Poste')
                    ->weight('bold'),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('start_date')
                    ->label('Du')
                    ->date('d/m/Y'),
                TextColumn::make('end_date')
                    ->label('Au')
                    ->date('d/m/Y')
                    ->placeholder('En cours'),
                TextColumn::make('hourly_rate')
                    ->label('Taux H.')
                    ->money('EUR')
                    ->color('gray'),
                TextColumn::make('signature_status')
                    ->label('Signature')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->label('Nouveau contrat'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('print_contract')
                    ->label('Imprimer')
                    ->icon(Phosphor::Printer)
                    ->action(function (Contract $record, RHDocumentService $service) {
                        $path = \Illuminate\Support\Facades\Storage::disk('public')->path('documents/rh/contrat_'.$record->employee->registration_number.'.pdf');
                        if (!file_exists($path)) {
                            $path = $service->generateContract($record);
                        }
                        return response()->download($path);
                    }),
                Action::make('request_signature')
                    ->icon(Phosphor::PenNib)
                    ->color('info')
                    ->visible(fn (Contract $record) => $record->signature_status === SignatureStatus::PENDING)
                    ->action(function (Contract $record, SignatureService $service) {
                        $email = $record->employee->email;
                        $name = $record->employee->full_name;
                        $pathFile = Storage::disk('public')->path('documents/rh/contrat_'.$record->employee->registration_number.'.pdf');

                        if (! $email) {
                            Notification::make()->title('Erreur : Le salarié n\'a pas d\'adresse email')->danger()->send();

                            return;
                        }

                        $service->requestSignature(
                            model: $record,
                            type: SignatureType::AUTOGRAPH,
                            email: $email,
                            name: $name,
                            documentPath: $pathFile,
                        );

                        Notification::make()->title('Demande de signature envoyée par email')->success()->send();
                    })
                    ->label('Demander Signature'),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
