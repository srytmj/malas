<?php

namespace App\Filament\Resources\Loans\Tables;

use App\Models\Loan;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class LoansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('borrower_name')
                    ->label('Peminjam')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('collection.volume.series.title_romaji')
                    ->label('Series')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('collection.volume.volume_number')
                    ->label('Vol.')
                    ->numeric(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'active' => 'success',
                        'returned' => 'info',
                        'overdue' => 'warning',
                        'lost' => 'danger',
                        'cancelled' => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'active' => 'Dipinjam',
                        'returned' => 'Dikembalikan',
                        'overdue' => 'Terlambat',
                        'lost' => 'Hilang',
                        'cancelled' => 'Dibatalkan',
                        default => $state,
                    }),
                TextColumn::make('due_date')
                    ->label('Tenggat')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('loaned_at')
                    ->label('Dipinjam')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('returned_at')
                    ->label('Dikembalikan')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu',
                        'active' => 'Dipinjam',
                        'returned' => 'Dikembalikan',
                        'overdue' => 'Terlambat',
                        'lost' => 'Hilang',
                        'cancelled' => 'Dibatalkan',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('activate')
                    ->label('Aktivasi')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (Loan $record): bool => $record->status === 'pending')
                    ->action(fn (Loan $record) => $record->update([
                        'status' => 'active',
                        'loaned_at' => now(),
                    ]))
                    ->requiresConfirmation()
                    ->modalHeading('Aktivasi Peminjaman')
                    ->modalDescription('Volume akan ditandai sebagai dipinjam sekarang.'),
                Action::make('return')
                    ->label('Kembalikan')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('info')
                    ->visible(fn (Loan $record): bool => in_array($record->status, ['active', 'overdue']))
                    ->action(fn (Loan $record) => $record->update([
                        'status' => 'returned',
                        'returned_at' => now(),
                    ]))
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pengembalian'),
                Action::make('markLost')
                    ->label('Hilang')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->color('danger')
                    ->visible(fn (Loan $record): bool => in_array($record->status, ['active', 'overdue']))
                    ->action(fn (Loan $record) => $record->update(['status' => 'lost']))
                    ->requiresConfirmation()
                    ->modalHeading('Tandai sebagai Hilang'),
                Action::make('cancel')
                    ->label('Batalkan')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('gray')
                    ->visible(fn (Loan $record): bool => $record->status === 'pending')
                    ->action(fn (Loan $record) => $record->update(['status' => 'cancelled']))
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Peminjaman'),
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
            ->defaultSort('due_date');
    }
}
