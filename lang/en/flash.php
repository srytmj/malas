<?php

return [
    'ai_settings' => [
        'saved' => 'AI settings saved successfully.',
    ],

    'anilist' => [
        'imported_from_cache' => 'Series imported successfully (from search cache — some fields may be incomplete).',
        'imported' => 'Series imported successfully from AniList.',
        'updated' => 'Series updated successfully from AniList.',
        'bulk_result' => 'Bulk import complete: :imported new series, :updated updated.',
        'bulk_result_with_failed' => 'Bulk import complete: :imported new series, :updated updated, :failed failed to import.',
    ],

    'ranobedb' => [
        'imported' => 'Series imported successfully from RanobeDB.',
        'updated' => 'Series updated successfully from RanobeDB.',
    ],

    'volumes_generated_suffix' => ' :count volumes auto-generated.',

    'announcements' => [
        'created' => 'Announcement created successfully.',
        'updated' => 'Announcement updated successfully.',
        'deleted' => 'Announcement deleted successfully.',
        'restored' => 'Announcement restored successfully.',
    ],

    'database_backup' => [
        'invalid_file' => 'This is not a valid Malas backup file. Make sure you uploaded a file downloaded from this page.',
        'imported' => 'Database imported successfully. All data (except users) has been restored from the backup.',
        'import_failed' => 'Import failed and was rolled back. Error message: :error',
    ],

    'genre_funfacts' => [
        'reset' => ":name's funfact quota was reset successfully.",
        'override_updated' => ":name's funfact quota limit updated successfully.",
    ],

    'mail_settings' => [
        'saved' => 'Email settings saved successfully.',
    ],

    'menus' => [
        'reordered' => 'Menu order updated successfully.',
        'updated' => 'Menu updated successfully.',
    ],

    'series' => [
        'created' => 'Series added successfully.',
        'updated' => 'Series updated successfully.',
        'deleted' => 'Series deleted successfully.',
        'bulk_deleted' => ':count series deleted successfully.',
        'restored' => 'Series restored successfully.',
        'bulk_restored' => ':count series restored successfully.',
    ],

    'series_media' => [
        'added' => 'Media added successfully.',
        'deleted' => 'Media deleted successfully.',
    ],

    'site_settings' => [
        'saved' => 'Content settings saved successfully.',
    ],

    'storage_settings' => [
        'saved_migrating' => 'Storage settings saved. Old files are being moved to the new location in the background.',
        'saved' => 'Storage settings saved successfully.',
        'connection_ok' => 'Connection successful — credentials are valid and the bucket is accessible.',
    ],

    'tickets' => [
        'responded' => 'Response sent successfully.',
        'created' => 'Ticket sent successfully. An admin will review it soon.',
        'limit_reached' => "You can only have :max active ticket(s) at a time. Wait for your existing ticket(s) to be responded to or resolved.",
    ],

    'users' => [
        'banned' => ':name has been banned successfully.',
        'unbanned' => ':name has been unbanned successfully.',
        'role_changed' => ":name's role changed to :role successfully.",
    ],

    'volumes' => [
        'total_not_set' => 'Total volumes has not been set for this series.',
        'generated' => ':count volume(s) auto-generated successfully.',
        'all_already_exist' => 'All volumes already exist, nothing to generate.',
        'created' => 'Volume added successfully.',
        'updated' => 'Volume updated successfully.',
        'deleted' => 'Volume deleted successfully.',
        'deleted_cover_note' => ' The volume cover cannot be restored automatically.',
        'restored' => 'Volume restored successfully.',
    ],

    'sso_fallback' => [
        'link_sent' => "If that email is registered, a login link has been sent. Check your inbox (and spam folder).",
    ],

    'settings' => [
        'profile_visibility_updated' => 'Your profile is now :state.',
        'profile_public' => 'public',
        'profile_private' => 'private',
    ],

    'collections' => [
        'already_added' => 'The selected series is already in your collection.',
        'added' => ':count series added to your collection successfully.',
        'added_with_skipped' => ':count series added to your collection successfully, :skipped skipped (already added).',
        'undo_added' => ':count series addition undone.',
        'deleted' => 'Collection deleted successfully.',
        'already_restored' => 'This series is already back in your collection.',
        'restored' => 'Collection restored successfully.',
        'condition_updated' => 'Collection condition updated successfully.',
        'review_saved' => 'Review saved successfully.',

        'volumes_invalid_number' => 'Enter at least one valid volume number.',
        'volumes_too_many' => 'Maximum 100 volumes at once.',
        'volumes_added' => ':count volume(s) added successfully.',
        'volumes_added_with_skipped' => ':count volume(s) added successfully, :skipped skipped (already added).',
        'volumes_all_existing' => 'All the volumes you entered are already in your collection.',
        'volume_format_updated' => 'Volume :number format updated successfully.',
        'volumes_format_bulk_updated' => ':count volume(s) format changed successfully.',
        'volume_deleted' => 'Volume removed from your collection successfully.',
        'volumes_bulk_deleted' => ':count volume(s) removed from your collection successfully.',
        'volumes_restored' => ':count volume(s) restored successfully.',

        'volume_marked_read' => 'Volume :number marked as read.',
        'volume_marked_unread' => 'Volume :number marked as unread.',
        'all_already_read' => 'All volumes are already marked as read.',
        'marked_all_read' => ':count volume(s) marked as read.',
        'undo_marked_read' => 'Change undone.',
        'all_owned_already_read' => 'All the volumes you own are already marked as read.',
        'none_read_yet' => "You haven't marked any volume as read yet.",

        'quick_volume_added' => 'Volume :number added successfully.',
        'quick_no_volume_for_format' => "You don't have any volume with this format yet.",
        'quick_top_volume_loaned' => 'The highest volume of this format is currently lent out and cannot be deleted.',
        'quick_volume_deleted' => 'Volume :number deleted successfully.',
    ],

    'dashboard' => [
        'funfact_quota_reached' => "You've reached the regenerate limit of :max x per week. Try again next week.",
        'funfact_failed' => 'Failed to generate funfact. Try again later.',
        'funfact_rate_limited' => 'The AI provider is currently rate-limited — showing a temporary funfact instead. Try regenerating later.',
        'funfact_regenerated' => 'Funfact regenerated successfully.',
    ],

    'loans' => [
        'already_loaned' => 'This volume is currently on loan.',
        'recorded' => 'Loan recorded successfully.',
        'marked_returned' => 'Volume marked as returned successfully.',
    ],

    'profile' => [
        'followed' => 'Now following :name.',
        'unfollowed' => 'Unfollowed :name.',
    ],

    'collection_groups' => [
        'created' => 'Group created successfully.',
        'renamed' => 'Group renamed successfully.',
        'deleted' => 'Group deleted successfully.',
        'items_added' => ':count manga added to the group successfully.',
        'item_removed' => 'Manga removed from the group successfully.',
        'item_restored' => 'Manga restored to the group.',
        'item_restore_failed' => 'Could not restore the manga — the collection entry no longer exists.',
        'made_public' => 'Group is now public — it appears on your profile.',
        'made_private' => 'Group is now private.',
    ],

    'wishlist' => [
        'already_owned' => 'This series is already in your collection.',
        'added' => 'Added to wishlist.',
        'deleted' => 'Removed from wishlist.',
        'restored' => 'Wishlist item restored.',
    ],
];
