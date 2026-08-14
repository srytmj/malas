export interface User {
    id: string;
    name: string;
    username: string | null;
    email: string;
    avatar: string | null;
    role: 'super_admin' | 'admin' | 'user';
    is_banned: boolean;
    ban_reason: string | null;
    is_profile_public: boolean;
    theme: 'light' | 'dark' | 'system';
}

export interface LinkedAccount {
    id: string;
    name: string;
    username: string | null;
    avatar: string | null;
    role: 'super_admin' | 'admin' | 'user';
}

export interface MenuItem {
    key: string;
    label: string;
    icon: string | null;
    route_name: string | null;
    parent_key: string | null;
    sort_order: number;
    is_maintenance: boolean;
}

export interface SharedAnnouncement {
    id: string;
    title: string;
    body: string;
    type: 'info' | 'warning' | 'danger' | 'success';
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
    };
    menus: MenuItem[];
    linked_accounts: LinkedAccount[];
    locale: 'id' | 'en' | 'ja';
    site_settings: {
        blur_adult_content: boolean;
    };
    flash: {
        success: string | null;
        error: string | null;
        info: string | null;
        undo_url: string | null;
        undo_payload: Record<string, string[] | string | number | null> | null;
    };
    announcements: SharedAnnouncement[];
};
