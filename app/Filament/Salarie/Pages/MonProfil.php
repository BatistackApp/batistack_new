<?php

namespace App\Filament\Salarie\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class MonProfil extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationLabel = 'Mon Profil';
    protected static ?string $title = 'Mon Profil';
    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.salarie.pages.mon-profil';

    public ?array $employeeData = [];
    public ?array $passwordData = [];

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
            ]);
        }
    }

    protected function getForms(): array
    {
        return [
            'employeeForm',
            'passwordForm',
        ];
    }

    public function employeeForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations de contact')
                    ->description('Mettez à jour vos informations personnelles.')
                    ->schema([
                        TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('address')
                            ->label('Adresse postale')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('postal_code')
                            ->label('Code postal')
                            ->maxLength(255),
                        TextInput::make('city')
                            ->label('Ville')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ])
            ->statePath('employeeData');
    }

    public function passwordForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Mot de passe')
                    ->description('Assurez-vous que votre compte utilise un mot de passe long et aléatoire.')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Mot de passe actuel')
                            ->password()
                            ->required()
                            ->currentPassword(),
                        TextInput::make('password')
                            ->label('Nouveau mot de passe')
                            ->password()
                            ->required()
                            ->rule(Password::default())
                            ->confirmed(),
                        TextInput::make('password_confirmation')
                            ->label('Confirmer le mot de passe')
                            ->password()
                            ->required(),
                    ])
                    ->columns(1),
            ])
            ->statePath('passwordData');
    }

    public function saveEmployeeData(): void
    {
        $data = $this->employeeForm->getState();
        $employee = auth()->user()->salarie;

        if ($employee) {
            $employee->update($data);
            
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
}
