import { type MenuItem } from '@/types';

/**
 * Menu bawaan sistem (di-seed lewat MenuSeeder) dipetakan ke translation key
 * `menu.*` di common.json — biar label sidebar ikut ganti bahasa. Menu key yang
 * nggak ada di map ini (mis. hasil rename manual admin) jatuh ke label DB apa adanya.
 */
const MENU_KEY_TRANSLATIONS: Record<string, string> = {
    'admin.dashboard': 'menu.dashboard',
    'admin.series': 'menu.series',
    'admin.collections': 'menu.collections',
    'admin.loans': 'menu.loans',
    'admin.users': 'menu.users',
    'category.anilist': 'menu.importData',
    'admin.search-external': 'menu.searchAllSources',
    'admin.anilist': 'menu.searchManga',
    'admin.anilist.status': 'menu.anilistStatus',
    'admin.ranobedb': 'menu.searchLightNovel',
    'category.lainnya': 'menu.others',
    'admin.menus': 'menu.menuManagement',
    'admin.menus.user-sidebar': 'menu.userSidebar',
    'admin.announcements': 'menu.announcements',
    'admin.tickets': 'menu.tickets',
    'admin.settings': 'menu.settings',
    'admin.activity-logs': 'menu.activityLog',
    'admin.funfact-quota': 'menu.funfactQuota',
    'user.dashboard': 'menu.dashboard',
    'user.catalog': 'menu.catalog',
    'user.collection': 'menu.myCollection',
    'user.wishlist': 'menu.wishlist',
    'user.loans': 'menu.myLoans',
    'user.directory': 'menu.findUsers',
    'user.tickets': 'menu.tickets',
    'settings': 'menu.profile',
};

export function menuTranslationKey(key: string): string | null {
    return MENU_KEY_TRANSLATIONS[key] ?? null;
}

/**
 * Ratakan menu (top-level + children) jadi satu daftar item yang bisa dinavigasi
 * (punya route_name), dengan urutan yang sama persis dengan yang dirender SidebarNav:
 * item top-level tanpa children masuk langsung, item top-level yang punya children
 * digantikan oleh children-nya (kategori sendiri biasanya tidak punya route_name).
 * Dipakai supaya urutan sidebar konsisten dengan Command Palette / Global Search (Ctrl+K).
 */
export function flattenMenuItems(menus: MenuItem[]): MenuItem[] {
    const topLevel = menus.filter((m) => !m.parent_key);
    const childrenByParent = new Map<string, MenuItem[]>();
    menus.filter((m) => m.parent_key).forEach((m) => {
        const arr = childrenByParent.get(m.parent_key!) ?? [];
        arr.push(m);
        childrenByParent.set(m.parent_key!, arr);
    });

    const result: MenuItem[] = [];
    topLevel.forEach((item) => {
        const children = childrenByParent.get(item.key);
        if (children && children.length > 0) {
            result.push(...children);
        } else if (item.route_name) {
            result.push(item);
        }
    });

    return result.filter((item) => !!item.route_name);
}
