<?php

namespace App\Filament\Resources\Volumes\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VolumeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('series_id')
                    ->label('Series')
                    ->relationship('series', 'title_romaji')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('volume_number')
                    ->label('Nomor Volume')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                TextInput::make('isbn')
                    ->label('ISBN')
                    ->maxLength(20),
                DatePicker::make('published_at')
                    ->label('Tanggal Terbit'),
            ]);
    }
}
