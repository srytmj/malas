export interface User {
    id: string;
    name: string;
    email: string;
    email_verified_at?: string;
    role: 'super_admin' | 'admin' | 'user';
    is_banned: boolean;
    ban_reason: string | null;
}

export interface MenuItem {
    key: string;
    label: string;
    icon: string | null;
    route_name: string;
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
    flash: {
        success: string | null;
        error: string | null;
        info: string | null;
    };
    announcements: SharedAnnouncement[];
};
