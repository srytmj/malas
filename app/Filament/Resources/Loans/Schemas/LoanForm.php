<?php

namespace App\Filament\Resources\Loans\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class LoanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Peminjaman')
                    ->schema([
                        Select::make('collection_id')
                            ->label('Volume (Koleksi)')
                            ->relationship('collection', 'id', fn ($query) => $query->with('volume.series'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->volume->series->title_romaji} Vol. {$record->volume->volume_number} — {$record->user->name}")
                            ->searchable()
                            ->required(),
                        Select::make('borrower_user_id')
                            ->label('Akun Peminjam')
                            ->relationship('borrower', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Opsional — isi jika peminjam punya akun di MALAS'),
                        TextInput::make('borrower_name')
                            ->label('Nama Peminjam')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('borrower_contact')
                            ->label('Kontak Peminjam')
                            ->maxLength(255)
                            ->placeholder('WhatsApp / email / telepon'),
                    ])
                    ->columns(2),

                Section::make('Jadwal & Status')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Menunggu',
                                'active' => 'Dipinjam',
                                'returned' => 'Dikembalikan',
                                'overdue' => 'Terlambat',
                                'lost' => 'Hilang',
                                'cancelled' => 'Dibatalkan',
                            ])
                            ->default('pending')
                            ->required(),
                        DatePicker::make('due_date')
                            ->label('Tenggat Kembali')
                            ->required(),
                        DateTimePicker::make('loaned_at')
                            ->label('Waktu Dipinjam')
                            ->helperText('Diisi otomatis saat status → aktif'),
                        DateTimePicker::make('returned_at')
                            ->label('Waktu Dikembalikan')
                            ->helperText('Diisi otomatis saat status → dikembalikan'),
                    ])
                    ->columns(2),

                Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
