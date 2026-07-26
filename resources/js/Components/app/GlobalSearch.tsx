import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import {
    BookOpen, HandCoins, LayoutDashboard, Library, Settings, Ticket,
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
    { label: 'Dashboard', routeName: 'dashboard', icon: LayoutDashboard },
    { label: 'Katalog', routeName: 'catalog.index', icon: BookOpen },
    { label: 'Koleksiku', routeName: 'collection.index', icon: Library },
    { label: 'Pinjaman Saya', routeName: 'loans.index', icon: HandCoins },
    { label: 'Tiket', routeName: 'tickets.index', icon: Ticket },
    { label: 'Pengaturan', routeName: 'settings.index', icon: Settings },
];

interface SearchResult { id: string; title: string }

export function GlobalSearch() {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [catalogResults, setCatalogResults] = useState<SearchResult[]>([]);
    const [collectionResults, setCollectionResults] = useState<SearchResult[]>([]);

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
        window.addEventListener('global-search:open', handleOpenEvent);
        return () => {
            document.removeEventListener('keydown', handleKeyDown);
            window.removeEventListener('global-search:open', handleOpenEvent);
        };
    }, []);

    useEffect(() => {
        if (!open) return;
        const q = query.trim();
        if (q.length < 2) {
            setCatalogResults([]);
            setCollectionResults([]);
            return;
        }
        const t = setTimeout(async () => {
            try {
                const url = new URL(route('search'), window.location.origin);
                url.searchParams.set('q', q);
                const res = await fetch(url.toString(), { credentials: 'same-origin' });
                const data: { catalog: SearchResult[]; collection: SearchResult[] } = await res.json();
                setCatalogResults(data.catalog);
                setCollectionResults(data.collection);
            } catch {
                setCatalogResults([]);
                setCollectionResults([]);
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
        <CommandDialog
            open={open}
            onOpenChange={setOpen}
            title="Cari"
            description="Cari manga di katalog atau koleksimu, atau navigasi cepat ke halaman lain"
        >
            <Command>
                <CommandInput
                    placeholder="Cari judul manga, koleksi, atau halaman..."
                    value={query}
                    onValueChange={setQuery}
                />
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

                    {collectionResults.length > 0 && (
                        <CommandGroup heading="Koleksiku">
                            {collectionResults.map((r) => (
                                <CommandItem
                                    key={`collection-${r.id}`}
                                    value={`collection-${r.id}`}
                                    onSelect={() => go('collection.show', r.id)}
                                >
                                    <Library />
                                    {r.title}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    )}

                    {catalogResults.length > 0 && (
                        <CommandGroup heading="Katalog">
                            {catalogResults.map((r) => (
                                <CommandItem
                                    key={`series-${r.id}`}
                                    value={`series-${r.id}`}
                                    onSelect={() => go('catalog.show', r.id)}
                                >
                                    <BookOpen />
                                    {r.title}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    )}
                </CommandList>
            </Command>
        </CommandDialog>
    );
}
