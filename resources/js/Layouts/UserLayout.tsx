import { PropsWithChildren, ReactNode, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import {
    BookOpen, HandCoins, Layers, LayoutDashboard, Library, LogOut,
    Menu as MenuIcon, Moon, Search, Settings, Sun, Ticket, User, X,
    type LucideIcon,
} from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';
import { ScrollArea } from '@/Components/ui/scroll-area';
import { useTheme } from '@/hooks/useTheme';
import { useFlash } from '@/hooks/useFlash';
import AnnouncementBanner from '@/Components/app/AnnouncementBanner';
import { SidebarNav } from '@/Components/app/SidebarNav';
import { GlobalSearch } from '@/Components/app/GlobalSearch';
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
};

function SidebarContent({ menus, onNavClick }: { menus: MenuItem[]; onNavClick?: () => void }) {
    const { auth } = usePage().props;
    const user = auth.user!;
    const { theme, toggleTheme } = useTheme();

    function handleLogout() {
        router.post(route('logout'));
    }

    return (
        <div className="flex h-full flex-col">
            <div className="flex h-14 items-center border-b px-5">
                <span className="text-base font-bold tracking-tight">MALAS</span>
            </div>

            <ScrollArea className="min-h-0 flex-1">
                <nav className="px-2 py-3">
                    <SidebarNav menus={menus} iconMap={ICON_MAP} onNavClick={onNavClick} />
                </nav>
            </ScrollArea>

            <div className="border-t px-2 py-3 space-y-0.5">
                <Button
                    variant="ghost"
                    size="sm"
                    className="w-full justify-start gap-3 text-muted-foreground"
                    onClick={toggleTheme}
                >
                    {theme === 'dark'
                        ? <Sun className="h-4 w-4" />
                        : <Moon className="h-4 w-4" />}
                    {theme === 'dark' ? 'Mode Terang' : 'Mode Gelap'}
                </Button>

                <div className="flex items-center gap-2.5 px-3 py-2">
                    <Avatar className="h-8 w-8 shrink-0">
                        <AvatarImage src={user.avatar || undefined} alt={user.name} />
                        <AvatarFallback className="text-xs">{user.name.slice(0, 2).toUpperCase()}</AvatarFallback>
                    </Avatar>
                    <div className="min-w-0">
                        <p className="text-sm font-medium truncate">{user.name}</p>
                        <p className="text-xs text-muted-foreground">Anggota</p>
                    </div>
                </div>

                <Button
                    variant="ghost"
                    size="sm"
                    className="w-full justify-start gap-3 text-muted-foreground hover:text-destructive"
                    onClick={handleLogout}
                >
                    <LogOut className="h-4 w-4" />
                    Keluar
                </Button>
            </div>
        </div>
    );
}

interface UserLayoutProps extends PropsWithChildren {
    header?: ReactNode;
}

export default function UserLayout({ children, header }: UserLayoutProps) {
    const { menus } = usePage().props;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    useFlash();

    function closeSidebar() {
        setSidebarOpen(false);
    }

    return (
        <div className="flex h-screen overflow-hidden bg-background">
            <GlobalSearch />

            {/* Desktop sidebar */}
            <aside className="hidden w-52 shrink-0 border-r bg-background lg:flex lg:flex-col">
                <SidebarContent menus={menus} />
            </aside>

            {/* Mobile sidebar overlay */}
            {sidebarOpen && (
                <>
                    <div
                        className="fixed inset-0 z-30 bg-black/50 lg:hidden"
                        onClick={closeSidebar}
                    />
                    <aside className="fixed inset-y-0 left-0 z-40 w-52 border-r bg-background lg:hidden">
                        <SidebarContent menus={menus} onNavClick={closeSidebar} />
                    </aside>
                </>
            )}

            {/* Main area */}
            <div className="flex flex-1 flex-col overflow-hidden">
                {/* Mobile topbar */}
                <header className="flex h-14 items-center gap-3 border-b px-4 lg:hidden">
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => setSidebarOpen(prev => !prev)}
                        aria-label="Toggle sidebar"
                    >
                        {sidebarOpen
                            ? <X className="h-5 w-5" />
                            : <MenuIcon className="h-5 w-5" />}
                    </Button>
                    <span className="flex-1 text-base font-bold">MALAS</span>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => window.dispatchEvent(new Event('global-search:open'))}
                        aria-label="Cari"
                    >
                        <Search className="h-5 w-5" />
                    </Button>
                </header>

                {/* Desktop topbar */}
                <header className="hidden h-14 items-center border-b px-6 lg:flex">
                    <div className="mx-auto flex w-full max-w-md justify-center">
                        <button
                            type="button"
                            onClick={() => window.dispatchEvent(new Event('global-search:open'))}
                            className="flex w-full items-center gap-2 rounded-lg border border-input/50 bg-muted/50 px-3 py-1.5 text-sm text-muted-foreground transition-colors hover:bg-accent"
                        >
                            <Search className="h-4 w-4" />
                            <span className="flex-1 text-left">Cari manga, koleksi, atau halaman...</span>
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

                <ScrollArea className="min-h-0 flex-1">
                    <main className="p-6">
                        {children}
                    </main>
                </ScrollArea>
            </div>
        </div>
    );
}
