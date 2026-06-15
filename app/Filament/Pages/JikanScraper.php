<?php

namespace App\Filament\Pages;

use App\Actions\ImportSeriesFromJikan;
use App\Services\JikanService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class JikanScraper extends Page implements HasTable
{
    use InteractsWithTable;

    protected static \UnitEnum|string|null   $navigationGroup = 'Alat';
    protected static \BackedEnum|string|null $navigationIcon  = Heroicon::OutlinedCloudArrowDown;
    protected static ?string $navigationLabel = 'Jikan Scraper';
    protected static ?int    $navigationSort  = 1;
    protected string $view = 'filament.pages.jikan-scraper';

    // ── Active tab ───────────────────────────────────────────────────────────
    public string $activeTab = 'search';

    // ── Search tab ───────────────────────────────────────────────────────────
    public string  $searchQuery      = '';
    public array   $searchResults    = [];
    public ?array  $searchPagination = null;
    public int     $searchPage       = 1;

    public bool    $showAdvancedSearch = false;
    public ?string $searchStatus       = null;
    public ?string $searchType         = null;
    public string  $searchOrderBy      = 'popularity';
    public string  $searchSort         = 'asc';
    public string  $searchMinScore     = '';
    public string  $searchMaxScore     = '';
    public string  $searchStartYear    = '';
    public string  $searchEndYear      = '';

    // ── Year tab ─────────────────────────────────────────────────────────────
    public int     $scrapeYear       = 0;
    public array   $scrapeResults    = [];
    public ?array  $scrapePagination = null;
    public int     $scrapePage       = 0;

    public ?string $scrapeStatus   = null;
    public ?string $scrapeType     = null;
    public string  $scrapeOrderBy  = 'start_date';
    public string  $scrapeSort     = 'asc';

    // ── Table-level instant filters (no API call) ────────────────────────────
    public ?string $tableTypeFilter   = null;
    public ?string $tableStatusFilter = null;
    public string  $tableClientFilter = '';

    public function mount(): void
    {
        $this->scrapeYear = (int) date('Y');
    }

    public function getTitle(): string { return 'Jikan Scraper'; }

    // ── Tab switch → reset instant filters ──────────────────────────────────
    public function updatedActiveTab(): void
    {
        $this->tableTypeFilter   = null;
        $this->tableStatusFilter = null;
        $this->tableClientFilter = '';
    }

    // ── Schema content ───────────────────────────────────────────────────────
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->livewireProperty('activeTab')
                ->tabs([
                    'search' => Tab::make('Cari Judul')
                        ->icon('heroicon-o-magnifying-glass')
                        ->schema([
                            Section::make()->compact()->schema([
                                TextInput::make('searchQuery')
                                    ->label('')
                                    ->placeholder('Ketik judul manga, misal: Berserk, One Piece…')
                                    ->required()
                                    ->minLength(2),
                                Section::make('Filter Lanjutan')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Grid::make(['default' => 2, 'sm' => 4])->schema([
                                            Select::make('searchStatus')
                                                ->label('Status')
                                                ->placeholder('Semua Status')
                                                ->options([
                                                    'publishing'   => 'Publishing',
                                                    'complete'     => 'Complete',
                                                    'hiatus'       => 'On Hiatus',
                                                    'discontinued' => 'Discontinued',
                                                    'upcoming'     => 'Upcoming',
                                                ]),
                                            Select::make('searchType')
                                                ->label('Tipe')
                                                ->placeholder('Semua Tipe')
                                                ->options([
                                                    'manga'      => 'Manga',
                                                    'manhwa'     => 'Manhwa',
                                                    'manhua'     => 'Manhua',
                                                    'novel'      => 'Novel',
                                                    'lightnovel' => 'Light Novel',
                                                    'oneshot'    => 'One-shot',
                                                    'doujin'     => 'Doujin',
                                                ]),
                                            Select::make('searchOrderBy')
                                                ->label('Urutkan')
                                                ->options(static::orderByOptions()),
                                            Select::make('searchSort')
                                                ->label('Urutan')
                                                ->options(['asc' => 'Ascending ↑', 'desc' => 'Descending ↓']),
                                            TextInput::make('searchMinScore')
                                                ->label('Skor Min')
                                                ->numeric()->minValue(1)->maxValue(10)->step(0.1)
                                                ->placeholder('1.0'),
                                            TextInput::make('searchMaxScore')
                                                ->label('Skor Maks')
                                                ->numeric()->minValue(1)->maxValue(10)->step(0.1)
                                                ->placeholder('10.0'),
                                            TextInput::make('searchStartYear')
                                                ->label('Tahun Mulai')
                                                ->numeric()->minValue(1900)->maxValue((int) date('Y'))
                                                ->placeholder('1900'),
                                            TextInput::make('searchEndYear')
                                                ->label('Tahun Akhir')
                                                ->numeric()->minValue(1900)->maxValue((int) date('Y'))
                                                ->placeholder((string) date('Y')),
                                        ]),
                                        Actions::make([
                                            Action::make('resetSearchApiFilters')
                                                ->label('Reset filter')
                                                ->color('gray')
                                                ->size('sm')
                                                ->action(fn () => $this->resetSearchApiFilters()),
                                        ])->alignEnd(),
                                    ]),
                                Actions::make([
                                    Action::make('search')
                                        ->label('Cari')
                                        ->icon('heroicon-o-magnifying-glass')
                                        ->action(fn () => $this->search()),
                                ]),
                            ]),
                        ]),
                    'year' => Tab::make('Browse Tahun')
                        ->icon('heroicon-o-calendar-days')
                        ->schema([
                            Section::make()->compact()->schema([
                                Grid::make(['default' => 2, 'sm' => 3, 'lg' => 5])->schema([
                                    TextInput::make('scrapeYear')
                                        ->label('Tahun')
                                        ->required()
                                        ->numeric()
                                        ->minValue(1900)
                                        ->maxValue((int) date('Y')),
                                    Select::make('scrapeType')
                                        ->label('Tipe')
                                        ->placeholder('Semua Tipe')
                                        ->options([
                                            'manga'      => 'Manga',
                                            'manhwa'     => 'Manhwa',
                                            'manhua'     => 'Manhua',
                                            'novel'      => 'Novel',
                                            'lightnovel' => 'Light Novel',
                                            'oneshot'    => 'One-shot',
                                            'doujin'     => 'Doujin',
                                        ]),
                                    Select::make('scrapeStatus')
                                        ->label('Status')
                                        ->placeholder('Semua Status')
                                        ->options([
                                            'publishing'   => 'Publishing',
                                            'complete'     => 'Complete',
                                            'hiatus'       => 'On Hiatus',
                                            'discontinued' => 'Discontinued',
                                            'upcoming'     => 'Upcoming',
                                        ]),
                                    Select::make('scrapeOrderBy')
                                        ->label('Urutkan')
                                        ->options(static::orderByOptions()),
                                    Select::make('scrapeSort')
                                        ->label('Urutan')
                                        ->options(['asc' => 'Ascending ↑', 'desc' => 'Descending ↓']),
                                ]),
                                Actions::make([
                                    Action::make('scrape')
                                        ->label('Browse')
                                        ->icon('heroicon-m-magnifying-glass')
                                        ->action(fn () => $this->scrape()),
                                ]),
                            ]),
                        ]),
                ]),

            // ── Filter bar + Load More (shared, below tabs) ──────────────────
            Section::make()->compact()
                ->visible(fn () => $this->hasSearchResults() || $this->hasScrapeResults())
                ->schema([
                    Grid::make(['default' => 1, 'sm' => 3])->schema([
                        Select::make('tableTypeFilter')
                            ->label('Filter Tipe')
                            ->placeholder('Semua Tipe')
                            ->options([
                                'Manga'       => 'Manga',
                                'Manhwa'      => 'Manhwa',
                                'Manhua'      => 'Manhua',
                                'Novel'       => 'Novel',
                                'Light Novel' => 'Light Novel',
                                'One-shot'    => 'One-shot',
                                'Doujin'      => 'Doujin',
                            ])
                            ->live(),
                        Select::make('tableStatusFilter')
                            ->label('Filter Status')
                            ->placeholder('Semua Status')
                            ->options([
                                'Finished'     => 'Finished',
                                'Publishing'   => 'Publishing',
                                'On Hiatus'    => 'On Hiatus',
                                'Discontinued' => 'Discontinued',
                                'Upcoming'     => 'Upcoming',
                            ])
                            ->live(),
                        TextInput::make('tableClientFilter')
                            ->label('Cari dalam hasil')
                            ->placeholder('Filter judul / author…')
                            ->live(debounce: 300),
                    ]),
                    Actions::make([
                        Action::make('searchLoadMore')
                            ->label('Muat Lebih')
                            ->color('gray')
                            ->size('sm')
                            ->action(fn () => $this->searchLoadMore())
                            ->visible(fn () => $this->activeTab === 'search' && $this->searchHasNext()),
                        Action::make('scrapeFetchNext')
                            ->label(fn () => $this->scrapeTotalPages() > 0
                                ? "Hal. {$this->scrapePage} / {$this->scrapeTotalPages()}"
                                : 'Muat Berikutnya')
                            ->color('gray')
                            ->size('sm')
                            ->action(fn () => $this->scrapeFetchNext())
                            ->visible(fn () => $this->activeTab === 'year' && $this->scrapeHasNext()),
                        Action::make('scrapeComplete')
                            ->label('Semua halaman dimuat')
                            ->icon('heroicon-m-check-circle')
                            ->color('success')
                            ->disabled()
                            ->visible(fn () => $this->activeTab === 'year' && $this->hasScrapeResults() && ! $this->scrapeHasNext()),
                    ])->alignEnd(),
                ]),

            EmbeddedTable::make(),
        ]);
    }

    // ── Search ───────────────────────────────────────────────────────────────

    public function search(): void
    {
        $this->validate(['searchQuery' => 'required|min:2']);

        $this->searchResults    = [];
        $this->searchPage       = 1;
        $this->searchPagination = null;
        $this->resetInstantFilters();

        try {
            $res = app(JikanService::class)->searchManga($this->searchQuery, 1, $this->buildSearchFilters());
            $this->searchResults    = $res['data'];
            $this->searchPagination = $res['pagination'];
        } catch (\Throwable $e) {
            Notification::make()->title('Pencarian gagal')->body($e->getMessage())->danger()->send();
        }
    }

    public function searchLoadMore(): void
    {
        $this->searchPage++;
        try {
            $res = app(JikanService::class)->searchManga($this->searchQuery, $this->searchPage, $this->buildSearchFilters());
            $this->searchResults    = array_merge($this->searchResults, $res['data']);
            $this->searchPagination = $res['pagination'];
        } catch (\Throwable $e) {
            Notification::make()->title('Gagal memuat')->body($e->getMessage())->danger()->send();
        }
    }

    private function buildSearchFilters(): array
    {
        return array_filter([
            'status'     => $this->searchStatus,
            'type'       => $this->searchType,
            'order_by'   => $this->searchOrderBy   ?: 'popularity',
            'sort'       => $this->searchSort      ?: 'asc',
            'min_score'  => $this->searchMinScore  ?: null,
            'max_score'  => $this->searchMaxScore  ?: null,
            'start_date' => $this->searchStartYear ? "{$this->searchStartYear}-01-01" : null,
            'end_date'   => $this->searchEndYear   ? "{$this->searchEndYear}-12-31"   : null,
        ]);
    }

    // ── Year scrape ──────────────────────────────────────────────────────────

    public function scrape(): void
    {
        $this->validate(['scrapeYear' => 'required|integer|min:1900|max:' . date('Y')]);

        $this->scrapeResults    = [];
        $this->scrapePage       = 0;
        $this->scrapePagination = null;
        $this->resetInstantFilters();

        $this->scrapeFetchNext();
    }

    public function scrapeFetchNext(): void
    {
        $next = $this->scrapePage + 1;
        try {
            $res = app(JikanService::class)->getMangaByYear($this->scrapeYear, $next, [
                'status'   => $this->scrapeStatus,
                'type'     => $this->scrapeType,
                'order_by' => $this->scrapeOrderBy ?: 'start_date',
                'sort'     => $this->scrapeSort    ?: 'asc',
            ]);
            $this->scrapeResults    = array_merge($this->scrapeResults, $res['data']);
            $this->scrapePagination = $res['pagination'];
            $this->scrapePage       = $next;
        } catch (\Throwable $e) {
            Notification::make()->title('Gagal memuat')->body($e->getMessage())->danger()->send();
        }
    }

    // ── Filament Table ───────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => $this->getFilteredCollection())
            ->columns([
                ImageColumn::make('cover')
                    ->label('')
                    ->getStateUsing(fn (array $record): ?string =>
                        $record['images']['jpg']['small_image_url']
                        ?? $record['images']['jpg']['image_url']
                        ?? null
                    )
                    ->height(64)
                    ->width(44)
                    ->url(fn (array $record): ?string =>
                        $record['images']['jpg']['large_image_url']
                        ?? $record['images']['jpg']['image_url']
                        ?? null
                    )
                    ->openUrlInNewTab()
                    ->extraImgAttributes(['class' => 'rounded object-cover cursor-zoom-in', 'loading' => 'lazy']),

                TextColumn::make('title')
                    ->label('Judul')
                    ->description(fn (array $record): string => $record['title_japanese'] ?? '')
                    ->wrap()
                    ->limit(60)
                    ->tooltip(fn (array $record): string => $record['title'] ?? ''),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Manga'                => 'primary',
                        'Manhwa'               => 'info',
                        'Manhua'               => 'warning',
                        'Novel', 'Light Novel' => 'success',
                        default                => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Finished'     => 'success',
                        'Publishing'   => 'warning',
                        'On Hiatus'    => 'gray',
                        'Discontinued' => 'danger',
                        'Upcoming'     => 'info',
                        default        => 'gray',
                    }),

                TextColumn::make('vol_ch')
                    ->label('Vol / Ch')
                    ->getStateUsing(fn (array $record): string =>
                        ($record['volumes'] ?: '?') . 'v / ' . ($record['chapters'] ?: '?') . 'ch'
                    )
                    ->alignCenter(),

                TextColumn::make('score')
                    ->label('Score')
                    ->getStateUsing(fn (array $record): string =>
                        $record['score'] ? '★ ' . number_format((float) $record['score'], 2) : '—'
                    )
                    ->alignCenter(),

                TextColumn::make('authors_list')
                    ->label('Author')
                    ->getStateUsing(fn (array $record): string =>
                        implode(', ', array_column($record['authors'] ?? [], 'name'))
                    )
                    ->limit(28),

                TextColumn::make('year')
                    ->label('Tahun')
                    ->getStateUsing(fn (array $record): string =>
                        (string) ($record['published']['prop']['from']['year']
                            ?? (! empty($record['published']['from'])
                                ? substr((string) $record['published']['from'], 0, 4)
                                : '—'))
                    )
                    ->alignCenter(),
            ])
            ->bulkActions([
                BulkAction::make('import')
                    ->label('Import ke Database')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Import')
                    ->modalDescription(fn (Collection $records): string =>
                        "Import {$records->count()} series ke database?"
                    )
                    ->action(function (Collection $records): void {
                        $action   = app(ImportSeriesFromJikan::class);
                        $imported = $skipped = $failed = 0;
                        $importedIds = [];

                        foreach ($records as $record) {
                            $malId = (int) ($record['mal_id'] ?? 0);
                            try {
                                $action->handle($malId);
                                $imported++;
                                $importedIds[] = $malId;
                            } catch (\Throwable $e) {
                                str_contains($e->getMessage(), 'sudah ada') ? $skipped++ : $failed++;
                            }
                        }

                        if (! empty($importedIds)) {
                            $key = $this->activeTab === 'search' ? 'searchResults' : 'scrapeResults';
                            $this->{$key} = array_values(
                                array_filter($this->{$key}, fn ($r) => ! in_array((int) $r['mal_id'], $importedIds))
                            );
                        }

                        if ($imported > 0) Notification::make()->title("{$imported} series berhasil diimpor")->success()->send();
                        if ($skipped > 0)  Notification::make()->title("{$skipped} series sudah ada, dilewati")->warning()->send();
                        if ($failed > 0)   Notification::make()->title("{$failed} series gagal diimpor")->danger()->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->emptyStateIcon('heroicon-o-book-open')
            ->emptyStateHeading('Belum ada hasil')
            ->emptyStateDescription(
                $this->activeTab === 'search'
                    ? 'Ketik judul di atas dan klik Cari.'
                    : 'Pilih tahun dan klik Browse.'
            );
    }

    public function getFilteredCollection(): Collection
    {
        $raw = $this->activeTab === 'search' ? $this->searchResults : $this->scrapeResults;

        return collect($raw)
            ->when(
                $this->tableTypeFilter,
                fn ($c, $f) => $c->filter(fn ($r) => strcasecmp($r['type'] ?? '', $f) === 0)
            )
            ->when(
                $this->tableStatusFilter,
                fn ($c, $f) => $c->filter(fn ($r) => strcasecmp($r['status'] ?? '', $f) === 0)
            )
            ->when(
                $this->tableClientFilter,
                function ($c, $f) {
                    $f = mb_strtolower(trim($f));
                    return $c->filter(function ($r) use ($f) {
                        $authors = implode(' ', array_column($r['authors'] ?? [], 'name'));
                        return str_contains(mb_strtolower($r['title'] ?? ''), $f)
                            || str_contains(mb_strtolower($r['title_english'] ?? ''), $f)
                            || str_contains(mb_strtolower($r['title_japanese'] ?? ''), $f)
                            || str_contains(mb_strtolower($authors), $f);
                    });
                }
            )
            ->values()
            ->map(fn (array $r): array => array_merge(['__key' => (string) $r['mal_id']], $r));
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function resetInstantFilters(): void
    {
        $this->tableTypeFilter   = null;
        $this->tableStatusFilter = null;
        $this->tableClientFilter = '';
    }

    public function resetSearchApiFilters(): void
    {
        $this->searchStatus    = null;
        $this->searchType      = null;
        $this->searchOrderBy   = 'popularity';
        $this->searchSort      = 'asc';
        $this->searchMinScore  = '';
        $this->searchMaxScore  = '';
        $this->searchStartYear = '';
        $this->searchEndYear   = '';
    }

    public function searchHasNext(): bool   { return $this->searchPagination['has_next_page'] ?? false; }
    public function scrapeHasNext(): bool   { return $this->scrapePagination['has_next_page'] ?? false; }
    public function scrapeTotalPages(): int { return $this->scrapePagination['last_visible_page'] ?? 0; }

    public function hasSearchResults(): bool { return ! empty($this->searchResults); }
    public function hasScrapeResults(): bool { return ! empty($this->scrapeResults); }

    public static function orderByOptions(): array
    {
        return [
            'popularity' => 'Popularitas',
            'score'      => 'Skor',
            'rank'       => 'Ranking',
            'title'      => 'Judul A–Z',
            'start_date' => 'Tanggal Mulai',
            'end_date'   => 'Tanggal Selesai',
            'volumes'    => 'Jumlah Volume',
            'chapters'   => 'Jumlah Chapter',
            'members'    => 'Members',
            'favorites'  => 'Favorit',
        ];
    }
}
