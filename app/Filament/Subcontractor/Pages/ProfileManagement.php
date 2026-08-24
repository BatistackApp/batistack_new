<?php

namespace App\Filament\Subcontractor\Pages;

use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\VigilanceService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileManagement extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = 'Mon Profil';

    protected static ?string $title = 'Profil Entreprise';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.subcontractor.pages.profile-management';

    public ?array $profileData = [];

    public ?array $passwordData = [];

    public bool $isCompliant = false;

    public function mount(): void
    {
        $thirdParty = $this->getThirdParty();

        if ($thirdParty) {
            $this->profileForm->fill([
                'name' => $thirdParty->name,
                'legal_name' => $thirdParty->legal_name,
                'siren' => $thirdParty->siren,
                'siret' => $thirdParty->siret,
                'vat_number' => $thirdParty->vat_number,
                'email' => $thirdParty->email,
                'phone' => $thirdParty->phone,
                'iban' => $thirdParty->iban,
                'bic' => $thirdParty->bic,
            ]);

            $vigilanceService = app(VigilanceService::class);
            $this->isCompliant = $vigilanceService->scanCompliance($thirdParty)['compliant'];
        }
    }

    protected function getForms(): array
    {
        return [
            'profileForm',
            'passwordForm',
        ];
    }

    public function profileForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informations générales')
                    ->description('Données de votre entreprise')
                    ->schema([
                        TextInput::make('name')
                            ->label('Raison sociale')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('legal_name')
                            ->label('Dénomination légale')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Identifiants')
                    ->schema([
                        TextInput::make('siren')
                            ->label('SIREN')
                            ->maxLength(255),
                        TextInput::make('siret')
                            ->label('SIRET')
                            ->maxLength(255),
                        TextInput::make('vat_number')
                            ->label('N° TVA')
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Section::make('Coordonnées')
                    ->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Informations bancaires')
                    ->schema([
                        TextInput::make('iban')
                            ->label('IBAN')
                            ->maxLength(255),
                        TextInput::make('bic')
                            ->label('BIC / SWIFT')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ])
            ->statePath('profileData');
    }

    public function passwordForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Mot de passe')
                    ->description('Assurez-vous que votre compte utilise un mot de passe long et aléatoire.')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Mot de passe actuel')
                            ->password()
                            ->required()
                            ->revealable()
                            ->currentPassword(),
                        TextInput::make('password')
                            ->label('Nouveau mot de passe')
                            ->password()
                            ->required()
                            ->rule(Password::default())
                            ->revealable()
                            ->confirmed(),
                        TextInput::make('password_confirmation')
                            ->label('Confirmer le mot de passe')
                            ->password()
                            ->revealable()
                            ->required(),
                    ])
                    ->columns(1),
            ])
            ->statePath('passwordData');
    }

    public function saveProfileData(): void
    {
        $data = $this->profileForm->getState();
        $thirdParty = $this->getThirdParty();

        if ($thirdParty) {
            $thirdParty->update([
                'name' => $data['name'],
                'legal_name' => $data['legal_name'],
                'siren' => $data['siren'],
                'siret' => $data['siret'],
                'vat_number' => $data['vat_number'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'iban' => $data['iban'],
                'bic' => $data['bic'],
            ]);

            Notification::make()
                ->title('Profil entreprise mis à jour')
                ->success()
                ->send();
        }
    }

    public function savePasswordData(): void
    {
        $data = $this->passwordForm->getState();

        auth()->user()->update([
            'password' => Hash::make($data['password']),
        ]);

        $this->passwordForm->fill();

        Notification::make()
            ->title('Mot de passe mis à jour avec succès.')
            ->success()
            ->send();
    }

    private function getThirdParty(): ?ThirdParty
    {
        $user = auth()->user();

        if (! $user || ! $user->contact || ! $user->contact->thirdParty) {
            return null;
        }

        return $user->contact->thirdParty;
    }
}
