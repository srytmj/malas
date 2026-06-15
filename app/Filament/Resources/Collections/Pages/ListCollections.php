<?php

namespace App\Filament\Resources\Collections\Pages;

use App\Filament\Resources\Collections\CollectionResource;
use App\Models\User;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListCollections extends ListRecords
{
    protected static string $resource = CollectionResource::class;

    public function getTitle(): string
    {
        return 'Koleksi per Pemilik';
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Pemilik')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('series_count')
                    ->label('Total Series')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('total_volumes_owned')
                    ->label('Volume Dimiliki')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(fn (User $record): int =>
                        $record->ownedCollections->sum(fn ($c) => $c->ownedVolumes->count())
                    ),
                TextColumn::make('estimated_worth')
                    ->label('Est. Worth')
                    ->getStateUsing(fn (User $record): string =>
                        'Rp ' . number_format(
                            $record->ownedCollections->sum(fn ($c) => $c->getEstimatedWorth()),
                            0, ',', '.'
                        )
                    ),
            ])
            ->recordActions([
                ViewAction::make()->label('Lihat Koleksi'),
            ])
            ->defaultSort('name');
    }
}
