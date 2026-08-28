<?php

namespace App\Filament\Customer\Pages;

use App\Models\Tiers\ThirdParty;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CustomerProfile extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = 'Mon Profil';

    protected static ?string $title = 'Mon Espace';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.customer.pages.customer-profile';

    public ?array $passwordData = [];

    public ?array $thirdParty = null;

    public array $contacts = [];

    public array $addresses = [];

    public function mount(): void
    {
        $tp = $this->getThirdParty();

        if ($tp) {
            $this->thirdParty = $tp->toArray();
            $this->contacts = $tp->contacts()->active()->get()->toArray();
            $this->addresses = $tp->addresses()->get()->toArray();
        }
    }

    protected function getForms(): array
    {
        return [
            'passwordForm',
        ];
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
