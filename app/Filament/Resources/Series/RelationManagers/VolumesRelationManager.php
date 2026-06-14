<?php

namespace App\Filament\Resources\Series\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VolumesRelationManager extends RelationManager
{
    protected static string $relationship = 'volumes';

    protected static ?string $title = 'Volume';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('volume_number')
            ->columns([
                TextColumn::make('volume_number')
                    ->label('Vol.')
                    ->sortable()
                    ->numeric(),
                TextColumn::make('isbn')
                    ->label('ISBN')
                    ->searchable(),
                TextColumn::make('published_at')
                    ->label('Terbit')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('collections_count')
                    ->label('Koleksi')
                    ->counts('collections')
                    ->badge()
                    ->color('gray'),
            ])
            ->defaultSort('volume_number')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
