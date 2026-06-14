<?php

namespace App\Filament\Resources\Series\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SeriesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Utama')
                    ->schema([
                        TextInput::make('title_romaji')
                            ->label('Judul (Romaji)')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        TextInput::make('title_english')
                            ->label('Judul (Inggris)')
                            ->maxLength(255),
                        TextInput::make('title_japanese')
                            ->label('Judul (Jepang)')
                            ->maxLength(255),
                        TextInput::make('mal_id')
                            ->label('MAL ID')
                            ->numeric()
                            ->unique(ignoreRecord: true)
                            ->helperText('ID manga di MyAnimeList (opsional)'),
                        Textarea::make('synopsis')
                            ->label('Sinopsis')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Detail Penerbitan')
                    ->schema([
                        Select::make('status')
                            ->label('Status Terbit')
                            ->options([
                                'publishing' => 'Terbit',
                                'finished' => 'Selesai',
                                'on_hiatus' => 'Hiatus',
                                'discontinued' => 'Dihentikan',
                                'not_yet_published' => 'Belum Terbit',
                            ])
                            ->default('publishing')
                            ->required(),
                        TextInput::make('total_volumes')
                            ->label('Total Volume')
                            ->numeric()
                            ->minValue(1),
                        DatePicker::make('published_from')
                            ->label('Mulai Terbit'),
                        DatePicker::make('published_to')
                            ->label('Selesai Terbit'),
                        TextInput::make('score')
                            ->label('Skor MAL')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.01),
                        TextInput::make('rank')
                            ->label('Rank MAL')
                            ->numeric()
                            ->minValue(1),
                    ])
                    ->columns(2),
            ]);
    }
}
