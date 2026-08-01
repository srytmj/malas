// Domain types shared across admin and user pages.
// Inertia page props / auth types live in @/types/index.d.ts.

export type SeriesStatus          = 'publishing' | 'finished' | 'on_hiatus' | 'discontinued' | 'not_yet_published';
export type SeriesType            = 'manga' | 'manhwa' | 'manhua' | 'novel' | 'one_shot' | 'doujinshi';
export type VolumeType            = 'regular' | 'digital' | 'bind_up';
export type CollectionCondition   = 'mint' | 'good' | 'fair' | 'poor';
export type CollectionVolumeFormat = 'physical' | 'ebook' | 'online' | 'webtoon';
export type EbookSource           = 'bookwalker' | 'amazon' | 'local_epub';
export type VolumeLanguage        = 'id' | 'en' | 'ja' | 'other';
export type TicketType            = 'catalog_request' | 'title_revision' | 'other';
export type TicketStatus          = 'open' | 'in_progress' | 'resolved' | 'closed';

export interface Series {
    id: string;
    mal_id: number | null;
    title_romaji: string;
    title_english: string | null;
    title_japanese: string | null;
    synopsis: string | null;
    cover_path: string | null;
    status: SeriesStatus;
    type: SeriesType;
    published_from: string | null;
    published_to: string | null;
    total_volumes: number | null;
    score: number | null;
    rank: number | null;
    created_at: string;
    updated_at: string;
}

export interface Volume {
    id: string;
    series_id: string;
    volume_number: number;
    cover_path: string | null;
    type: VolumeType;
    digital_source: string | null;
    isbn: string | null;
    published_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface Collection {
    id: string;
    user_id: string;
    series_id: string;
    condition: CollectionCondition;
    acquired_at: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    series?: Series;
}

export interface Loan {
    id: string;
    collection_id: string;
    collection_volume_id: string;
    borrower_name: string;
    loaned_at: string;
    due_at: string | null;
    returned_at: string | null;
    notes: string | null;
    is_overdue: boolean;
    created_at: string;
    updated_at: string;
}

export interface Announcement {
    id: string;
    title: string;
    body: string;
    type: 'info' | 'warning' | 'danger' | 'success';
    is_active: boolean;
    starts_at: string | null;
    expires_at: string | null;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
    next_page_url: string | null;
    prev_page_url: string | null;
}
