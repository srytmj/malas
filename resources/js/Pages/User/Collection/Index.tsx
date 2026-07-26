import { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    BookOpen, Eye, LayoutGrid, Library, List, Loader2, Plus, RefreshCw, Search, Trash2,
} from 'lucide-react';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle, EmptyDescription, EmptyContent } from '@/Components/ui/empty';
import { AdultBlurOverlay } from '@/Components/app/AdultBlurOverlay';
import { SeriesStatusBadge } from '@/Components/app/StatusBadge';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { ScrollArea } from '@/Components/ui/scroll-area';
import { HoverCard, HoverCardContent, HoverCardTrigger } from '@/Components/ui/hover-card';
import {
    ContextMenu, ContextMenuContent, ContextMenuItem, ContextMenuSeparator, ContextMenuTrigger,
} from '@/Components/ui/context-menu';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import { type SeriesStatus, type SeriesType } from '@/lib/types';

interface CollectionRow {
    id: string;
    series_id: string;
    title_romaji: string;
    title_english: string | null;
    cover_url: string | null;
    total_volumes: number | null;
    collection_volumes_count: number;
    read_volumes_count: number;
    status: SeriesStatus;
    type: SeriesType;
    genres: string[];
    condition: 'mint' | 'good' | 'fair' | 'poor';
    created_at: string;
    is_adult: boolean;
}

interface SeriesResult {
    id: string;
    title_romaji: string;
    title_english: string | null;
    cover_url: string | null;
    type: SeriesType;
    status: SeriesStatus;
}

interface Props extends PageProps {
    collections: CollectionRow[];
}

const STATUS_OPTIONS: { value: SeriesStatus; label: string }[] = [
    { value: 'publishing',        label: 'Publishing' },
    { value: 'finished',          label: 'Selesai' },
    { value: 'on_hiatus',         label: 'Hiatus' },
    { value: 'discontinued',      label: 'Discontinued' },
    { value: 'not_yet_published', label: 'Belum Terbit' },
];

const SORT_OPTIONS = [
    { value: 'added_desc',   label: 'Baru ditambahkan' },
    { value: 'added_asc',    label: 'Lama ditambahkan' },
    { value: 'name_asc',     label: 'Nama A-Z' },
    { value: 'name_desc',    label: 'Nama Z-A' },
    { value: 'volumes_desc', label: 'Volume terbanyak' },
    { value: 'volumes_asc',  label: 'Volume tersedikit' },
] as const;

type ViewMode = 'grid' | 'table';
type SortValue = typeof SORT_OPTIONS[number]['value'];

const VIEW_KEY = 'malas.collection.view';
const SORT_KEY = 'malas.collection.sort';

function readLocal<T extends string>(key: string, fallback: T): T {
    if (typeof window === 'undefined') return fallback;
    return (window.localStorage.getItem(key) as T) || fallback;
}

