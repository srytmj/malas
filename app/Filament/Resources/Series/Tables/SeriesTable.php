<?php

namespace App\Filament\Resources\Series\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SeriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title_romaji')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('title_english')
                    ->label('Inggris')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
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
                TextColumn::make('volumes_count')
                    ->label('Volume')
                    ->counts('volumes')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('score')
                    ->label('Skor')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('mal_id')
                    ->label('MAL ID')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'publishing' => 'Terbit',
                        'finished' => 'Selesai',
                        'on_hiatus' => 'Hiatus',
                        'discontinued' => 'Dihentikan',
                        'not_yet_published' => 'Belum Terbit',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('title_romaji');
    }
}
