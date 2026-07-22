<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            // Admin menus
            [
                'key'         => 'admin.dashboard',
                'label'       => 'Dashboard',
                'icon'        => 'layout-dashboard',
                'route_name'  => 'admin.dashboard',
                'sort_order'  => 1,
                'role_access' => ['admin', 'super_admin'],
            ],
            [
                'key'         => 'admin.series',
                'label'       => 'Series',
                'icon'        => 'book-open',
                'route_name'  => 'admin.series.index',
                'sort_order'  => 2,
                'role_access' => ['admin', 'super_admin'],
            ],
            [
                'key'         => 'admin.collections',
                'label'       => 'Koleksi',
                'icon'        => 'library',
                'route_name'  => 'admin.collections.index',
                'sort_order'  => 3,
                'role_access' => ['admin', 'super_admin'],
            ],
            [
                'key'         => 'admin.loans',
                'label'       => 'Peminjaman',
                'icon'        => 'hand-coins',
                'route_name'  => 'admin.loans.index',
                'sort_order'  => 4,
                'role_access' => ['admin', 'super_admin'],
            ],
            [
                'key'         => 'admin.users',
                'label'       => 'Pengguna',
                'icon'        => 'users',
                'route_name'  => 'admin.users.index',
                'sort_order'  => 5,
                'role_access' => ['admin', 'super_admin'],
            ],
            [
                'key'         => 'admin.menus',
                'label'       => 'Menu',
                'icon'        => 'menu',
                'route_name'  => 'admin.menus.index',
                'sort_order'  => 6,
                'role_access' => ['admin', 'super_admin'],
            ],
            [
                'key'         => 'admin.announcements',
                'label'       => 'Pengumuman',
                'icon'        => 'megaphone',
                'route_name'  => 'admin.announcements.index',
                'sort_order'  => 7,
                'role_access' => ['admin', 'super_admin'],
            ],
            [
                'key'         => 'admin.anilist',
                'label'       => 'Cari Manga (AniList)',
                'icon'        => 'search',
                'route_name'  => 'admin.anilist.index',
                'sort_order'  => 8,
                'role_access' => ['admin', 'super_admin'],
            ],
            [
                'key'         => 'admin.anilist.status',
                'label'       => 'Status AniList',
                'icon'        => 'activity',
                'route_name'  => 'admin.anilist.status.page',
                'sort_order'  => 9,
                'role_access' => ['admin', 'super_admin'],
            ],
            [
                'key'         => 'admin.tickets',
                'label'       => 'Tiket',
                'icon'        => 'ticket',
                'route_name'  => 'admin.tickets.index',
                'sort_order'  => 10,
                'role_access' => ['admin', 'super_admin'],
            ],
            // User menus
            [
                'key'         => 'user.dashboard',
                'label'       => 'Dashboard',
                'icon'        => 'layout-dashboard',
                'route_name'  => 'dashboard',
                'sort_order'  => 1,
                'role_access' => ['user'],
            ],
            [
                'key'         => 'user.catalog',
                'label'       => 'Katalog',
                'icon'        => 'book-open',
                'route_name'  => 'catalog.index',
                'sort_order'  => 2,
                'role_access' => ['user'],
            ],
            [
                'key'         => 'user.collection',
                'label'       => 'Koleksiku',
                'icon'        => 'library',
                'route_name'  => 'collection.index',
                'sort_order'  => 3,
                'role_access' => ['user'],
            ],
            [
                'key'         => 'user.loans',
                'label'       => 'Pinjaman Saya',
                'icon'        => 'hand-coins',
                'route_name'  => 'loans.index',
                'sort_order'  => 4,
                'role_access' => ['user'],
            ],
            [
                'key'         => 'user.tickets',
                'label'       => 'Tiket',
                'icon'        => 'ticket',
                'route_name'  => 'tickets.index',
                'sort_order'  => 5,
                'role_access' => ['user'],
            ],
            // Shared — all roles
            [
                'key'         => 'settings',
                'label'       => 'Pengaturan',
                'icon'        => 'settings',
                'route_name'  => 'settings.index',
                'sort_order'  => 50,
                'role_access' => ['user', 'admin', 'super_admin'],
            ],
        ];

        foreach ($menus as $menu) {
            Menu::updateOrCreate(['key' => $menu['key']], $menu);
        }
    }
}
