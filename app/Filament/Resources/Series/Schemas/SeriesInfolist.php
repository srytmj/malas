<?php

namespace App\Filament\Resources\Series\Schemas;

use App\Models\Series;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SeriesInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cover')
                    ->schema([
                        ImageEntry::make('cover_path')
                            ->label('')
                            ->disk(config('filesystems.cover_disk', 'public'))
                            ->height(300)
                            ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                            ->hidden(fn (Series $record): bool => blank($record->cover_path)),
                    ])
                    ->hidden(fn (Series $record): bool => blank($record->cover_path)),

                Section::make('Informasi Utama')
                    ->schema([
                        TextEntry::make('mal_id')
                            ->label('MAL ID')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('title_romaji')
                            ->label('Judul (Romaji)'),
                        TextEntry::make('title_english')
                            ->label('Judul (Inggris)')
                            ->placeholder('-'),
                        TextEntry::make('title_japanese')
                            ->label('Judul (Jepang)')
                            ->placeholder('-'),
                        TextEntry::make('synopsis')
                            ->label('Sinopsis')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Detail Penerbitan')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'publishing' => 'success',
                                'finished' => 'info',
                                'on_hiatus' => 'warning',
                                'discontinued' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'publishing' => 'Terbit',
                                'finished' => 'Selesai',
                                'on_hiatus' => 'Hiatus',
                                'discontinued' => 'Dihentikan',
                                'not_yet_published' => 'Belum Terbit',
                                default => $state,
                            }),
                        TextEntry::make('total_volumes')
                            ->label('Total Volume')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('published_from')
                            ->label('Mulai Terbit')
                            ->date('d M Y')
                            ->placeholder('-'),
                        TextEntry::make('published_to')
                            ->label('Selesai Terbit')
                            ->date('d M Y')
                            ->placeholder('-'),
                        TextEntry::make('score')
                            ->label('Skor MAL')
                            ->numeric(decimalPlaces: 2)
                            ->placeholder('-'),
                        TextEntry::make('rank')
                            ->label('Rank MAL')
                            ->numeric()
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Section::make('Metadata')
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d M Y, H:i'),
                        TextEntry::make('updated_at')
                            ->label('Diperbarui')
                            ->dateTime('d M Y, H:i'),
                        TextEntry::make('deleted_at')
                            ->label('Dihapus')
                            ->dateTime('d M Y, H:i')
                            ->visible(fn (Series $record): bool => $record->trashed()),
                        TextEntry::make('deleted_reason')
                            ->label('Alasan Hapus')
                            ->placeholder('-')
                            ->columnSpanFull()
                            ->visible(fn (Series $record): bool => $record->trashed()),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
