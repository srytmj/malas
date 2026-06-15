<?php

namespace App\Filament\Pages;

use App\Models\Edition;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class Settings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static \UnitEnum|string|null   $navigationGroup = 'Alat';
    protected static \BackedEnum|string|null $navigationIcon  = Heroicon::OutlinedCog6Tooth;
    protected static ?string $navigationLabel = 'Pengaturan';
    protected static ?int    $navigationSort  = 2;

    public function getTitle(): string
    {
        return 'Pengaturan';
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Edition::query()->orderBy('language')->orderBy('name'))
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Edisi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('language')
                    ->label('Bahasa')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'id' => 'success',
                        'en' => 'info',
                        'ja' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('publisher')
                    ->label('Penerbit')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('collections_count')
                    ->label('Dipakai')
                    ->counts('collections')
                    ->badge()
                    ->color('gray'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->model(Edition::class)
                    ->label('Tambah Edisi')
                    ->form($this->editionFormSchema()),
            ])
            ->recordActions([
                EditAction::make()
                    ->form($this->editionFormSchema()),
                DeleteAction::make()
                    ->before(function (Edition $record) {
                        if ($record->collections()->exists()) {
                            $this->halt();
                            Notification::make()
                                ->title('Tidak bisa dihapus')
                                ->body('Edisi ini masih dipakai oleh koleksi.')
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    private function editionFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Nama Edisi')
                ->placeholder('Bahasa Indonesia — Elex Media')
                ->required()
                ->maxLength(255),
            Select::make('language')
                ->label('Bahasa')
                ->options([
                    'id' => 'Bahasa Indonesia (id)',
                    'en' => 'English (en)',
                    'ja' => '日本語 (ja)',
                    'zh' => 'Chinese (zh)',
                    'ko' => 'Korean (ko)',
                    'fr' => 'Français (fr)',
                    'de' => 'Deutsch (de)',
                    'es' => 'Español (es)',
                ])
                ->required()
                ->searchable(),
            TextInput::make('publisher')
                ->label('Penerbit')
                ->placeholder('Elex Media, Viz Media, dst.')
                ->maxLength(255),
        ];
    }
}