export default function CollectionIndex({ collections }: Props) {
    const [deleteTarget, setDeleteTarget]   = useState<CollectionRow | null>(null);
    const [deleting, setDeleting]           = useState(false);
    const [filterQuery, setFilterQuery]     = useState('');
    const [statusFilter, setStatusFilter]   = useState('');
    const [view, setView]                   = useState<ViewMode>(() => readLocal<ViewMode>(VIEW_KEY, 'grid'));
    const [sort, setSort]                   = useState<SortValue>(() => readLocal<SortValue>(SORT_KEY, 'added_desc'));
    const [refreshing, setRefreshing]       = useState(false);

    useEffect(() => {
        window.localStorage.setItem(VIEW_KEY, view);
    }, [view]);

    useEffect(() => {
        window.localStorage.setItem(SORT_KEY, sort);
    }, [sort]);

    function handleRefresh() {
        setRefreshing(true);
        router.reload({ onFinish: () => setRefreshing(false) });
    }

    const filteredCollections = collections
        .filter((c) => {
            const q = filterQuery.trim().toLowerCase();
            const matchesQuery = !q
                || c.title_romaji.toLowerCase().includes(q)
                || (c.title_english?.toLowerCase().includes(q) ?? false);
            const matchesStatus = !statusFilter || c.status === statusFilter;
            return matchesQuery && matchesStatus;
        })
        .sort((a, b) => {
            switch (sort) {
                case 'name_asc': return a.title_romaji.localeCompare(b.title_romaji);
                case 'name_desc': return b.title_romaji.localeCompare(a.title_romaji);
                case 'added_asc': return a.created_at.localeCompare(b.created_at);
                case 'volumes_desc': return b.collection_volumes_count - a.collection_volumes_count;
                case 'volumes_asc': return a.collection_volumes_count - b.collection_volumes_count;
                case 'added_desc':
                default: return b.created_at.localeCompare(a.created_at);
            }
        });

    // Add series dialog
    const [addOpen, setAddOpen]             = useState(false);
    const [searchQuery, setSearchQuery]     = useState('');
    const [searchResults, setSearchResults] = useState<SeriesResult[]>([]);
    const [searchLoading, setSearchLoading] = useState(false);
    const [selectedIds, setSelectedIds]     = useState<Set<string>>(new Set());
    const [inCollectionIds, setInCollectionIds] = useState<Set<string>>(
        new Set(collections.map((c) => c.series_id)),
    );
    const [adding, setAdding]               = useState(false);

    // Debounced search
    useEffect(() => {
        if (!addOpen) return;
        setSearchResults([]);
        setSearchLoading(true);
        const t = setTimeout(async () => {
            try {
                const url = new URL(route('catalog.search'), window.location.origin);
                if (searchQuery.trim()) url.searchParams.set('q', searchQuery.trim());
                const res  = await fetch(url.toString(), { credentials: 'same-origin' });
                const data: { results: SeriesResult[]; collection_series_ids: string[] } = await res.json();
                setSearchResults(data.results);
                setInCollectionIds(new Set(data.collection_series_ids));
            } catch {
                setSearchResults([]);
            } finally {
                setSearchLoading(false);
            }
        }, 350);
        return () => clearTimeout(t);
    }, [searchQuery, addOpen]);

    function toggleSelect(id: string) {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            next.has(id) ? next.delete(id) : next.add(id);
            return next;
        });
    }

    function handleAdd() {
        if (selectedIds.size === 0) return;
        setAdding(true);
        router.post(
            route('collection.store'),
            { series_ids: Array.from(selectedIds) },
            {
                onSuccess: () => {
                    setAddOpen(false);
                    setSelectedIds(new Set());
                    setSearchQuery('');
                },
                onFinish: () => setAdding(false),
            },
        );
    }

    function handleDelete() {
        if (!deleteTarget) return;
        setDeleting(true);
        router.delete(route('collection.destroy', deleteTarget.id), {
            onFinish: () => { setDeleting(false); setDeleteTarget(null); },
        });
    }

    function GenreBadges({ genres }: { genres: string[] }) {
        if (genres.length === 0) return null;
        const shown = genres.slice(0, 3);
        const rest  = genres.length - shown.length;
        return (
            <div className="flex flex-wrap gap-1">
                {shown.map((g) => (
                    <Badge key={g} variant="outline" className="text-[10px] px-1.5 py-0">{g}</Badge>
                ))}
                {rest > 0 && (
                    <Badge variant="outline" className="text-[10px] px-1.5 py-0 text-muted-foreground">+{rest}</Badge>
                )}
            </div>
        );
    }

    return (
        <UserLayout
            header={
                <PageHeader
                    title="Koleksiku"
                    description={`${collections.length} series dalam koleksi`}
                    actions={
                        <Button size="sm" onClick={() => { setAddOpen(true); setSearchQuery(''); setSelectedIds(new Set()); }}>
                            <Plus className="mr-1.5 h-3.5 w-3.5" />
                            Tambah Series
                        </Button>
                    }
                />
            }
        >
            <Head title="Koleksiku" />
            {collections.length === 0 ? (
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <Library />
                        </EmptyMedia>
                        <EmptyTitle>Koleksi masih kosong</EmptyTitle>
                        <EmptyDescription>
                            Tambahkan series untuk mulai melacak volume yang kamu miliki.
                        </EmptyDescription>
                    </EmptyHeader>
                    <EmptyContent>
                        <Button onClick={() => setAddOpen(true)}>
                            <Plus className="mr-1.5 h-4 w-4" />
                            Tambah Series
                        </Button>
                        <p className="text-sm text-muted-foreground">
                            Koleksimu belum ada di katalog?{' '}
                            <Link
                                href={route('tickets.create')}
                                className="font-medium text-primary underline-offset-4 hover:underline"
                            >
                                Request lewat tiket
                            </Link>
                        </p>
                    </EmptyContent>
                </Empty>
            ) : (
                <>
                    <div className="mb-4 flex flex-wrap items-center gap-2">
                        <div className="relative max-w-sm flex-1">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                className="pl-9"
                                placeholder="Cari di koleksimu..."
                                value={filterQuery}
                                onChange={(e) => setFilterQuery(e.target.value)}
                            />
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            onClick={handleRefresh}
                            disabled={refreshing}
                            aria-label="Segarkan"
                        >
                            <RefreshCw className={cn('h-4 w-4', refreshing && 'animate-spin')} />
                        </Button>
                        <Select value={statusFilter} onValueChange={(v) => setStatusFilter(v ?? '')}>
                            <SelectTrigger className="w-40">
                                <SelectValue placeholder="Semua status">
                                    {(value: string) => STATUS_OPTIONS.find((s) => s.value === value)?.label ?? 'Semua status'}
                                </SelectValue>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">Semua status</SelectItem>
                                {STATUS_OPTIONS.map((s) => (
                                    <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select value={sort} onValueChange={(v) => setSort((v ?? 'added_desc') as SortValue)}>
                            <SelectTrigger className="w-44">
                                <SelectValue placeholder="Urutkan">
                                    {(value: string) => SORT_OPTIONS.find((s) => s.value === value)?.label ?? 'Urutkan'}
                                </SelectValue>
                            </SelectTrigger>
                            <SelectContent>
                                {SORT_OPTIONS.map((s) => (
                                    <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <div className="ml-auto flex items-center gap-1 rounded-md border p-0.5">
                            <Button
                                type="button"
                                variant={view === 'grid' ? 'secondary' : 'ghost'}
                                size="icon"
                                className="h-7 w-7"
                                onClick={() => setView('grid')}
                                aria-label="Tampilan grid"
                            >
                                <LayoutGrid className="h-3.5 w-3.5" />
                            </Button>
                            <Button
                                type="button"
                                variant={view === 'table' ? 'secondary' : 'ghost'}
                                size="icon"
                                className="h-7 w-7"
                                onClick={() => setView('table')}
                                aria-label="Tampilan tabel"
                            >
                                <List className="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>

                    {filteredCollections.length === 0 ? (
                        <p className="py-12 text-center text-sm text-muted-foreground">
                            Tidak ada koleksi yang cocok dengan filter saat ini.
                        </p>
                    ) : view === 'grid' ? (
                        <div className="grid gap-4 [grid-template-columns:repeat(auto-fill,minmax(160px,1fr))]">
                            {filteredCollections.map((c) => (
                                <div
                                    key={c.id}
                                    className="group flex flex-col overflow-hidden rounded-lg border bg-card transition-shadow hover:shadow-md cursor-pointer"
                                    onClick={() => router.visit(route('collection.show', c.id))}
                                >
                                    <AdultBlurOverlay isAdult={c.is_adult} className="aspect-[2/3] w-full overflow-hidden bg-muted">
                                        {c.cover_url ? (
                                            <img
                                                src={c.cover_url}
                                                alt={c.title_romaji}
                                                className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                            />
                                        ) : (
                                            <div className="flex h-full items-center justify-center">
                                                <BookOpen className="h-10 w-10 text-muted-foreground/40" />
                                            </div>
                                        )}
                                    </AdultBlurOverlay>

                                    <div className="flex flex-1 flex-col gap-1.5 p-3">
                                        <p className="line-clamp-2 text-sm font-medium leading-tight">{c.title_romaji}</p>
                                        {c.title_english && (
                                            <p className="line-clamp-1 text-xs text-muted-foreground">{c.title_english}</p>
                                        )}
                                        <div className="flex flex-wrap items-center gap-1.5">
                                            <SeriesStatusBadge status={c.status} />
                                        </div>
                                        <GenreBadges genres={c.genres} />

                                        <div className="mt-auto flex items-center justify-between pt-1.5">
                                            <p className="text-xs text-muted-foreground">
                                                <span className="font-medium text-foreground">{c.collection_volumes_count}</span>
                                                {c.total_volumes ? `/${c.total_volumes}` : ''} vol.
                                            </p>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-7 w-7 text-destructive/60 hover:text-destructive"
                                                onClick={(e) => { e.stopPropagation(); setDeleteTarget(c); }}
                                            >
                                                <Trash2 className="h-3.5 w-3.5" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-12" />
                                        <TableHead>Judul</TableHead>
                                        <TableHead className="w-36">Status</TableHead>
                                        <TableHead>Genre</TableHead>
                                        <TableHead className="w-24 text-right">Volume</TableHead>
                                        <TableHead className="w-12" />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {filteredCollections.map((c) => (
                                        <ContextMenu key={c.id}>
                                            <ContextMenuTrigger
                                                onClick={() => router.visit(route('collection.show', c.id))}
                                                render={<TableRow className="cursor-pointer" />}
                                            >
                                                <TableCell>
                                                    <AdultBlurOverlay isAdult={c.is_adult} className="h-10 w-7 overflow-hidden rounded bg-muted">
                                                        {c.cover_url && (
                                                            <img src={c.cover_url} alt={c.title_romaji} className="h-full w-full object-cover" />
                                                        )}
                                                    </AdultBlurOverlay>
                                                </TableCell>
                                                <TableCell onClick={(e) => e.stopPropagation()}>
                                                    <HoverCard>
                                                        <HoverCardTrigger
                                                            render={<Link href={route('collection.show', c.id)} className="font-medium hover:underline" />}
                                                        >
                                                            {c.title_romaji}
                                                        </HoverCardTrigger>
                                                        <HoverCardContent>
                                                            <div className="flex gap-3">
                                                                <div className="h-24 w-16 shrink-0 overflow-hidden rounded bg-muted">
                                                                    {c.cover_url ? (
                                                                        <img src={c.cover_url} alt={c.title_romaji} className="h-full w-full object-cover" />
                                                                    ) : null}
                                                                </div>
                                                                <div className="min-w-0 space-y-1.5">
                                                                    <p className="font-medium leading-tight">{c.title_romaji}</p>
                                                                    <SeriesStatusBadge status={c.status} />
                                                                    <GenreBadges genres={c.genres} />
                                                                </div>
                                                            </div>
                                                        </HoverCardContent>
                                                    </HoverCard>
                                                    {c.title_english && (
                                                        <p className="text-xs text-muted-foreground">{c.title_english}</p>
                                                    )}
                                                </TableCell>
                                                <TableCell><SeriesStatusBadge status={c.status} /></TableCell>
                                                <TableCell><GenreBadges genres={c.genres} /></TableCell>
                                                <TableCell className="text-right">
                                                    <p>{c.collection_volumes_count}{c.total_volumes ? `/${c.total_volumes}` : ''}</p>
                                                    {c.collection_volumes_count > 0 && (
                                                        <p className="text-xs text-muted-foreground">
                                                            {c.read_volumes_count}/{c.collection_volumes_count} dibaca
                                                        </p>
                                                    )}
                                                </TableCell>
                                                <TableCell onClick={(e) => e.stopPropagation()}>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-7 w-7 text-destructive/60 hover:text-destructive"
                                                        onClick={() => setDeleteTarget(c)}
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                </TableCell>
                                            </ContextMenuTrigger>
                                            <ContextMenuContent>
                                                <ContextMenuItem onClick={() => router.visit(route('collection.show', c.id))}>
                                                    <Eye className="mr-2 h-4 w-4" />
                                                    Lihat
                                                </ContextMenuItem>
                                                <ContextMenuSeparator />
                                                <ContextMenuItem variant="destructive" onClick={() => setDeleteTarget(c)}>
                                                    <Trash2 className="mr-2 h-4 w-4" />
                                                    Hapus dari Koleksi
                                                </ContextMenuItem>
                                            </ContextMenuContent>
                                        </ContextMenu>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </>
            )}

            {/* Add Series Dialog */}
            <Dialog open={addOpen} onOpenChange={(open) => { setAddOpen(open); if (!open) { setSelectedIds(new Set()); setSearchQuery(''); } }}>
                <DialogContent className="max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>Tambah Series ke Koleksi</DialogTitle>
                        <DialogDescription>Pilih satu atau lebih series. Klik gambar untuk memilih.</DialogDescription>
                    </DialogHeader>

                    <div className="relative">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            className="pl-9"
                            placeholder="Cari judul series..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            autoFocus
                        />
                    </div>

                    <ScrollArea className="max-h-[480px]">
                        {searchLoading && (
                            <div className="flex items-center justify-center py-10 text-muted-foreground">
                                <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                                Mencari...
                            </div>
                        )}

                        {!searchLoading && searchResults.length === 0 && (
                            <div className="py-8 text-center">
                                <p className="text-sm text-muted-foreground">
                                    {searchQuery.trim() ? 'Tidak ada hasil.' : 'Ketik untuk mencari series.'}
                                </p>
                                {searchQuery.trim() && (
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        Judul tidak ditemukan?{' '}
                                        <Link
                                            href={route('tickets.create')}
                                            className="font-medium text-primary underline-offset-4 hover:underline"
                                            onClick={() => setAddOpen(false)}
                                        >
                                            Request lewat tiket
                                        </Link>
                                    </p>
                                )}
                            </div>
                        )}

                        {!searchLoading && searchResults.length > 0 && (
                            <div className="grid grid-cols-3 gap-2 pb-1 sm:grid-cols-5 lg:grid-cols-6">
                                {searchResults.map((s) => {
                                    const isInCollection = inCollectionIds.has(s.id);
                                    const isSelected     = selectedIds.has(s.id);
                                    return (
                                        <button
                                            key={s.id}
                                            type="button"
                                            disabled={isInCollection}
                                            onClick={() => !isInCollection && toggleSelect(s.id)}
                                            className={cn(
                                                'group relative flex flex-col overflow-hidden rounded-lg border text-left transition-all focus:outline-none focus:ring-2 focus:ring-ring',
                                                isInCollection && 'opacity-40 cursor-not-allowed',
                                                isSelected && 'ring-2 ring-primary border-primary',
                                                !isInCollection && !isSelected && 'hover:ring-2 hover:ring-primary/50',
                                            )}
                                        >
                                            <div className="relative aspect-[2/3] overflow-hidden bg-muted">
                                                {s.cover_url ? (
                                                    <img src={s.cover_url} alt={s.title_romaji} className="h-full w-full object-cover" />
                                                ) : (
                                                    <div className="flex h-full items-center justify-center">
                                                        <BookOpen className="h-6 w-6 text-muted-foreground/30" />
                                                    </div>
                                                )}
                                                {isSelected && (
                                                    <div className="absolute inset-0 flex items-center justify-center bg-primary/30">
                                                        <div className="rounded-full bg-primary p-1.5">
                                                            <svg className="h-4 w-4 text-primary-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        </div>
                                                    </div>
                                                )}
                                                {isInCollection && (
                                                    <div className="absolute bottom-1 right-1">
                                                        <span className="rounded bg-black/60 px-1 py-0.5 text-[10px] text-white">Dimiliki</span>
                                                    </div>
                                                )}
                                            </div>
                                            <div className="p-1.5">
                                                <p className="line-clamp-2 text-xs font-medium leading-tight">{s.title_romaji}</p>
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>
                        )}
                    </ScrollArea>

                    <DialogFooter className="items-center gap-2">
                        {selectedIds.size > 0 && (
                            <p className="mr-auto text-sm text-muted-foreground">{selectedIds.size} dipilih</p>
                        )}
                        <Button variant="outline" onClick={() => setAddOpen(false)}>Batal</Button>
                        <Button disabled={selectedIds.size === 0 || adding} onClick={handleAdd}>
                            {adding ? 'Menambahkan...' : `Tambah${selectedIds.size > 0 ? ` (${selectedIds.size})` : ''}`}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete dialog */}
            <Dialog open={!!deleteTarget} onOpenChange={(open) => !open && setDeleteTarget(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus dari Koleksi</DialogTitle>
                        <DialogDescription>
                            Yakin ingin menghapus <strong>{deleteTarget?.title_romaji}</strong> dari koleksimu?
                            Semua data volume dan pinjaman aktif akan hilang.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteTarget(null)}>Batal</Button>
                        <Button variant="destructive" disabled={deleting} onClick={handleDelete}>
                            {deleting ? 'Menghapus...' : 'Hapus'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </UserLayout>
    );
}
