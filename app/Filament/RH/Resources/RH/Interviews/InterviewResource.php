<?php

namespace App\Filament\RH\Resources\RH\Interviews;

use App\Filament\RH\Resources\RH\Interviews\Pages\CreateInterview;
use App\Filament\RH\Resources\RH\Interviews\Pages\EditInterview;
use App\Filament\RH\Resources\RH\Interviews\Pages\ListInterviews;
use App\Filament\RH\Resources\RH\Interviews\Schemas\InterviewForm;
use App\Filament\RH\Resources\RH\Interviews\Tables\InterviewsTable;
use App\Models\RH\Interview;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InterviewResource extends Resource
{
    protected static ?string $model = Interview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return InterviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InterviewsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInterviews::route('/'),
            'create' => CreateInterview::route('/create'),
            'edit' => EditInterview::route('/{record}/edit'),
        ];
    }
}
