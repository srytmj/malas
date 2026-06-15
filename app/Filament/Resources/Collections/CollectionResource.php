<?php

namespace App\Filament\Resources\Collections;

use App\Filament\Resources\Collections\Pages\ListCollections;
use App\Filament\Resources\Collections\Pages\ViewUserCollection;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CollectionResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static \UnitEnum|string|null $navigationGroup = 'Koleksi';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Koleksi';

    protected static ?string $pluralModelLabel = 'Koleksi';

    protected static ?string $slug = 'collections';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table;
    }

    public static function getEloquentQuery(): Builder
    {
        return User::query()
            ->whereHas('ownedCollections')
            ->withCount('ownedCollections as series_count')
            ->with(['ownedCollections.series', 'ownedCollections.ownedVolumes']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCollections::route('/'),
            'view' => ViewUserCollection::route('/{record}'),
        ];
    }
}
