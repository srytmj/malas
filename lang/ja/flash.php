<?php

return [
    'ai_settings' => [
        'saved' => 'AI設定を保存しました。',
    ],

    'anilist' => [
        'imported_from_cache' => 'シリーズをインポートしました（検索キャッシュのデータ — 一部項目が不完全な場合があります）。',
        'imported' => 'AniListからシリーズをインポートしました。',
        'updated' => 'AniListからシリーズを更新しました。',
        'bulk_result' => '一括インポート完了: 新規:imported件、更新:updated件。',
        'bulk_result_with_failed' => '一括インポート完了: 新規:imported件、更新:updated件、失敗:failed件。',
    ],

    'ranobedb' => [
        'imported' => 'RanobeDBからシリーズをインポートしました。',
        'updated' => 'RanobeDBからシリーズを更新しました。',
    ],

    'volumes_generated_suffix' => ' 巻:countを自動生成しました。',

    'announcements' => [
        'created' => 'お知らせを作成しました。',
        'updated' => 'お知らせを更新しました。',
        'deleted' => 'お知らせを削除しました。',
        'restored' => 'お知らせを復元しました。',
    ],

    'database_backup' => [
        'invalid_file' => '有効なMalasバックアップファイルではありません。このページからダウンロードしたファイルかご確認ください。',
        'imported' => 'データベースをインポートしました。ユーザーを除く全データがバックアップから復元されました。',
        'import_failed' => 'インポートに失敗しロールバックされました。エラー内容: :error',
    ],

    'genre_funfacts' => [
        'reset' => ':nameのfunfact生成回数をリセットしました。',
        'override_updated' => ':nameのfunfact生成上限を更新しました。',
    ],

    'mail_settings' => [
        'saved' => 'メール設定を保存しました。',
    ],

    'menus' => [
        'reordered' => 'メューの並び順を更新しました。',
        'updated' => 'メニューを更新しました。',
    ],

    'series' => [
        'created' => 'シリーズを追加しました。',
        'updated' => 'シリーズを更新しました。',
        'deleted' => 'シリーズを削除しました。',
        'bulk_deleted' => ':count件のシリーズを削除しました。',
        'restored' => 'シリーズを復元しました。',
        'bulk_restored' => ':count件のシリーズを復元しました。',
    ],

    'series_media' => [
        'added' => 'メディアを追加しました。',
        'deleted' => 'メディアを削除しました。',
    ],

    'site_settings' => [
        'saved' => 'コンテンツ設定を保存しました。',
    ],

    'storage_settings' => [
        'saved_migrating' => 'ストレージ設定を保存しました。既存ファイルはバックグラウンドで新しい保存先に移動中です。',
        'saved' => 'ストレージ設定を保存しました。',
        'connection_ok' => '接続に成功しました — 認証情報は有効で、バケットにアクセスできます。',
    ],

    'tickets' => [
        'responded' => '返信を送信しました。',
        'created' => 'チケットを送信しました。管理者が確認します。',
        'limit_reached' => '同時に持てるアクティブなチケットは:max件までです。既存のチケットへの返信または解決をお待ちください。',
    ],

    'users' => [
        'banned' => ':nameをBANしました。',
        'unbanned' => ':nameのBANを解除しました。',
        'role_changed' => ':nameのロールを:roleに変更しました。',
    ],

    'volumes' => [
        'total_not_set' => 'このシリーズの総巻数が設定されていません。',
        'generated' => ':count巻を自動生成しました。',
        'all_already_exist' => 'すべての巻がすでに存在するため、生成の必要はありません。',
        'created' => '巻を追加しました。',
        'updated' => '巻を更新しました。',
        'deleted' => '巻を削除しました。',
        'deleted_cover_note' => ' 巻の表紙は自動で復元できません。',
        'restored' => '巻を復元しました。',
    ],

    'sso_fallback' => [
        'link_sent' => 'そのメールアドレスが登録済みであれば、ログインリンクを送信しました。受信箱（迷惑メールフォルダも）をご確認ください。',
    ],

    'settings' => [
        'profile_visibility_updated' => 'プロフィールを:stateに設定しました。',
        'profile_public' => '公開',
        'profile_private' => '非公開',
    ],

    'collections' => [
        'already_added' => '選択したシリーズはすでにコレクションにあります。',
        'added' => ':count件のシリーズをコレクションに追加しました。',
        'added_with_skipped' => ':count件のシリーズをコレクションに追加しました（:skipped件はすでに追加済みのためスキップ）。',
        'undo_added' => ':count件のシリーズ追加を取り消しました。',
        'deleted' => 'コレクションを削除しました。',
        'already_restored' => 'このシリーズはすでにコレクションに戻っています。',
        'restored' => 'コレクションを復元しました。',
        'condition_updated' => 'コレクションの状態を更新しました。',
        'review_saved' => 'レビューを保存しました。',

        'volumes_invalid_number' => '有効な巻番号を1つ以上入力してください。',
        'volumes_too_many' => '一度に追加できるのは最大100巻までです。',
        'volumes_added' => ':count巻を追加しました。',
        'volumes_added_with_skipped' => ':count巻を追加しました（:skipped巻はすでに追加済みのためスキップ）。',
        'volumes_all_existing' => '入力した巻はすべてすでにコレクションにあります。',
        'volume_format_updated' => '第:number巻のフォーマットを更新しました。',
        'volumes_format_bulk_updated' => ':count巻のフォーマットを変更しました。',
        'volume_deleted' => 'コレクションから巻を削除しました。',
        'volumes_bulk_deleted' => 'コレクションから:count巻を削除しました。',
        'volumes_restored' => ':count巻を復元しました。',

        'volume_marked_read' => '第:number巻を既読にしました。',
        'volume_marked_unread' => '第:number巻を未読にしました。',
        'all_already_read' => 'すべての巻がすでに既読です。',
        'marked_all_read' => ':count巻を既読にしました。',
        'undo_marked_read' => '変更を取り消しました。',
        'all_owned_already_read' => '所有しているすべての巻がすでに既読です。',
        'none_read_yet' => 'まだ既読にした巻がありません。',

        'quick_volume_added' => '第:number巻を追加しました。',
        'quick_no_volume_for_format' => 'このフォーマットの巻はまだありません。',
        'quick_top_volume_loaned' => 'このフォーマットの最新巻は現在貸出中のため削除できません。',
        'quick_volume_deleted' => '第:number巻を削除しました。',
    ],

    'dashboard' => [
        'funfact_quota_reached' => '週:max回までの再生成上限に達しました。来週また試してください。',
        'funfact_failed' => 'funfactの生成に失敗しました。しばらくしてからもう一度お試しください。',
        'funfact_rate_limited' => 'AIプロバイダーが現在レート制限中のため、一時的なfunfactを表示しています。後でもう一度生成してください。',
        'funfact_regenerated' => 'funfactを再生成しました。',
    ],

    'loans' => [
        'already_loaned' => 'この巻は現在貸出中です。',
        'recorded' => '貸出を記録しました。',
        'marked_returned' => '巻を返却済みにしました。',
    ],

    'profile' => [
        'followed' => ':nameをフォローしました。',
        'unfollowed' => ':nameのフォローを解除しました。',
    ],

    'collection_groups' => [
        'created' => 'グループを作成しました。',
        'renamed' => 'グループ名を変更しました。',
        'deleted' => 'グループを削除しました。',
        'items_added' => ':count件のマンガをグループに追加しました。',
        'item_removed' => 'マンガをグループから削除しました。',
        'item_restored' => 'マンガをグループに戻しました。',
        'item_restore_failed' => '元に戻せませんでした — コレクションがすでに存在しません。',
        'made_public' => 'グループを公開に設定しました — プロフィールに表示されます。',
        'made_private' => 'グループを非公開に設定しました。',
    ],

    'wishlist' => [
        'already_owned' => 'このシリーズはすでにコレクションにあります。',
        'added' => 'ウィッシュリストに追加しました。',
        'deleted' => 'ウィッシュリストから削除しました。',
        'restored' => 'ウィッシュリストのアイテムを復元しました。',
    ],
];
