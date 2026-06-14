<?php

namespace App\Filament\Resources\Series\Schemas;

use App\Models\Series;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SeriesInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('mal_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('title_romaji'),
                TextEntry::make('title_english')
                    ->placeholder('-'),
                TextEntry::make('title_japanese')
                    ->placeholder('-'),
                TextEntry::make('synopsis')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('cover_path')
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('published_from')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('published_to')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('total_volumes')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('score')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('rank')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Series $record): bool => $record->trashed()),
                TextEntry::make('deleted_reason')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('deleted_by')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
