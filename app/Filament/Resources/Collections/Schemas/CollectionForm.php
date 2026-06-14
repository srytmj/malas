<?php

namespace App\Filament\Resources\Collections\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CollectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Pemilik')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('volume_id')
                    ->label('Volume')
                    ->relationship('volume', 'volume_number', fn ($query) => $query->with('series'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->series->title_romaji} Vol. {$record->volume_number}")
                    ->searchable()
                    ->required(),
                Select::make('condition')
                    ->label('Kondisi')
                    ->options([
                        'mint' => 'Mint',
                        'good' => 'Good',
                        'fair' => 'Fair',
                        'poor' => 'Poor',
                    ])
                    ->default('good')
                    ->required(),
                TextInput::make('location')
                    ->label('Lokasi Rak')
                    ->maxLength(255)
                    ->placeholder('Contoh: Rak A Baris 2'),
                DatePicker::make('acquired_at')
                    ->label('Tanggal Diperoleh'),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
