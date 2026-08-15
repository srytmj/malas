<?php

return [
    'ai_settings' => [
        'saved' => 'Pengaturan AI berhasil disimpan.',
    ],

    'anilist' => [
        'imported_from_cache' => 'Series berhasil diimpor (data dari cache pencarian — beberapa field mungkin tidak lengkap).',
        'imported' => 'Series berhasil diimpor dari AniList.',
        'updated' => 'Series berhasil diperbarui dari AniList.',
        'bulk_result' => 'Bulk import selesai: :imported series baru, :updated diperbarui.',
        'bulk_result_with_failed' => 'Bulk import selesai: :imported series baru, :updated diperbarui, :failed gagal diimpor.',
    ],

    'ranobedb' => [
        'imported' => 'Series berhasil diimpor dari RanobeDB.',
        'updated' => 'Series berhasil diperbarui dari RanobeDB.',
    ],

    'volumes_generated_suffix' => ' :count volume dibuat otomatis.',

    'announcements' => [
        'created' => 'Pengumuman berhasil dibuat.',
        'updated' => 'Pengumuman berhasil diperbarui.',
        'deleted' => 'Pengumuman berhasil dihapus.',
        'restored' => 'Pengumuman berhasil dipulihkan.',
    ],

    'database_backup' => [
        'invalid_file' => 'File bukan backup Malas yang valid. Pastikan file yang diupload adalah hasil download dari halaman ini.',
        'imported' => 'Database berhasil diimpor. Semua data (kecuali user) telah dipulihkan dari backup.',
        'import_failed' => 'Import gagal dan dibatalkan (rollback). Pesan error: :error',
    ],

    'genre_funfacts' => [
        'reset' => 'Kuota funfact :name berhasil direset.',
        'override_updated' => 'Batas kuota funfact :name berhasil diperbarui.',
    ],

    'mail_settings' => [
        'saved' => 'Pengaturan Email berhasil disimpan.',
    ],

    'menus' => [
        'reordered' => 'Urutan menu berhasil diperbarui.',
        'updated' => 'Menu berhasil diperbarui.',
    ],

    'series' => [
        'created' => 'Series berhasil ditambahkan.',
        'updated' => 'Series berhasil diperbarui.',
        'deleted' => 'Series berhasil dihapus.',
        'bulk_deleted' => ':count series berhasil dihapus.',
        'restored' => 'Series berhasil dipulihkan.',
        'bulk_restored' => ':count series berhasil dipulihkan.',
    ],

    'series_media' => [
        'added' => 'Media berhasil ditambahkan.',
        'deleted' => 'Media berhasil dihapus.',
    ],

    'site_settings' => [
        'saved' => 'Pengaturan konten berhasil disimpan.',
    ],

    'storage_settings' => [
        'saved_migrating' => 'Pengaturan penyimpanan disimpan. File lama sedang dipindahkan ke lokasi baru di latar belakang.',
        'saved' => 'Pengaturan penyimpanan berhasil disimpan.',
        'connection_ok' => 'Koneksi berhasil — kredensial valid dan bucket bisa diakses.',
    ],

    'tickets' => [
        'responded' => 'Respons berhasil dikirim.',
        'created' => 'Tiket berhasil dikirim. Admin akan segera meninjau.',
        'limit_reached' => 'Kamu hanya bisa punya :max tiket aktif dalam waktu yang sama. Tunggu tiket yang ada direspon/selesai dulu.',
    ],

    'users' => [
        'banned' => ':name berhasil di-ban.',
        'unbanned' => ':name berhasil di-unban.',
        'role_changed' => 'Role :name berhasil diubah ke :role.',
    ],

    'volumes' => [
        'total_not_set' => 'Total volume belum diset untuk series ini.',
        'generated' => ':count volume berhasil dibuat otomatis.',
        'all_already_exist' => 'Semua volume sudah ada, tidak ada yang perlu dibuat.',
        'created' => 'Volume berhasil ditambahkan.',
        'updated' => 'Volume berhasil diperbarui.',
        'deleted' => 'Volume berhasil dihapus.',
        'deleted_cover_note' => ' Cover volume tidak bisa dipulihkan otomatis.',
        'restored' => 'Volume berhasil dipulihkan.',
    ],

    'sso_fallback' => [
        'link_sent' => 'Kalau email itu terdaftar, link login sudah dikirim. Cek inbox (dan folder spam).',
    ],

    'settings' => [
        'profile_visibility_updated' => 'Profil kamu sekarang :state.',
        'profile_public' => 'publik',
        'profile_private' => 'privat',
    ],

    'collections' => [
        'already_added' => 'Series yang dipilih sudah ada di koleksimu.',
        'added' => ':count series berhasil ditambahkan ke koleksi.',
        'added_with_skipped' => ':count series berhasil ditambahkan ke koleksi, :skipped dilewati (sudah ada).',
        'undo_added' => ':count series dibatalkan penambahannya.',
        'deleted' => 'Koleksi berhasil dihapus.',
        'already_restored' => 'Series ini sudah ada lagi di koleksimu.',
        'restored' => 'Koleksi berhasil dipulihkan.',
        'condition_updated' => 'Kondisi koleksi berhasil diperbarui.',
        'review_saved' => 'Review berhasil disimpan.',

        'volumes_invalid_number' => 'Masukkan minimal satu nomor volume yang valid.',
        'volumes_too_many' => 'Maksimal 100 volume sekaligus.',
        'volumes_added' => ':count volume berhasil ditambahkan.',
        'volumes_added_with_skipped' => ':count volume berhasil ditambahkan, :skipped dilewati (sudah ada).',
        'volumes_all_existing' => 'Semua volume yang diinput sudah ada di koleksimu.',
        'volume_format_updated' => 'Format volume :number berhasil diperbarui.',
        'volumes_format_bulk_updated' => ':count volume berhasil diubah formatnya.',
        'volume_deleted' => 'Volume berhasil dihapus dari koleksi.',
        'volumes_bulk_deleted' => ':count volume berhasil dihapus dari koleksi.',
        'volumes_restored' => ':count volume berhasil dipulihkan.',

        'volume_marked_read' => 'Volume :number ditandai sudah dibaca.',
        'volume_marked_unread' => 'Volume :number ditandai belum dibaca.',
        'all_already_read' => 'Semua volume sudah ditandai dibaca.',
        'marked_all_read' => ':count volume ditandai sudah dibaca.',
        'undo_marked_read' => 'Perubahan dibatalkan.',
        'all_owned_already_read' => 'Semua volume yang dimiliki sudah ditandai dibaca.',
        'none_read_yet' => 'Belum ada volume yang ditandai dibaca.',

        'quick_volume_added' => 'Volume :number berhasil ditambahkan.',
        'quick_no_volume_for_format' => 'Belum ada volume dengan format ini.',
        'quick_top_volume_loaned' => 'Volume tertinggi format ini sedang dipinjamkan, tidak bisa dihapus.',
        'quick_volume_deleted' => 'Volume :number berhasil dihapus.',
    ],

    'dashboard' => [
        'funfact_quota_reached' => 'Batas generate ulang :max x per minggu sudah tercapai. Coba lagi minggu depan.',
        'funfact_failed' => 'Gagal generate funfact. Coba lagi nanti.',
        'funfact_rate_limited' => 'Provider AI lagi kena limit — ditampilkan funfact sementara. Coba generate ulang nanti.',
        'funfact_regenerated' => 'Funfact berhasil di-generate ulang.',
    ],

    'loans' => [
        'already_loaned' => 'Volume ini masih dalam status dipinjam.',
        'recorded' => 'Pinjaman berhasil dicatat.',
        'marked_returned' => 'Volume berhasil ditandai sudah dikembalikan.',
    ],

    'profile' => [
        'followed' => 'Mengikuti :name.',
        'unfollowed' => 'Berhenti mengikuti :name.',
    ],

    'collection_groups' => [
        'created' => 'Grup berhasil dibuat.',
        'renamed' => 'Nama grup berhasil diubah.',
        'deleted' => 'Grup berhasil dihapus.',
        'items_added' => ':count manga berhasil ditambahkan ke grup.',
        'item_removed' => 'Manga berhasil dihapus dari grup.',
        'item_restored' => 'Manga dikembalikan ke grup.',
        'item_restore_failed' => 'Gagal mengembalikan manga — koleksinya sudah tidak ada.',
        'made_public' => 'Grup sekarang publik — muncul di profilmu.',
        'made_private' => 'Grup sekarang privat.',
    ],

    'wishlist' => [
        'already_owned' => 'Series ini sudah ada di koleksimu.',
        'added' => 'Ditambahkan ke wishlist.',
        'deleted' => 'Dihapus dari wishlist.',
        'restored' => 'Wishlist dipulihkan.',
    ],
];
