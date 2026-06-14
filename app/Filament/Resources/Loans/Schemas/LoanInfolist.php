<?php

namespace App\Filament\Resources\Loans\Schemas;

use App\Models\Loan;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LoanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Peminjaman')
                    ->schema([
                        TextEntry::make('collection.volume.series.title_romaji')
                            ->label('Series'),
                        TextEntry::make('collection.volume.volume_number')
                            ->label('Volume')
                            ->numeric(),
                        TextEntry::make('borrower_name')
                            ->label('Peminjam'),
                        TextEntry::make('borrower_contact')
                            ->label('Kontak')
                            ->placeholder('-'),
                        TextEntry::make('status')
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
                        TextEntry::make('due_date')
                            ->label('Tenggat')
                            ->date('d M Y'),
                    ])
                    ->columns(2),

                Section::make('Waktu')
                    ->schema([
                        TextEntry::make('loaned_at')
                            ->label('Dipinjam')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('-'),
                        TextEntry::make('returned_at')
                            ->label('Dikembalikan')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                TextEntry::make('notes')
                    ->label('Catatan')
                    ->placeholder('-')
                    ->columnSpanFull(),
            ]);
    }
}
