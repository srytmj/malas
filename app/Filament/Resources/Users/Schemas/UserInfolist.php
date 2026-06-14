<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Akun')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama'),
                        TextEntry::make('email')
                            ->label('Email'),
                        TextEntry::make('role')
                            ->label('Peran')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'super_admin' => 'danger',
                                'admin' => 'warning',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'super_admin' => 'Super Admin',
                                'admin' => 'Admin',
                                default => 'User',
                            }),
                        TextEntry::make('email_verified_at')
                            ->label('Verifikasi Email')
                            ->dateTime('d M Y')
                            ->placeholder('Belum diverifikasi'),
                    ])
                    ->columns(2),

                Section::make('Status Ban')
                    ->schema([
                        IconEntry::make('is_banned')
                            ->label('Di-ban')
                            ->boolean()
                            ->trueColor('danger')
                            ->falseColor('success'),
                        TextEntry::make('banned_at')
                            ->label('Waktu Ban')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('-'),
                        TextEntry::make('ban_reason')
                            ->label('Alasan Ban')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn (User $record): bool => $record->is_banned),

                TextEntry::make('created_at')
                    ->label('Bergabung')
                    ->date('d M Y'),
            ]);
    }
}
