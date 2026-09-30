<?php

namespace App\Filament\Salarie\Pages;

use App\Services\RH\EmployeePinService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class MonProfil extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = 'Mon Profil';

    protected static ?string $title = 'Mon Profil';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.salarie.pages.mon-profil';

    public ?array $employeeData = [];

    public ?array $passwordData = [];

    public ?array $pinData = [];

    public function mount(): void
    {
        $user = auth()->user();
        $employee = $user->salarie;

        if ($employee) {
            $this->employeeForm->fill([
                'phone' => $employee->phone,
                'address' => $employee->address,
                'postal_code' => $employee->postal_code,
                'city' => $employee->city,
                'email' => $employee->email,
            ]);
        }
    }

    protected function getForms(): array
    {
        return [
            'employeeForm',
            'passwordForm',
            'pinForm',
        ];
    }

    public function employeeForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informations de contact')
                    ->description('Mettez à jour vos informations personnelles.')
                    ->schema([
                        TextInput::make('phone')->label('Téléphone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('email')->label('Email')
                            ->label('Adresse email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('address')->label('Adresse')
                            ->label('Adresse postale')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('postal_code')
                            ->label('Code postal')
                            ->maxLength(255),
                        TextInput::make('city')->label('Ville')
                            ->label('Ville')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ])
            ->statePath('employeeData');
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

    public function pinForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Code PIN')
                    ->description('Le code PIN est demandé pour signer les états des lieux de votre véhicule (4 chiffres).')
                    ->schema([
                        TextInput::make('current_pin')
                            ->label('Code PIN actuel')
                            ->password()
                            ->maxLength(4)
                            ->required(fn (): bool => (bool) auth()->user()->salarie?->pin_hash)
                            ->rule(function () {
                                return function (string $attribute, $value, \Closure $fail) {
                                    $employee = auth()->user()->salarie;

                                    if ($employee?->pin_hash && ! app(EmployeePinService::class)->checkPin($employee, $value)) {
                                        $fail('Le code PIN actuel est incorrect.');
                                    }
                                };
                            }),
                        TextInput::make('pin')
                            ->label('Nouveau code PIN')
                            ->password()
                            ->required()
                            ->maxLength(4)
                            ->confirmed()
                            ->rule('regex:/^[0-9]{4}$/'),
                        TextInput::make('pin_confirmation')
                            ->label('Confirmer le nouveau code PIN')
                            ->password()
                            ->required(),
                    ])
                    ->columns(1),
            ])
            ->statePath('pinData');
    }

    public function saveEmployeeData(): void
    {
        $data = $this->employeeForm->getState();
        $employee = auth()->user()->salarie;

        if ($employee) {
            $employee->update($data);
            auth()->user()->email = $data['email'];

            Notification::make()
                ->title('Profil mis à jour avec succès.')
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

    public function savePinData(): void
    {
        $data = $this->pinForm->getState();

        $employee = auth()->user()->salarie;

        if (! $employee) {
            return;
        }

        app(EmployeePinService::class)->setPin($employee, $data['pin']);

        $this->pinForm->fill();

        Notification::make()
            ->title('Code PIN mis à jour avec succès.')
            ->success()
            ->send();
    }
}
