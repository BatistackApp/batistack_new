<?php

namespace App\Filament\RH\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use App\Models\Paie\PayrollContributionProfile;
use App\Services\Paie\PayrollSimulatorService;
use BackedEnum;

class PayrollSimulator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Simulateur Brut/Net';
    protected static ?string $title = 'Simulateur de Paie';
    protected static \UnitEnum|string|null $navigationGroup = 'Outils & Simulation';
    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.r-h.pages.payroll-simulator';

    public ?array $data = [];
    public ?array $simulationResult = null;

    public function mount(): void
    {
        $this->form->fill([
            'direction' => 'gross_to_net',
            'amount' => 2500,
        ]);
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                Section::make('Paramètres de la simulation')
                    ->schema([
                        Select::make('profile_id')
                            ->label('Profil de cotisations')
                            ->options(PayrollContributionProfile::all()->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        
                        Radio::make('direction')
                            ->label('Sens du calcul')
                            ->options([
                                'gross_to_net' => 'Brut vers Net',
                                'net_to_gross' => 'Net (Social) vers Brut',
                            ])
                            ->inline()
                            ->required(),
                        
                        TextInput::make('amount')
                            ->label('Montant (€)')
                            ->numeric()
                            ->required(),
                    ])->columns(3),
            ])
            ->statePath('data');
    }

    public function simulate(): void
    {
        $data = $this->form->getState();
        $profile = PayrollContributionProfile::find($data['profile_id']);
        
        if (!$profile) {
            return;
        }

        $amount = (float) $data['amount'];
        $simulator = new PayrollSimulatorService();

        if ($data['direction'] === 'gross_to_net') {
            $this->simulationResult = $simulator->simulateFromGross($amount, $profile);
        } else {
            $this->simulationResult = $simulator->simulateFromNet($amount, $profile);
        }
    }
}
