import { useEffect, useMemo, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import {
    BookOpen, Download, Eye, LayoutGrid, Layers, Library, List, Loader2, Minus, Plus, RefreshCw, Search, Trash2, Upload,
} from 'lucide-react';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle, EmptyDescription, EmptyContent } from '@/Components/ui/empty';
import { AdultBlurOverlay } from '@/Components/app/AdultBlurOverlay';
import { SeriesStatusBadge } from '@/Components/app/StatusBadge';
import { Badge } from '@/Components/ui/badge';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { ScrollArea } from '@/Components/ui/scroll-area';
import { HoverCard, HoverCardContent, HoverCardTrigger } from '@/Components/ui/hover-card';
import {
    ContextMenu, ContextMenuContent, ContextMenuItem, ContextMenuSeparator, ContextMenuTrigger,
} from '@/Components/ui/context-menu';
import {
    Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectSeparator, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { cn } from '@/lib/utils';
import { useTypeFilterOptions } from '@/lib/typeFilters';
import { PageProps } from '@/types';
import { type SeriesStatus, type SeriesType } from '@/lib/types';

interface CollectionRow {
    id: string;
    series_id: string;
    slug: string;
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
    missing_volumes: number[];
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

const SORT_VALUES = ['added_desc', 'added_asc', 'name_asc', 'name_desc', 'volumes_desc', 'volumes_asc'] as const;

type ViewMode = 'grid' | 'table';
type SortValue = typeof SORT_VALUES[number];

const VIEW_KEY = 'malas.collection.view';
const SORT_KEY = 'malas.collection.sort';

function readLocal<T extends string>(key: string, fallback: T): T {
    if (typeof window === 'undefined') return fallback;
    return (window.localStorage.getItem(key) as T) || fallback;
}

/** Compress a sorted-or-unsorted list of volume numbers into "4, 6-7, 9-12" range syntax. */
function compressRanges(numbers: number[]): string {
    if (numbers.length === 0) return '';
    const sorted = [...numbers].sort((a, b) => a - b);
    const parts: string[] = [];
    let start = sorted[0];
    let end = sorted[0];

    for (let i = 1; i <= sorted.length; i++) {
        if (i < sorted.length && sorted[i] === end + 1) {
            end = sorted[i];
        } else {
            parts.push(start === end ? `${start}` : `${start}-${end}`);
            if (i < sorted.length) {
                start = sorted[i];
                end = sorted[i];
            }
        }
    }

    return parts.join(', ');
}

export default function CollectionIndex({ collections }: Props) {
    const { t } = useTranslation('collection');
    const typeFilterOptions = useTypeFilterOptions();
    const statusOptions: { value: SeriesStatus; label: string }[] = [
        { value: 'publishing',        label: t('common:badge.status.publishing') },
        { value: 'finished',          label: t('common:badge.status.finished') },
        { value: 'on_hiatus',         label: t('common:badge.status.on_hiatus') },
        { value: 'discontinued',      label: t('common:badge.status.discontinued') },
        { value: 'not_yet_published', label: t('common:badge.status.not_yet_published') },
    ];
    const sortOptions = SORT_VALUES.map((value) => ({ value, label: t(`index.sort.${value}`) }));
    const [deleteTarget, setDeleteTarget]   = useState<CollectionRow | null>(null);
    const [deleting, setDeleting]           = useState(false);
    const [filterQuery, setFilterQuery]     = useState('');
    const [statusFilter, setStatusFilter]   = useState('');
    const [genreFilter, setGenreFilter]     = useState('');
    const [typeFilter, setTypeFilter]       = useState('all');
    const [view, setView]                   = useState<ViewMode>(() => readLocal<ViewMode>(VIEW_KEY, 'grid'));
    const [sort, setSort]                   = useState<SortValue>(() => readLocal<SortValue>(SORT_KEY, 'added_desc'));
    const [refreshing, setRefreshing]       = useState(false);
    const [busyId, setBusyId]               = useState<string | null>(null);

    function handleAdvanceRead(collectionId: string, direction: 'forward' | 'backward') {
        setBusyId(`${collectionId}:${direction}`);
        router.patch(route('collection.volumes.readProgress', collectionId), { direction }, {
            preserveScroll: true,
            onFinish: () => setBusyId(null),
        });
    }

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

    const genreGroups = useMemo(() => {
        const manga = new Set<string>();
        const novel = new Set<string>();
        collections.forEach((c) => {
            const bucket = c.type === 'novel' ? novel : manga;
            c.genres.forEach((g) => bucket.add(g));
        });
        return {
            manga: Array.from(manga).sort(),
            novel: Array.from(novel).sort(),
        };
    }, [collections]);

    const filteredCollections = collections
        .filter((c) => {
            const q = filterQuery.trim().toLowerCase();
            const matchesQuery = !q
                || c.title_romaji.toLowerCase().includes(q)
                || (c.title_english?.toLowerCase().includes(q) ?? false);
            const matchesStatus = !statusFilter || c.status === statusFilter;
            const matchesGenre = !genreFilter || c.genres.includes(genreFilter);
            const matchesType = typeFilter === 'all' || c.type === typeFilter;
            return matchesQuery && matchesStatus && matchesGenre && matchesType;
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

    // Import dialog
    const [importOpen, setImportOpen]       = useState(false);
    const [importFile, setImportFile]       = useState<File | null>(null);
    const [importing, setImporting]         = useState(false);
    const importFileRef = useRef<HTMLInputElement>(null);

    function handleImport() {
        if (!importFile) return;
        setImporting(true);
        const form = new FormData();
        form.append('collection_file', importFile);
        router.post(route('collection.import'), form, {
            forceFormData: true,
            onFinish: () => {
                setImporting(false);
                setImportOpen(false);
                setImportFile(null);
                if (importFileRef.current) importFileRef.current.value = '';
            },
        });
    }

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

    const visibleResults = searchResults.filter((s) => !inCollectionIds.has(s.id));

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

    function ReadStepper({ c }: { c: CollectionRow }) {
        const hasUnread = c.read_volumes_count < c.collection_volumes_count;
        const hasRead = c.read_volumes_count > 0;
        return (
            <div className="flex items-center gap-1" onClick={(e) => e.stopPropagation()}>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="h-5 w-5"
                    disabled={!hasRead || busyId !== null}
                    onClick={() => handleAdvanceRead(c.id, 'backward')}
                    aria-label={t('index.readProgress.decrease')}
                >
                    <Minus className="h-3 w-3" />
                </Button>
                <span className="min-w-9 text-center text-[10px] text-muted-foreground">
                    {busyId === `${c.id}:forward` || busyId === `${c.id}:backward`
                        ? <Loader2 className="mx-auto h-3 w-3 animate-spin" />
                        : t('index.readCountShort', { read: c.read_volumes_count, total: c.collection_volumes_count })}
                </span>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="h-5 w-5"
                    disabled={!hasUnread || busyId !== null}
                    onClick={() => handleAdvanceRead(c.id, 'forward')}
                    aria-label={t('index.readProgress.increase')}
                >
                    <Plus className="h-3 w-3" />
                </Button>
            </div>
        );
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

    function MissingVolumesBadge({ c }: { c: CollectionRow }) {
        const missing = c.missing_volumes;
        if (missing.length === 0) return null;
        const compressed = compressRanges(missing);
        return (
            <span onClick={(e) => e.stopPropagation()}>
                <HoverCard>
                    <HoverCardTrigger
                        render={
                            <Link href={`${route('collection.show', c.slug)}?addVolumes=${encodeURIComponent(compressed)}`} />
                        }
                    >
                        <Badge
                            variant="outline"
                            className="cursor-pointer border-amber-500/50 bg-amber-500/10 text-[10px] text-amber-700 dark:text-amber-400"
                        >
                            {t('index.missingVolumes.badge', { count: missing.length })}
                        </Badge>
                    </HoverCardTrigger>
                    <HoverCardContent className="w-auto max-w-xs">
                        <p className="text-xs font-medium text-muted-foreground">{t('index.missingVolumes.title')}</p>
                        <p className="mt-1 text-sm">{compressed}</p>
                        <p className="mt-2 text-xs text-muted-foreground">{t('index.missingVolumes.clickHint')}</p>
                    </HoverCardContent>
                </HoverCard>
            </span>
        );
    }

    return (
        <UserLayout
            header={
                <PageHeader
                    title={t('index.title')}
                    description={t('index.count', { count: collections.length })}
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <Link href={route('collection.groups.index')} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }))}>
                                <Layers className="mr-1.5 h-3.5 w-3.5" />
                                {t('groups.navLabel')}
                            </Link>
                            <a href={route('collection.export')} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }))}>
                                <Download className="mr-1.5 h-3.5 w-3.5" />
                                {t('index.export')}
                            </a>
                            <Button variant="outline" size="sm" onClick={() => setImportOpen(true)}>
                                <Upload className="mr-1.5 h-3.5 w-3.5" />
                                {t('index.import')}
                            </Button>
                            <Button size="sm" onClick={() => { setAddOpen(true); setSearchQuery(''); setSelectedIds(new Set()); }}>
                                <Plus className="mr-1.5 h-3.5 w-3.5" />
                                {t('index.addSeries')}
                            </Button>
                        </div>
                    }
                />
            }
        >
            <Head title={t('index.title')} />
            {collections.length === 0 ? (
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <Library />
                        </EmptyMedia>
                        <EmptyTitle>{t('index.empty.title')}</EmptyTitle>
                        <EmptyDescription>
                            {t('index.empty.description')}
                        </EmptyDescription>
                    </EmptyHeader>
                    <EmptyContent>
                        <Button onClick={() => setAddOpen(true)}>
                            <Plus className="mr-1.5 h-4 w-4" />
                            {t('index.addSeries')}
                        </Button>
                        <p className="text-sm text-muted-foreground">
                            {t('index.empty.notInCatalog')}{' '}
                            <Link
                                href={route('tickets.create')}
                                className="font-medium text-primary underline-offset-4 hover:underline"
                            >
                                {t('index.empty.requestViaTicket')}
                            </Link>
                        </p>
                    </EmptyContent>
                </Empty>
            ) : (
                <>
                    {/* Quick filter tipe — Segmented Control, terpisah dari filter lain */}
                    <div className="mb-3 overflow-x-auto">
                        <ToggleGroup
                            value={[typeFilter]}
                            onValueChange={(vals) => setTypeFilter(vals[0] ?? 'all')}
                            variant="outline"
                            size="sm"
                        >
                            {typeFilterOptions.map((opt) => (
                                <ToggleGroupItem key={opt.value} value={opt.value}>
                                    {opt.label}
                                </ToggleGroupItem>
                            ))}
                        </ToggleGroup>
                    </div>
                    <div className="mb-4 flex flex-wrap items-center gap-2">
                        <div className="relative max-w-sm flex-1">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                className="pl-9"
                                placeholder={t('index.searchPlaceholder')}
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
                            aria-label={t('index.refresh')}
                        >
                            <RefreshCw className={cn('h-4 w-4', refreshing && 'animate-spin')} />
                        </Button>
                        <Select value={statusFilter} onValueChange={(v) => setStatusFilter(v ?? '')}>
                            <SelectTrigger className="w-40">
                                <SelectValue placeholder={t('index.allStatus')}>
                                    {(value: string) => statusOptions.find((s) => s.value === value)?.label ?? t('index.allStatus')}
                                </SelectValue>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">{t('index.allStatus')}</SelectItem>
                                {statusOptions.map((s) => (
                                    <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select value={genreFilter} onValueChange={(v) => setGenreFilter(v ?? '')}>
                            <SelectTrigger className="w-40">
                                <SelectValue placeholder={t('index.allGenre')}>
                                    {(value: string) => value || t('index.allGenre')}
                                </SelectValue>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">{t('index.allGenre')}</SelectItem>
                                {genreGroups.manga.length > 0 && (
                                    <SelectGroup>
                                        <SelectLabel>{t('common:common.manga')}</SelectLabel>
                                        {genreGroups.manga.map((g) => (
                                            <SelectItem key={`manga-${g}`} value={g}>{g}</SelectItem>
                                        ))}
                                    </SelectGroup>
                                )}
                                {genreGroups.manga.length > 0 && genreGroups.novel.length > 0 && <SelectSeparator />}
                                {genreGroups.novel.length > 0 && (
                                    <SelectGroup>
                                        <SelectLabel>{t('common:common.lightNovel')}</SelectLabel>
                                        {genreGroups.novel.map((g) => (
                                            <SelectItem key={`novel-${g}`} value={g}>{g}</SelectItem>
                                        ))}
                                    </SelectGroup>
                                )}
                            </SelectContent>
                        </Select>
                        <Select value={sort} onValueChange={(v) => setSort((v ?? 'added_desc') as SortValue)}>
                            <SelectTrigger className="w-44">
                                <SelectValue placeholder={t('index.sort.label')}>
                                    {(value: string) => sortOptions.find((s) => s.value === value)?.label ?? t('index.sort.label')}
                                </SelectValue>
                            </SelectTrigger>
                            <SelectContent>
                                {sortOptions.map((s) => (
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
                                aria-label={t('index.gridView')}
                            >
                                <LayoutGrid className="h-3.5 w-3.5" />
                            </Button>
                            <Button
                                type="button"
                                variant={view === 'table' ? 'secondary' : 'ghost'}
                                size="icon"
                                className="h-7 w-7"
                                onClick={() => setView('table')}
                                aria-label={t('index.tableView')}
                            >
                                <List className="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>

                    {filteredCollections.length === 0 ? (
                        <p className="py-12 text-center text-sm text-muted-foreground">
                            {t('index.noneMatchFilter')}
                        </p>
                    ) : view === 'grid' ? (
                        <div className="grid gap-4 [grid-template-columns:repeat(auto-fill,minmax(160px,1fr))]">
                            {filteredCollections.map((c) => (
                                <div
                                    key={c.id}
                                    className="group flex flex-col overflow-hidden rounded-lg border bg-card transition-shadow hover:shadow-md cursor-pointer"
                                    onClick={() => router.visit(route('collection.show', c.slug))}
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
                                            <MissingVolumesBadge c={c} />
                                        </div>
                                        <GenreBadges genres={c.genres} />
                                        {c.collection_volumes_count > 0 && <ReadStepper c={c} />}

                                        <div className="mt-auto flex items-center justify-between pt-1.5">
                                            <p className="text-xs text-muted-foreground">
                                                <span className="font-medium text-foreground">{c.collection_volumes_count}</span>
                                                {c.total_volumes ? `/${c.total_volumes}` : ''} {t('index.volumeShort')}
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
                                        <TableHead>{t('index.columnTitle')}</TableHead>
                                        <TableHead className="w-36">{t('user:tickets.status')}</TableHead>
                                        <TableHead>{t('index.columnGenre')}</TableHead>
                                        <TableHead className="w-32 text-right">{t('index.columnVolume')}</TableHead>
                                        <TableHead className="w-12" />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {filteredCollections.map((c) => (
                                        <ContextMenu key={c.id}>
                                            <ContextMenuTrigger
                                                onClick={() => router.visit(route('collection.show', c.slug))}
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
                                                            render={<Link href={route('collection.show', c.slug)} className="font-medium hover:underline" />}
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
                                                <TableCell className="text-right" onClick={(e) => e.stopPropagation()}>
                                                    <p>{c.collection_volumes_count}{c.total_volumes ? `/${c.total_volumes}` : ''}</p>
                                                    {c.missing_volumes.length > 0 && (
                                                        <div className="mt-1 flex justify-end">
                                                            <MissingVolumesBadge c={c} />
                                                        </div>
                                                    )}
                                                    {c.collection_volumes_count > 0 && (
                                                        <>
                                                            <p className="text-xs text-muted-foreground">
                                                                {t('index.readCount', { read: c.read_volumes_count, total: c.collection_volumes_count })}
                                                            </p>
                                                            <div className="mt-1 flex justify-end">
                                                                <ReadStepper c={c} />
                                                            </div>
                                                        </>
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
                                                <ContextMenuItem onClick={() => router.visit(route('collection.show', c.slug))}>
                                                    <Eye className="mr-2 h-4 w-4" />
                                                    {t('index.view')}
                                                </ContextMenuItem>
                                                <ContextMenuSeparator />
                                                <ContextMenuItem variant="destructive" onClick={() => setDeleteTarget(c)}>
                                                    <Trash2 className="mr-2 h-4 w-4" />
                                                    {t('index.removeFromCollection')}
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
                        <DialogTitle>{t('index.addDialog.title')}</DialogTitle>
                        <DialogDescription>{t('index.addDialog.description')}</DialogDescription>
                    </DialogHeader>

                    <div className="relative">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            className="pl-9"
                            placeholder={t('index.addDialog.searchPlaceholder')}
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            autoFocus
                        />
                    </div>

                    <ScrollArea className="max-h-[480px]">
                        {searchLoading && (
                            <div className="flex items-center justify-center py-10 text-muted-foreground">
                                <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                                {t('index.addDialog.searching')}
                            </div>
                        )}

                        {!searchLoading && visibleResults.length === 0 && (
                            <div className="py-8 text-center">
                                <p className="text-sm text-muted-foreground">
                                    {searchQuery.trim() ? t('index.addDialog.noResults') : t('index.addDialog.typeToSearch')}
                                </p>
                                {searchQuery.trim() && (
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        {t('index.addDialog.notFound')}{' '}
                                        <Link
                                            href={route('tickets.create')}
                                            className="font-medium text-primary underline-offset-4 hover:underline"
                                            onClick={() => setAddOpen(false)}
                                        >
                                            {t('index.addDialog.requestViaTicket')}
                                        </Link>
                                    </p>
                                )}
                            </div>
                        )}

                        {!searchLoading && visibleResults.length > 0 && (
                            <div className="grid grid-cols-3 gap-2 pb-1 sm:grid-cols-5 lg:grid-cols-6">
                                {visibleResults.map((s) => {
                                    const isSelected = selectedIds.has(s.id);
                                    return (
                                        <button
                                            key={s.id}
                                            type="button"
                                            onClick={() => toggleSelect(s.id)}
                                            className={cn(
                                                'group relative flex flex-col overflow-hidden rounded-lg border text-left transition-all focus:outline-none focus:ring-2 focus:ring-ring',
                                                isSelected && 'ring-2 ring-primary border-primary',
                                                !isSelected && 'hover:ring-2 hover:ring-primary/50',
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
                            <p className="mr-auto text-sm text-muted-foreground">{t('index.addDialog.selectedCount', { count: selectedIds.size })}</p>
                        )}
                        <Button variant="outline" onClick={() => setAddOpen(false)}>{t('index.addDialog.cancel')}</Button>
                        <Button disabled={selectedIds.size === 0 || adding} onClick={handleAdd}>
                            {adding ? t('index.addDialog.adding') : (selectedIds.size > 0 ? t('index.addDialog.addWithCount', { count: selectedIds.size }) : t('index.addDialog.add'))}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete dialog */}
            <Dialog open={!!deleteTarget} onOpenChange={(open) => !open && setDeleteTarget(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('index.deleteDialog.title')}</DialogTitle>
                        <DialogDescription>
                            {t('index.deleteDialog.confirmPrefix')} <strong>{deleteTarget?.title_romaji}</strong> {t('index.deleteDialog.confirmSuffix')}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteTarget(null)}>{t('index.deleteDialog.cancel')}</Button>
                        <Button variant="destructive" disabled={deleting} onClick={handleDelete}>
                            {deleting ? t('index.deleteDialog.deleting') : t('index.deleteDialog.delete')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Import dialog */}
            <Dialog
                open={importOpen}
                onOpenChange={(open) => {
                    setImportOpen(open);
                    if (!open) { setImportFile(null); if (importFileRef.current) importFileRef.current.value = ''; }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('index.importDialog.title')}</DialogTitle>
                        <DialogDescription>{t('index.importDialog.description')}</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-1.5">
                        <Label htmlFor="import-file">{t('index.importDialog.fileLabel')}</Label>
                        <input
                            id="import-file"
                            ref={importFileRef}
                            type="file"
                            accept="application/json,.json"
                            onChange={(e) => setImportFile(e.target.files?.[0] ?? null)}
                            className="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-sm file:font-medium"
                        />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setImportOpen(false)}>{t('index.importDialog.cancel')}</Button>
                        <Button disabled={!importFile || importing} onClick={handleImport}>
                            {importing ? t('index.importDialog.importing') : t('index.importDialog.import')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </UserLayout>
    );
}
