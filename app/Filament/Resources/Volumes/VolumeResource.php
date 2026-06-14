<?php

namespace App\Filament\Resources\Volumes;

use App\Filament\Resources\Volumes\Pages\CreateVolume;
use App\Filament\Resources\Volumes\Pages\EditVolume;
use App\Filament\Resources\Volumes\Pages\ListVolumes;
use App\Filament\Resources\Volumes\Schemas\VolumeForm;
use App\Filament\Resources\Volumes\Tables\VolumesTable;
use App\Models\Volume;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VolumeResource extends Resource
{
    protected static ?string $model = Volume::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookmark;

    protected static \UnitEnum|string|null $navigationGroup = 'Koleksi';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'volume_number';

    protected static ?string $modelLabel = 'Volume';

    protected static ?string $pluralModelLabel = 'Volume';

    public static function form(Schema $schema): Schema
    {
        return VolumeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VolumesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVolumes::route('/'),
            'create' => CreateVolume::route('/create'),
            'edit' => EditVolume::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
