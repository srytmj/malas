import { PropsWithChildren, ReactNode, useEffect, useRef, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import i18n from '@/lib/i18n';
import {
    BookOpen, HandCoins, Heart, Layers, LayoutDashboard, Library, LogOut,
    Menu as MenuIcon, Moon, PanelLeftClose, PanelLeftOpen, Search, Settings, Sun, Ticket, User, X,
    type LucideIcon,
} from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';
import { ScrollArea } from '@/Components/ui/scroll-area';
import {
    Sheet, SheetContent, SheetHeader, SheetTitle,
} from '@/Components/ui/sheet';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/Components/ui/tooltip';
import { useTheme } from '@/hooks/useTheme';
import { useFlash } from '@/hooks/useFlash';
import AnnouncementBanner from '@/Components/app/AnnouncementBanner';
import { SidebarNav } from '@/Components/app/SidebarNav';
import { GlobalSearch } from '@/Components/app/GlobalSearch';
import { LanguageSwitcher } from '@/Components/app/LanguageSwitcher';
import { cn } from '@/lib/utils';
import { type MenuItem } from '@/types';

const ICON_MAP: Record<string, LucideIcon> = {
    'layout-dashboard': LayoutDashboard,
    'book-open':        BookOpen,
    'library':          Library,
    'hand-coins':       HandCoins,
    'settings':         Settings,
    'ticket':           Ticket,
    'user':             User,
    'layers':           Layers,
    'heart':            Heart,
    'search':           Search,
};

interface SidebarContentProps {
    menus: MenuItem[];
    onNavClick?: () => void;
    collapsed?: boolean;
    onToggleCollapsed?: () => void;
}

function SidebarContent({ menus, onNavClick, collapsed, onToggleCollapsed }: SidebarContentProps) {
    const { auth } = usePage().props;
    const user = auth.user!;
    const { theme, toggleTheme } = useTheme();
    const { t } = useTranslation();

    function handleLogout() {
        router.post(route('logout'));
    }

    function handleOpenSearch() {
        window.dispatchEvent(new Event('global-search:open'));
        onNavClick?.();
    }

    return (
        <div className="flex h-full flex-col">
            <div className={cn('flex h-14 items-center border-b', collapsed ? 'justify-center px-2' : 'justify-between px-5')}>
                {!collapsed && <span className="text-base font-bold tracking-tight">MALAS</span>}
                {onToggleCollapsed && (
                    <Tooltip>
                        <TooltipTrigger
                            render={
                                <Button variant="ghost" size="icon-sm" onClick={onToggleCollapsed} aria-label={collapsed ? t('nav.expandSidebar') : t('nav.collapseSidebar')}>
                                    {collapsed ? <PanelLeftOpen className="h-4 w-4" /> : <PanelLeftClose className="h-4 w-4" />}
                                </Button>
                            }
                        />
                        <TooltipContent side="right">{collapsed ? t('nav.expandSidebar') : t('nav.collapseSidebar')}</TooltipContent>
                    </Tooltip>
                )}
            </div>

            <ScrollArea className="min-h-0 flex-1">
                <nav className="px-2 py-3">
                    <SidebarNav menus={menus} iconMap={ICON_MAP} onNavClick={onNavClick} collapsed={collapsed} />
                </nav>
            </ScrollArea>

            <div className="border-t px-2 py-3 space-y-0.5">
                <Button
                    variant="ghost"
                    size="sm"
                    className="w-full justify-start gap-3 text-muted-foreground lg:hidden"
                    onClick={handleOpenSearch}
                >
                    <Search className="h-4 w-4" />
                    {t('nav.search')}
                </Button>

                {collapsed ? (
                    <Tooltip>
                        <TooltipTrigger
                            render={
                                <Button variant="ghost" size="icon" className="mx-auto flex" onClick={toggleTheme} aria-label={theme === 'dark' ? t('nav.lightMode') : t('nav.darkMode')}>
                                    {theme === 'dark' ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
                                </Button>
                            }
                        />
                        <TooltipContent side="right">{theme === 'dark' ? t('nav.lightMode') : t('nav.darkMode')}</TooltipContent>
                    </Tooltip>
                ) : (
                    <Button
                        variant="ghost"
                        size="sm"
                        className="w-full justify-start gap-3 text-muted-foreground"
                        onClick={toggleTheme}
                    >
                        {theme === 'dark'
                            ? <Sun className="h-4 w-4" />
                            : <Moon className="h-4 w-4" />}
                        {theme === 'dark' ? t('nav.lightMode') : t('nav.darkMode')}
                    </Button>
                )}

                <LanguageSwitcher collapsed={collapsed} />

                {collapsed ? (
                    <Tooltip>
                        <TooltipTrigger
                            render={
                                <Link
                                    href={route('profile.show', user.username ?? user.id)}
                                    className="flex items-center justify-center rounded-md px-3 py-2 transition-colors hover:bg-muted"
                                >
                                    <Avatar className="h-8 w-8 shrink-0">
                                        <AvatarImage src={user.avatar || undefined} alt={user.name} />
                                        <AvatarFallback className="text-xs">{user.name.slice(0, 2).toUpperCase()}</AvatarFallback>
                                    </Avatar>
                                </Link>
                            }
                        />
                        <TooltipContent side="right">{user.name}</TooltipContent>
                    </Tooltip>
                ) : (
                    <Link
                        href={route('profile.show', user.username ?? user.id)}
                        className="flex items-center gap-2.5 rounded-md px-3 py-2 transition-colors hover:bg-muted"
                    >
                        <Avatar className="h-8 w-8 shrink-0">
                            <AvatarImage src={user.avatar || undefined} alt={user.name} />
                            <AvatarFallback className="text-xs">{user.name.slice(0, 2).toUpperCase()}</AvatarFallback>
                        </Avatar>
                        <div className="min-w-0">
                            <p className="text-sm font-medium truncate">{user.name}</p>
                            <p className="text-xs text-muted-foreground">{t('nav.member')}</p>
                        </div>
                    </Link>
                )}

                {collapsed ? (
                    <Tooltip>
                        <TooltipTrigger
                            render={
                                <Button variant="ghost" size="icon" className="mx-auto flex text-muted-foreground hover:text-destructive" onClick={handleLogout} aria-label={t('nav.logout')}>
                                    <LogOut className="h-4 w-4" />
                                </Button>
                            }
                        />
                        <TooltipContent side="right">{t('nav.logout')}</TooltipContent>
                    </Tooltip>
                ) : (
                    <Button
                        variant="ghost"
                        size="sm"
                        className="w-full justify-start gap-3 text-muted-foreground hover:text-destructive"
                        onClick={handleLogout}
                    >
                        <LogOut className="h-4 w-4" />
                        {t('nav.logout')}
                    </Button>
                )}
            </div>
        </div>
    );
}

interface UserLayoutProps extends PropsWithChildren {
    header?: ReactNode;
}

export default function UserLayout({ children, header }: UserLayoutProps) {
    const { menus, announcements, locale } = usePage().props;
    const { t } = useTranslation();
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [fabVisible, setFabVisible] = useState(true);
    const [collapsed, setCollapsed] = useState(() => (
        typeof window !== 'undefined' && window.localStorage.getItem('user-sidebar-collapsed') === '1'
    ));
    const scrollWrapRef = useRef<HTMLDivElement>(null);
    const lastScrollTop = useRef(0);
    useFlash();

    function closeSidebar() {
        setSidebarOpen(false);
    }

    function toggleCollapsed() {
        setCollapsed((prev) => {
            const next = !prev;
            window.localStorage.setItem('user-sidebar-collapsed', next ? '1' : '0');
            return next;
        });
    }

    useEffect(() => {
        const viewport = scrollWrapRef.current?.querySelector('[data-slot="scroll-area-viewport"]');
        if (!viewport) return;

        function onScroll() {
            const scrollTop = (viewport as HTMLElement).scrollTop;
            setFabVisible(scrollTop <= lastScrollTop.current || scrollTop < 80);
            lastScrollTop.current = scrollTop;
        }

        viewport.addEventListener('scroll', onScroll);
        return () => viewport.removeEventListener('scroll', onScroll);
    }, []);

    useEffect(() => {
        if (locale) void i18n.changeLanguage(locale);
    }, [locale]);

    return (
        <div className="flex h-screen overflow-hidden bg-background">
            <GlobalSearch />

            {/* Desktop sidebar */}
            <aside
                className={cn(
                    'hidden shrink-0 border-r bg-background transition-[width] duration-200 lg:flex lg:flex-col',
                    collapsed ? 'w-16' : 'w-52',
                )}
            >
                <SidebarContent menus={menus} collapsed={collapsed} onToggleCollapsed={toggleCollapsed} />
            </aside>

            {/* Mobile menu — popup bottom sheet, dipicu dari FAB, bukan panel slide-in dari sisi */}
            <Sheet open={sidebarOpen} onOpenChange={setSidebarOpen}>
                <SheetContent side="bottom" className="flex h-[85vh] flex-col gap-0 p-0 lg:hidden">
                    <SheetHeader className="sr-only">
                        <SheetTitle>{t('nav.navigation')}</SheetTitle>
                    </SheetHeader>
                    <SidebarContent menus={menus} onNavClick={closeSidebar} />
                </SheetContent>
            </Sheet>

            {/* Mobile floating menu button — satu-satunya elemen mengambang, buka panel menu (termasuk Cari) */}
            <Button
                size="icon"
                className={cn(
                    'fixed bottom-6 right-6 z-[60] h-14 w-14 rounded-full shadow-lg transition-all duration-200 lg:hidden',
                    fabVisible || sidebarOpen ? 'scale-100 opacity-100' : 'pointer-events-none scale-75 opacity-0',
                )}
                onClick={() => setSidebarOpen((prev) => !prev)}
                aria-label={t('nav.openMenu')}
            >
                {sidebarOpen ? <X className="h-6 w-6" /> : <MenuIcon className="h-6 w-6" />}
                {!sidebarOpen && announcements.length > 0 && (
                    <span className="absolute right-1 top-1 h-2.5 w-2.5 rounded-full bg-destructive ring-2 ring-background" />
                )}
            </Button>

            {/* Main area */}
            <div className="flex flex-1 flex-col overflow-hidden">
                {/* Desktop topbar */}
                <header className="hidden h-14 items-center border-b px-6 lg:flex">
                    <div className="mx-auto flex w-full max-w-md justify-center">
                        <button
                            type="button"
                            onClick={() => window.dispatchEvent(new Event('global-search:open'))}
                            className="flex w-full items-center gap-2 rounded-lg border border-input/50 bg-muted/50 px-3 py-1.5 text-sm text-muted-foreground transition-colors hover:bg-accent"
                        >
                            <Search className="h-4 w-4" />
                            <span className="flex-1 text-left">{t('nav.searchPlaceholder')}</span>
                            <kbd className="rounded border bg-background px-1 py-0.5 font-mono text-[10px]">⌘K</kbd>
                        </button>
                    </div>
                </header>

                <AnnouncementBanner />

                {header && (
                    <div className="border-b bg-background px-6 py-4">
                        {header}
                    </div>
                )}

                <div ref={scrollWrapRef} className="min-h-0 flex-1">
                    <ScrollArea className="h-full">
                        <main className="p-6">
                            {children}
                        </main>
                    </ScrollArea>
                </div>
            </div>
        </div>
    );
}
