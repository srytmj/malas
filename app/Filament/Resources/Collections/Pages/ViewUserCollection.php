<?php

namespace App\Filament\Resources\Collections\Pages;

use App\Filament\Resources\Collections\CollectionResource;
use App\Models\Collection;
use App\Models\Edition;
use App\Models\Series;
use App\Models\User;
use App\Models\Volume;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ViewUserCollection extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CollectionResource::class;

    protected string $view = 'filament.resources.collections.pages.view-user-collection';

    public User $record;

    public function mount(User $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string
    {
        return "Koleksi: {$this->record->name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addManga')
                ->label('Tambah Manga')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->form($this->collectionEntryForm())
                ->action(function (array $data): void {
                    $collection = Collection::create([
                        'series_id'        => $data['series_id'],
                        'edition_id'       => $data['edition_id'] ?? null,
                        'condition'        => $data['condition'],
                        'price_per_volume' => $data['price_per_volume'] ?? null,
                        'location'         => $data['location'] ?? null,
                        'acquired_at'      => $data['acquired_at'] ?? null,
                        'notes'            => $data['notes'] ?? null,
                    ]);

                    $ownerIds = array_unique(array_merge(
                        [$this->record->id],
                        $data['additional_owner_ids'] ?? []
                    ));
                    $collection->owners()->sync($ownerIds);

                    $this->syncVolumes($collection, $data['series_id'], $data['owned_volume_ids'] ?? []);

                    Notification::make()->title('Manga ditambahkan')->success()->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Collection::query()
                    ->whereHas('owners', fn ($q) => $q->where('users.id', $this->record->id))
                    ->with(['series', 'edition', 'ownedVolumes', 'owners'])
            )
            ->columns([
                TextColumn::make('series.title_romaji')
                    ->label('Series')
                    ->searchable()
                    ->sortable()
                    ->limit(35),
                TextColumn::make('edition.name')
                    ->label('Edisi')
                    ->placeholder('—')
                    ->limit(25),
                TextColumn::make('volumes_summary')
                    ->label('Volume')
                    ->getStateUsing(fn (Collection $r): string =>
                        $r->ownedVolumes->count() . ' / ' . ($r->series?->total_volumes ?? '?')
                    )
                    ->badge()
                    ->color('success'),
                TextColumn::make('condition')
                    ->label('Kondisi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mint' => 'success',
                        'good' => 'info',
                        'fair' => 'warning',
                        'poor' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('location')
                    ->label('Lokasi')
                    ->placeholder('—'),
                TextColumn::make('worth')
                    ->label('Est. Worth')
                    ->getStateUsing(fn (Collection $r): string =>
                        'Rp ' . number_format($r->getEstimatedWorth(), 0, ',', '.')
                    ),
                TextColumn::make('owners_list')
                    ->label('Pemilik')
                    ->getStateUsing(fn (Collection $r): string =>
                        $r->owners->pluck('name')->implode(', ')
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->fillForm(fn (Collection $record): array => [
                        'edition_id'           => $record->edition_id,
                        'condition'            => $record->condition,
                        'price_per_volume'     => $record->price_per_volume,
                        'location'             => $record->location,
                        'acquired_at'          => $record->acquired_at,
                        'notes'                => $record->notes,
                        'owned_volume_ids'     => $record->ownedVolumes()->pluck('volumes.id')->toArray(),
                        'additional_owner_ids' => $record->owners->where('id', '!=', $this->record->id)->pluck('id')->toArray(),
                    ])
                    ->form($this->collectionEntryEditForm())
                    ->action(function (array $data, Collection $record): void {
                        $record->update([
                            'edition_id'       => $data['edition_id'] ?? null,
                            'condition'        => $data['condition'],
                            'price_per_volume' => $data['price_per_volume'] ?? null,
                            'location'         => $data['location'] ?? null,
                            'acquired_at'      => $data['acquired_at'] ?? null,
                            'notes'            => $data['notes'] ?? null,
                        ]);
                        $ownerIds = array_unique(array_merge(
                            [$this->record->id],
                            $data['additional_owner_ids'] ?? []
                        ));
                        $record->owners()->sync($ownerIds);
                        $this->syncVolumes($record, $record->series_id, $data['owned_volume_ids'] ?? []);
                        Notification::make()->title('Koleksi diperbarui')->success()->send();
                    }),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ]);
    }

    private function collectionEntryForm(): array
    {
        return [
            Select::make('series_id')
                ->label('Series')
                ->options(Series::query()->orderBy('title_romaji')->pluck('title_romaji', 'id'))
                ->searchable()
                ->preload()
                ->required()
                ->live(),
            Select::make('edition_id')
                ->label('Edisi')
                ->options(Edition::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->nullable()
                ->placeholder('— Pilih edisi —'),
            CheckboxList::make('owned_volume_ids')
                ->label('Volume yang Dimiliki')
                ->options(fn (callable $get) => $this->volumeOptions($get('series_id')))
                ->live()
                ->columns(5)
                ->gridDirection('row'),
            Select::make('condition')
                ->label('Kondisi')
                ->options(['mint' => 'Mint', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor'])
                ->default('good')
                ->required(),
            TextInput::make('price_per_volume')
                ->label('Harga / Volume (Rp)')
                ->numeric()
                ->prefix('Rp')
                ->helperText('Kosongkan untuk pakai harga referensi series'),
            TextInput::make('location')
                ->label('Lokasi Rak')
                ->placeholder('Rak A Baris 2'),
            DatePicker::make('acquired_at')
                ->label('Tanggal Diperoleh'),
            Select::make('additional_owner_ids')
                ->label('Pemilik Tambahan')
                ->multiple()
                ->options(
                    User::query()
                        ->where('id', '!=', $this->record->id)
                        ->pluck('name', 'id')
                )
                ->searchable()
                ->preload(),
            Textarea::make('notes')
                ->label('Catatan')
                ->rows(2)
                ->columnSpanFull(),
        ];
    }

    private function collectionEntryEditForm(): array
    {
        return array_values(array_filter(
            $this->collectionEntryForm(),
            fn ($c) => method_exists($c, 'getName') && $c->getName() !== 'series_id',
        ));
    }

    private function volumeOptions(?string $seriesId): array
    {
        if (! $seriesId) {
            return [];
        }

        return Volume::where('series_id', $seriesId)
            ->orderBy('volume_number')
            ->pluck('volume_number', 'id')
            ->map(fn ($num) => "Vol. {$num}")
            ->toArray();
    }

    private function syncVolumes(Collection $collection, string $seriesId, array $ownedIds): void
    {
        $allVolumes = Volume::where('series_id', $seriesId)->pluck('id')->toArray();
        $pivotData = [];
        foreach ($allVolumes as $volId) {
            $pivotData[$volId] = ['is_owned' => in_array($volId, $ownedIds, true)];
        }
        $collection->volumes()->sync($pivotData);
    }
}
