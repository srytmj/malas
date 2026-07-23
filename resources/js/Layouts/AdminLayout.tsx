import { PropsWithChildren, ReactNode, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import {
    Activity, BookOpen, Database, HandCoins, HardDrive, LayoutDashboard, Library, LogOut,
    Megaphone, Menu as MenuIcon, Moon, Search, Settings, Sun, Ticket, Users, X,
    type LucideIcon,
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { ScrollArea } from '@/Components/ui/scroll-area';
import { cn } from '@/lib/utils';
import { useTheme } from '@/hooks/useTheme';
import { useFlash } from '@/hooks/useFlash';
import AnnouncementBanner from '@/Components/app/AnnouncementBanner';
import { type MenuItem } from '@/types';

const ICON_MAP: Record<string, LucideIcon> = {
    'layout-dashboard': LayoutDashboard,
    'book-open':        BookOpen,
    'library':          Library,
    'hand-coins':       HandCoins,
    'users':            Users,
    'menu':             MenuIcon,
    'megaphone':        Megaphone,
    'search':           Search,
    'settings':         Settings,
    'activity':         Activity,
    'ticket':           Ticket,
    'hard-drive':       HardDrive,
    'database':         Database,
};

function NavItem({ item, onClick }: { item: MenuItem; onClick?: () => void }) {
    const Icon    = item.icon ? (ICON_MAP[item.icon] ?? null) : null;
    const isActive = route().current(item.route_name);
    const href    = route().has(item.route_name) ? route(item.route_name) : '#';

    return (
        <Link
            href={href}
            onClick={onClick}
            className={cn(
                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                isActive
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
            )}
        >
            {Icon && <Icon className="h-4 w-4 shrink-0" />}
            <span>{item.label}</span>
            {item.is_maintenance && (
                <span className="ml-auto text-[10px] text-yellow-500">●</span>
            )}
        </Link>
    );
}

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
                    <p className="mb-1 px-3 text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">
                        Navigasi
                    </p>
                    <div className="space-y-0.5">
                        {menus.map(item => (
                            <NavItem key={item.key} item={item} onClick={onNavClick} />
                        ))}
                    </div>
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

                <div className="px-3 py-2">
                    <p className="text-sm font-medium truncate">{user.name}</p>
                    <p className="text-xs text-muted-foreground capitalize">
                        {user.role.replace('_', ' ')}
                    </p>
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

interface AdminLayoutProps extends PropsWithChildren {
    header?: ReactNode;
}

export default function AdminLayout({ children, header }: AdminLayoutProps) {
    const { menus } = usePage().props;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    useFlash();

    function closeSidebar() {
        setSidebarOpen(false);
    }

    return (
        <div className="flex h-screen overflow-hidden bg-background">
            {/* Desktop sidebar */}
            <aside className="hidden w-56 shrink-0 border-r bg-background lg:flex lg:flex-col">
                <SidebarContent menus={menus} />
            </aside>

            {/* Mobile sidebar overlay */}
            {sidebarOpen && (
                <>
                    <div
                        className="fixed inset-0 z-30 bg-black/50 lg:hidden"
                        onClick={closeSidebar}
                    />
                    <aside className="fixed inset-y-0 left-0 z-40 w-56 border-r bg-background lg:hidden">
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
                    <span className="text-base font-bold">MALAS</span>
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
