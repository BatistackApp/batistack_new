<?php

namespace App\Livewire\Onboarding;

use App\Models\RH\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Component;

class CandidateForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];
    public Employee $employee;
    public bool $isCompleted = false;

    public function mount(string $uuid): void
    {
        $this->employee = Employee::where('uuid', $uuid)->firstOrFail();

        if ($this->employee->onboarding_completed) {
            $this->isCompleted = true;
            return;
        }

        $this->form->fill([
            'first_name' => $this->employee->first_name,
            'last_name' => $this->employee->last_name,
            'email' => $this->employee->email,
            'phone' => $this->employee->phone,
            'address' => $this->employee->address,
            'postal_code' => $this->employee->postal_code,
            'city' => $this->employee->city,
            'birth_date' => $this->employee->birth_date,
            'social_security_number' => $this->employee->social_security_number,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informations Personnelles')
                    ->description('Veuillez vérifier et compléter vos informations de contact.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('first_name')
                                ->label('Prénom')
                                ->required(),
                            TextInput::make('last_name')
                                ->label('Nom')
                                ->required(),
                            TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->required(),
                            TextInput::make('phone')
                                ->label('Téléphone')
                                ->tel()
                                ->required(),
                            DatePicker::make('birth_date')
                                ->label('Date de naissance')
                                ->required(),
                            TextInput::make('social_security_number')
                                ->label('Numéro de Sécurité Sociale')
                                ->required(),
                        ]),
                        Textarea::make('address')
                            ->label('Adresse postale')
                            ->required()
                            ->columnSpanFull(),
                        Grid::make(2)->schema([
                            TextInput::make('postal_code')
                                ->label('Code Postal')
                                ->required(),
                            TextInput::make('city')
                                ->label('Ville')
                                ->required(),
                        ]),
                    ]),

                Section::make('Documents Administratifs')
                    ->description('Veuillez fournir une copie de votre pièce d\'identité, de votre attestation de Sécurité Sociale et de votre RIB. Vous pouvez déposer plusieurs fichiers.')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('id_docs')
                            ->label('Pièce d\'identité, Attestation Sécu, RIB')
                            ->collection('identity_docs')
                            ->multiple()
                            ->required(),
                    ]),
            ])
            ->statePath('data')
            ->model($this->employee);
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $this->employee->update($data);
        $this->employee->update(['onboarding_completed' => true]);

        $this->isCompleted = true;
    }

    public function render()
    {
        return view('livewire.onboarding.candidate-form')->layout('layouts.public');
    }
}
