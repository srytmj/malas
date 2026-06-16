<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class InstalledPlugins extends Page implements HasTable
{
    use InteractsWithTable;

    protected static \UnitEnum|string|null   $navigationGroup = 'Alat';
    protected static \BackedEnum|string|null $navigationIcon  = Heroicon::OutlinedPuzzlePiece;
    protected static ?string $navigationLabel = 'Plugin Terpasang';
    protected static ?int    $navigationSort  = 99;

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public function getTitle(): string
    {
        return 'Plugin Terpasang';
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        $records = collect(static::getPlugins())
            ->map(fn (array $p): array => array_merge(['__key' => $p['name']], $p));

        return $table
            ->records(fn () => $records)
            ->columns([
                TextColumn::make('label')
                    ->label('Plugin')
                    ->description(fn (array $record): string => $record['name'])
                    ->icon(fn (array $record): string => $record['icon']),
                TextColumn::make('version')
                    ->label('Versi')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Core'             => 'primary',
                        'Security'         => 'success',
                        'Auth'             => 'info',
                        'UI'               => 'warning',
                        'Forms'            => 'gray',
                        'Charts'           => 'gray',
                        'Notifications'    => 'gray',
                        'Spatie'           => 'gray',
                        'Storage'          => 'gray',
                        'Tidak Support v5' => 'danger',
                        default            => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn (array $record): string => $record['active'] ? 'Aktif' : 'Belum v5')
                    ->badge()
                    ->color(fn (array $record): string => $record['active'] ? 'success' : 'danger'),
                TextColumn::make('desc')
                    ->label('Deskripsi')
                    ->wrap()
                    ->limit(100),
            ])
            ->paginated(false)
            ->striped();
    }

    public static function getPlugins(): array
    {
        return [
            // ── Core ──────────────────────────────────────────────────────────
            [
                'name'     => 'filament/filament',
                'label'    => 'Filament',
                'version'  => 'v5.6.7',
                'desc'     => 'Admin panel, forms, tables, infolists, notifications, widgets — inti dari seluruh UI MALAS.',
                'icon'     => 'heroicon-o-squares-2x2',
                'url'      => 'https://filamentphp.com',
                'category' => 'Core',
                'active'   => true,
            ],
            // ── Security ──────────────────────────────────────────────────────
            [
                'name'     => 'bezhansalleh/filament-shield',
                'label'    => 'Filament Shield',
                'version'  => '4.2.0',
                'desc'     => 'Generate policy & permission otomatis per resource/page berbasis Spatie Permission.',
                'icon'     => 'heroicon-o-shield-check',
                'url'      => 'https://filamentphp.com/plugins/bezhansalleh-shield',
                'category' => 'Security',
                'active'   => true,
            ],
            [
                'name'     => 'spatie/laravel-permission',
                'label'    => 'Spatie Permission',
                'version'  => '6.25.0',
                'desc'     => 'Foundation role & permission — dipakai Shield untuk menyimpan data permission di database.',
                'icon'     => 'heroicon-o-key',
                'url'      => 'https://spatie.be/docs/laravel-permission',
                'category' => 'Security',
                'active'   => true,
            ],
            [
                'name'     => 'althinect/filament-spatie-roles-permissions',
                'label'    => 'Spatie Roles & Permissions UI',
                'version'  => 'v3.3.2',
                'desc'     => 'UI Filament untuk mengelola roles dan permissions Spatie secara visual.',
                'icon'     => 'heroicon-o-user-group',
                'url'      => 'https://filamentphp.com/plugins/tharinda-rodrigo-spatie-roles-permissions',
                'category' => 'Security',
                'active'   => true,
            ],
            // ── Auth ──────────────────────────────────────────────────────────
            [
                'name'     => 'jeffgreco13/filament-breezy',
                'label'    => 'Filament Breezy',
                'version'  => 'v3.2.5',
                'desc'     => 'My Profile, ganti password, dan two-factor authentication (TOTP) untuk semua user.',
                'icon'     => 'heroicon-o-user-circle',
                'url'      => 'https://filamentphp.com/plugins/jeffgreco13-breezy',
                'category' => 'Auth',
                'active'   => true,
            ],
            [
                'name'     => 'diogogpinto/filament-auth-ui-enhancer',
                'label'    => 'Auth UI Enhancer',
                'version'  => 'v2.0.2',
                'desc'     => 'Memperindah tampilan halaman login Filament dengan desain yang lebih modern.',
                'icon'     => 'heroicon-o-paint-brush',
                'url'      => 'https://filamentphp.com/plugins/diogogpinto-auth-ui-enhancer',
                'category' => 'Auth',
                'active'   => true,
            ],
            // ── UI ────────────────────────────────────────────────────────────
            [
                'name'     => 'pxlrbt/filament-spotlight',
                'label'    => 'Filament Spotlight',
                'version'  => 'v2.1.1',
                'desc'     => 'Command palette (Ctrl+K / ⌘K) untuk navigasi cepat ke resource, page, atau action.',
                'icon'     => 'heroicon-o-magnifying-glass-circle',
                'url'      => 'https://filamentphp.com/plugins/pxlrbt-spotlight',
                'category' => 'UI',
                'active'   => true,
            ],
            [
                'name'     => 'cocosmos/filament-sticky-save-bar',
                'label'    => 'Sticky Save Bar',
                'version'  => 'v1.0.1',
                'desc'     => 'Tombol simpan sticky saat scroll di form panjang.',
                'icon'     => 'heroicon-o-arrow-down-circle',
                'url'      => 'https://filamentphp.com/plugins/cocosmos-sticky-save-bar',
                'category' => 'UI',
                'active'   => true,
            ],
            [
                'name'     => 'charrafimed/global-search-modal',
                'label'    => 'Global Search Modal',
                'version'  => 'v5.0.1',
                'desc'     => 'Modal pencarian global yang menggantikan search bar bawaan Filament.',
                'icon'     => 'heroicon-o-magnifying-glass',
                'url'      => 'https://filamentphp.com/plugins/charrafimed-global-search-modal',
                'category' => 'UI',
                'active'   => true,
            ],
            [
                'name'     => 'malzariey/filament-daterangepicker-filter',
                'label'    => 'Date Range Picker Filter',
                'version'  => '5.0.6',
                'desc'     => 'Filter date range dengan date picker untuk tabel Filament.',
                'icon'     => 'heroicon-o-calendar-days',
                'url'      => 'https://filamentphp.com/plugins/malzariey-daterangepicker',
                'category' => 'UI',
                'active'   => true,
            ],
            [
                'name'     => 'giacomomasseron/filament-textinput-autocomplete',
                'label'    => 'TextInput Autocomplete',
                'version'  => 'v1.0',
                'desc'     => 'Tambahkan autocomplete dropdown ke field TextInput Filament.',
                'icon'     => 'heroicon-o-list-bullet',
                'url'      => 'https://filamentphp.com/plugins/giacomo-masseroni-textinput-autocomplete',
                'category' => 'UI',
                'active'   => true,
            ],
            // ── Forms ─────────────────────────────────────────────────────────
            [
                'name'     => 'ptplugins/filament-number-input',
                'label'    => 'Number Input',
                'version'  => '1.0.2',
                'desc'     => 'Input angka dengan format ribuan, prefix/suffix, dan stepper button.',
                'icon'     => 'heroicon-o-calculator',
                'url'      => 'https://filamentphp.com/plugins/ptplugins-number-input',
                'category' => 'Forms',
                'active'   => true,
            ],
            // ── Charts ────────────────────────────────────────────────────────
            [
                'name'     => 'leandrocfe/filament-apex-charts',
                'label'    => 'Apex Charts',
                'version'  => '5.1.0',
                'desc'     => 'Widget chart interaktif berbasis ApexCharts untuk dashboard Filament.',
                'icon'     => 'heroicon-o-chart-bar',
                'url'      => 'https://filamentphp.com/plugins/leandrocfe-apex-charts',
                'category' => 'Charts',
                'active'   => true,
            ],
            [
                'name'     => 'lara-zeus/progress',
                'label'    => 'Lara Zeus Progress',
                'version'  => '3.0.1',
                'desc'     => 'Widget dan column progress bar untuk visualisasi data persentase.',
                'icon'     => 'heroicon-o-signal',
                'url'      => 'https://filamentphp.com/plugins/lara-zeus-progress',
                'category' => 'Charts',
                'active'   => true,
            ],
            // ── Notifications ─────────────────────────────────────────────────
            [
                'name'     => 'emuniq/filament-browser-notifications',
                'label'    => 'Browser Notifications',
                'version'  => 'v1.1.1',
                'desc'     => 'Push notifikasi browser menggunakan Web Push API untuk user yang sedang offline.',
                'icon'     => 'heroicon-o-bell-alert',
                'url'      => 'https://filamentphp.com/plugins/isach-browser-notifications',
                'category' => 'Notifications',
                'active'   => true,
            ],
            // ── Spatie Integrations ───────────────────────────────────────────
            [
                'name'     => 'filament/spatie-laravel-settings-plugin',
                'label'    => 'Spatie Settings',
                'version'  => 'v5.6.7',
                'desc'     => 'Form components untuk integrasi spatie/laravel-settings — simpan pengaturan app di database.',
                'icon'     => 'heroicon-o-cog-8-tooth',
                'url'      => 'https://filamentphp.com/plugins/filament-spatie-settings',
                'category' => 'Spatie',
                'active'   => true,
            ],
            [
                'name'     => 'filament/spatie-laravel-tags-plugin',
                'label'    => 'Spatie Tags',
                'version'  => 'v5.6.7',
                'desc'     => 'Input dan column tags dari spatie/laravel-tags — tag polymorphic untuk semua model.',
                'icon'     => 'heroicon-o-tag',
                'url'      => 'https://filamentphp.com/plugins/filament-spatie-tags',
                'category' => 'Spatie',
                'active'   => true,
            ],
            [
                'name'     => 'filament/spatie-laravel-google-fonts-plugin',
                'label'    => 'Spatie Google Fonts',
                'version'  => 'v5.6.7',
                'desc'     => 'Load Google Fonts via spatie/laravel-google-fonts dengan caching lokal.',
                'icon'     => 'heroicon-o-globe-alt',
                'url'      => 'https://filamentphp.com/plugins/filament-spatie-google-fonts',
                'category' => 'Spatie',
                'active'   => true,
            ],
            [
                'name'     => 'spatie/laravel-activitylog',
                'label'    => 'Activity Log',
                'version'  => '4.12.3',
                'desc'     => 'Logging otomatis setiap perubahan model (create/update/delete) untuk audit trail.',
                'icon'     => 'heroicon-o-clipboard-document-list',
                'url'      => 'https://spatie.be/docs/laravel-activitylog',
                'category' => 'Spatie',
                'active'   => true,
            ],
            // ── Storage ───────────────────────────────────────────────────────
            [
                'name'     => 'league/flysystem-aws-s3-v3',
                'label'    => 'Flysystem S3 (Cloudflare R2)',
                'version'  => '3.34.0',
                'desc'     => 'Driver S3 untuk Laravel Filesystem — dipakai menyimpan cover manga ke Cloudflare R2.',
                'icon'     => 'heroicon-o-cloud-arrow-up',
                'url'      => 'https://github.com/thephpleague/flysystem-aws-s3-v3',
                'category' => 'Storage',
                'active'   => true,
            ],
            // ── Tidak Support v5 ──────────────────────────────────────────────
            [
                'name'     => 'hasnayeen/themes',
                'label'    => 'Hasnayeen Themes',
                'version'  => '—',
                'desc'     => 'Multiple UI themes untuk Filament. Belum support Filament v5 (hanya v3).',
                'icon'     => 'heroicon-o-swatch',
                'url'      => 'https://filamentphp.com/plugins/hasnayeen-themes',
                'category' => 'Tidak Support v5',
                'active'   => false,
            ],
            [
                'name'     => 'pxlrbt/filament-excel',
                'label'    => 'Filament Excel',
                'version'  => '—',
                'desc'     => 'Export tabel ke Excel/CSV. Belum support Filament v5 (hanya v4).',
                'icon'     => 'heroicon-o-arrow-down-tray',
                'url'      => 'https://filamentphp.com/plugins/pxlrbt-excel',
                'category' => 'Tidak Support v5',
                'active'   => false,
            ],
            [
                'name'     => 'pxlrbt/filament-environment-indicator',
                'label'    => 'Environment Indicator',
                'version'  => '—',
                'desc'     => 'Indicator environment (local/staging/production) di panel. Belum support Filament v5.',
                'icon'     => 'heroicon-o-exclamation-triangle',
                'url'      => 'https://filamentphp.com/plugins/pxlrbt-environment-indicator',
                'category' => 'Tidak Support v5',
                'active'   => false,
            ],
            [
                'name'     => 'awcodes/filament-table-repeater',
                'label'    => 'Table Repeater',
                'version'  => '—',
                'desc'     => 'Repeater dalam bentuk tabel. Belum support Filament v5 (hanya v3).',
                'icon'     => 'heroicon-o-table-cells',
                'url'      => 'https://filamentphp.com/plugins/awcodes-table-repeater',
                'category' => 'Tidak Support v5',
                'active'   => false,
            ],
            [
                'name'     => 'archilex/filament-toggle-icon-column',
                'label'    => 'Toggle Icon Column',
                'version'  => '—',
                'desc'     => 'Kolom tabel dengan toggle icon. Belum support Filament v5 (hanya v3).',
                'icon'     => 'heroicon-o-check-circle',
                'url'      => 'https://filamentphp.com/plugins/kenneth-sese-toggle-icon-column',
                'category' => 'Tidak Support v5',
                'active'   => false,
            ],
            [
                'name'     => 'archilex/filament-stacked-image-column',
                'label'    => 'Stacked Image Column',
                'version'  => '—',
                'desc'     => 'Kolom gambar yang ditumpuk (stacked). Belum support Filament v5 (hanya v2).',
                'icon'     => 'heroicon-o-photo',
                'url'      => 'https://filamentphp.com/plugins/kenneth-sese-stacked-image-column',
                'category' => 'Tidak Support v5',
                'active'   => false,
            ],
            [
                'name'     => 'joaopaulolndev/filament-edit-profile',
                'label'    => 'Edit Profile Page',
                'version'  => '—',
                'desc'     => 'Halaman edit profil extended. Belum support Filament v5 (hanya v3). Fungsi sudah covered oleh Breezy.',
                'icon'     => 'heroicon-o-user',
                'url'      => 'https://filamentphp.com/plugins/joaopaulolndev-edit-profile',
                'category' => 'Tidak Support v5',
                'active'   => false,
            ],
        ];
    }
}
