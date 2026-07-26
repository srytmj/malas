import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import {
    Activity, BookOpen, Database, HandCoins, HardDrive, LayoutDashboard, Library,
    Megaphone, Menu as MenuIcon, Search, Sparkles, Ticket, Users,
    type LucideIcon,
} from 'lucide-react';
import {
    Command, CommandDialog, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList,
} from '@/Components/ui/command';

interface NavItem {
    label: string;
    routeName: string;
    icon: LucideIcon;
}

const NAV_ITEMS: NavItem[] = [
    { label: 'Dashboard', routeName: 'admin.dashboard', icon: LayoutDashboard },
    { label: 'Series', routeName: 'admin.series.index', icon: BookOpen },
    { label: 'Pengguna', routeName: 'admin.users.index', icon: Users },
    { label: 'Semua Koleksi', routeName: 'admin.collections.index', icon: Library },
    { label: 'Semua Pinjaman', routeName: 'admin.loans.index', icon: HandCoins },
    { label: 'Tiket', routeName: 'admin.tickets.index', icon: Ticket },
    { label: 'Pengumuman', routeName: 'admin.announcements.index', icon: Megaphone },
    { label: 'Menu', routeName: 'admin.menus.index', icon: MenuIcon },
    { label: 'Log Aktivitas', routeName: 'admin.activity-logs.index', icon: Activity },
    { label: 'Import AniList', routeName: 'admin.anilist.index', icon: Sparkles },
    { label: 'Pengaturan', routeName: 'admin.settings.index', icon: HardDrive },
];

interface SeriesResult { id: string; title: string }
interface UserResult { id: string; name: string; email: string }
interface TicketResult { id: string; subject: string }

export function CommandPalette() {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [series, setSeries] = useState<SeriesResult[]>([]);
    const [users, setUsers] = useState<UserResult[]>([]);
    const [tickets, setTickets] = useState<TicketResult[]>([]);

    useEffect(() => {
        function handleKeyDown(e: KeyboardEvent) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                setOpen((o) => !o);
            }
        }
        function handleOpenEvent() {
            setOpen(true);
        }
        document.addEventListener('keydown', handleKeyDown);
        window.addEventListener('command-palette:open', handleOpenEvent);
        return () => {
            document.removeEventListener('keydown', handleKeyDown);
            window.removeEventListener('command-palette:open', handleOpenEvent);
        };
    }, []);

    useEffect(() => {
        if (!open) return;
        const q = query.trim();
        if (q.length < 2) {
            setSeries([]);
            setUsers([]);
            setTickets([]);
            return;
        }
        const t = setTimeout(async () => {
            try {
                const url = new URL(route('admin.command-search'), window.location.origin);
                url.searchParams.set('q', q);
                const res = await fetch(url.toString(), { credentials: 'same-origin' });
                const data: { series: SeriesResult[]; users: UserResult[]; tickets: TicketResult[] } = await res.json();
                setSeries(data.series);
                setUsers(data.users);
                setTickets(data.tickets);
            } catch {
                setSeries([]);
                setUsers([]);
                setTickets([]);
            }
        }, 300);
        return () => clearTimeout(t);
    }, [query, open]);

    function go(routeName: string, param?: string) {
        setOpen(false);
        setQuery('');
        router.visit(param ? route(routeName, param) : route(routeName));
    }

    return (
        <CommandDialog open={open} onOpenChange={setOpen} title="Command Palette" description="Navigasi cepat ke halaman admin">
            <Command>
                <CommandInput placeholder="Cari halaman, series, pengguna, atau tiket..." value={query} onValueChange={setQuery} />
                <CommandList>
                    <CommandEmpty>Tidak ada hasil.</CommandEmpty>
                    <CommandGroup heading="Navigasi">
                        {NAV_ITEMS.map((item) => (
                            <CommandItem key={item.routeName} value={item.label} onSelect={() => go(item.routeName)}>
                                <item.icon />
                                {item.label}
                            </CommandItem>
                        ))}
                    </CommandGroup>

                    {series.length > 0 && (
                        <CommandGroup heading="Series">
                            {series.map((s) => (
                                <CommandItem key={s.id} value={`series-${s.id}`} onSelect={() => go('admin.series.show', s.id)}>
                                    <BookOpen />
                                    {s.title}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    )}

                    {users.length > 0 && (
                        <CommandGroup heading="Pengguna">
                            {users.map((u) => (
                                <CommandItem key={u.id} value={`user-${u.id}`} onSelect={() => go('admin.users.show', u.id)}>
                                    <Users />
                                    {u.name}
                                    <span className="ml-1 text-xs text-muted-foreground">{u.email}</span>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    )}

                    {tickets.length > 0 && (
                        <CommandGroup heading="Tiket">
                            {tickets.map((t) => (
                                <CommandItem key={t.id} value={`ticket-${t.id}`} onSelect={() => go('admin.tickets.show', t.id)}>
                                    <Search />
                                    {t.subject}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    )}
                </CommandList>
            </Command>
        </CommandDialog>
    );
}
