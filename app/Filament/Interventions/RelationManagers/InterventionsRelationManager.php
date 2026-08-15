<?php

namespace App\Filament\Interventions\RelationManagers;

use App\Filament\Interventions\Tables\InterventionsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class InterventionsRelationManager extends RelationManager
{
    protected static string $relationship = 'interventions';

    public function table(Table $table): Table
    {
        return InterventionsTable::configure($table);
    }
}
