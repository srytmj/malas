import { useEffect, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Plus, MoreHorizontal, Pencil, RefreshCw, Trash2, Eye } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Pagination } from '@/Components/app/Pagination';
import { SeriesStatusBadge, SeriesTypeBadge } from '@/Components/app/StatusBadge';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { HoverCard, HoverCardContent, HoverCardTrigger } from '@/Components/ui/hover-card';
import {
    ContextMenu, ContextMenuContent, ContextMenuItem, ContextMenuSeparator, ContextMenuTrigger,
} from '@/Components/ui/context-menu';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import { type PaginatedData, type SeriesStatus, type SeriesType } from '@/lib/types';

interface SeriesRow {
    id: string;
    title_romaji: string;
    title_english: string | null;
    cover_url: string | null;
    status: SeriesStatus;
    type: SeriesType;
    total_volumes: number | null;
    volumes_count: number;
    score: number | null;
}

interface Props extends PageProps {
    series: PaginatedData<SeriesRow>;
    filters: { search?: string | null; status?: string | null; type?: string | null };
}

const STATUSES = [
    { value: 'publishing',        label: 'Publishing' },
    { value: 'finished',          label: 'Selesai' },
    { value: 'on_hiatus',         label: 'Hiatus' },
    { value: 'discontinued',      label: 'Discontinued' },
    { value: 'not_yet_published', label: 'Belum Terbit' },
];

const TYPES = [
    { value: 'manga',     label: 'Manga' },
    { value: 'manhwa',    label: 'Manhwa' },
    { value: 'manhua',    label: 'Manhua' },
    { value: 'novel',     label: 'Novel' },
    { value: 'one_shot',  label: 'One Shot' },
    { value: 'doujinshi', label: 'Doujinshi' },
];

export default function SeriesIndex({ series, filters }: Props) {
    const [search, setSearch]               = useState(filters.search ?? '');
    const [deleteTarget, setDeleteTarget]   = useState<SeriesRow | null>(null);
    const [deleting, setDeleting]           = useState(false);
    const [selectedIds, setSelectedIds]     = useState<Set<string>>(new Set());
    const [bulkDeleteOpen, setBulkDeleteOpen] = useState(false);
    const [bulkDeleting, setBulkDeleting]   = useState(false);
    const [refreshing, setRefreshing]       = useState(false);
    const isFirstRender = useRef(true);

    useEffect(() => {
        // Reset pilihan saat halaman/filter berubah
        setSelectedIds(new Set());
    }, [series.current_page]);

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }
        const t = setTimeout(() => {
            router.get(
                route('admin.series.index'),
                { ...filters, search: search || undefined },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 400);
        return () => clearTimeout(t);
    }, [search]);

    function handleFilter(key: string, value: string) {
        router.get(
            route('admin.series.index'),
            { ...filters, search, [key]: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function handleRefresh() {
        setRefreshing(true);
        router.reload({ onFinish: () => setRefreshing(false) });
    }

    function handleDelete() {
        if (!deleteTarget) return;
        setDeleting(true);
        router.delete(route('admin.series.destroy', deleteTarget.id), {
            onFinish: () => { setDeleting(false); setDeleteTarget(null); },
        });
    }

    function toggleRow(id: string) {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            next.has(id) ? next.delete(id) : next.add(id);
            return next;
        });
    }

    function toggleAll() {
        const pageIds = series.data.map((s) => s.id);
        const allSelected = pageIds.every((id) => selectedIds.has(id));
        if (allSelected) {
            setSelectedIds((prev) => {
                const next = new Set(prev);
                pageIds.forEach((id) => next.delete(id));
                return next;
            });
        } else {
            setSelectedIds((prev) => new Set([...prev, ...pageIds]));
        }
    }

    function handleBulkDelete() {
        setBulkDeleting(true);
        router.delete(route('admin.series.bulk-destroy'), {
            data: { ids: Array.from(selectedIds) },
            onSuccess: () => setSelectedIds(new Set()),
            onFinish: () => { setBulkDeleting(false); setBulkDeleteOpen(false); },
        });
    }

    const pageIds      = series.data.map((s) => s.id);
    const allSelected  = pageIds.length > 0 && pageIds.every((id) => selectedIds.has(id));
    const someSelected = pageIds.some((id) => selectedIds.has(id)) && !allSelected;

    return (
        <AdminLayout
            header={
                <PageHeader
                    title="Series"
                    description={`${series.total} series terdaftar`}
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            <Link
                                href={route('admin.series.create')}
                                className={buttonVariants()}
                            >
                                <Plus className="h-4 w-4 mr-1.5" />
                                Tambah Series
                            </Link>
                            {selectedIds.size > 0 && (
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    className="ml-1 border-l pl-3"
                                    onClick={() => setBulkDeleteOpen(true)}
                                >
                                    <Trash2 className="mr-1.5 h-3.5 w-3.5" />
                                    Hapus ({selectedIds.size})
                                </Button>
                            )}
                        </div>
                    }
                />
            }
        >
            <Head title="Series" />
            {/* Filters */}
            <div className="mb-4 flex flex-wrap items-center gap-2">
                <Input
                    placeholder="Cari judul..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="w-56"
                />
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
                <Select
                    value={filters.status ?? ''}
                    onValueChange={(v) => handleFilter('status', v ?? '')}
                >
                    <SelectTrigger className="w-40">
                        <SelectValue placeholder="Semua status">
                            {(value: string) => STATUSES.find((s) => s.value === value)?.label ?? 'Semua status'}
                        </SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">Semua status</SelectItem>
                        {STATUSES.map((s) => (
                            <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <Select
                    value={filters.type ?? ''}
                    onValueChange={(v) => handleFilter('type', v ?? '')}
                >
                    <SelectTrigger className="w-40">
                        <SelectValue placeholder="Semua tipe">
                            {(value: string) => TYPES.find((t) => t.value === value)?.label ?? 'Semua tipe'}
                        </SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">Semua tipe</SelectItem>
                        {TYPES.map((t) => (
                            <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            {/* Table */}
            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-10">
                                <Checkbox
                                    checked={allSelected}
                                    indeterminate={someSelected}
                                    onCheckedChange={toggleAll}
                                    aria-label="Pilih semua"
                                />
                            </TableHead>
                            <TableHead className="w-12" />
                            <TableHead>Judul</TableHead>
                            <TableHead className="w-28">Tipe</TableHead>
                            <TableHead className="w-36">Status</TableHead>
                            <TableHead className="w-20 text-right">Volume</TableHead>
                            <TableHead className="w-20 text-right">Skor</TableHead>
                            <TableHead className="w-12" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {series.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={8} className="py-12 text-center text-muted-foreground">
                                    Tidak ada series ditemukan.
                                </TableCell>
                            </TableRow>
                        ) : series.data.map((s) => (
                            <ContextMenu key={s.id}>
                                <ContextMenuTrigger
                                    onClick={() => router.visit(route('admin.series.show', s.id))}
                                    render={<TableRow className={cn('cursor-pointer', selectedIds.has(s.id) && 'bg-muted/50')} />}
                                >
                                    <TableCell onClick={(e) => e.stopPropagation()}>
                                        <Checkbox
                                            checked={selectedIds.has(s.id)}
                                            onCheckedChange={() => toggleRow(s.id)}
                                            aria-label={`Pilih ${s.title_romaji}`}
                                        />
                                    </TableCell>
                                    <TableCell>
                                        {s.cover_url ? (
                                            <img src={s.cover_url} alt={s.title_romaji} className="h-10 w-7 rounded object-cover" />
                                        ) : (
                                            <div className="h-10 w-7 rounded bg-muted" />
                                        )}
                                    </TableCell>
                                    <TableCell onClick={(e) => e.stopPropagation()}>
                                        <HoverCard>
                                            <HoverCardTrigger
                                                render={<Link href={route('admin.series.show', s.id)} className="font-medium hover:underline" />}
                                            >
                                                {s.title_romaji}
                                            </HoverCardTrigger>
                                            <HoverCardContent>
                                                <div className="flex gap-3">
                                                    <div className="h-24 w-16 shrink-0 overflow-hidden rounded bg-muted">
                                                        {s.cover_url ? (
                                                            <img src={s.cover_url} alt={s.title_romaji} className="h-full w-full object-cover" />
                                                        ) : null}
                                                    </div>
                                                    <div className="min-w-0 space-y-1.5">
                                                        <p className="font-medium leading-tight">{s.title_romaji}</p>
                                                        <div className="flex flex-wrap gap-1">
                                                            <SeriesTypeBadge type={s.type} />
                                                            <SeriesStatusBadge status={s.status} />
                                                        </div>
                                                        <p className="text-xs text-muted-foreground">
                                                            {s.volumes_count}{s.total_volumes ? `/${s.total_volumes}` : ''} volume
                                                            {s.score !== null ? ` · Skor ${Number(s.score).toFixed(1)}` : ''}
                                                        </p>
                                                    </div>
                                                </div>
                                            </HoverCardContent>
                                        </HoverCard>
                                        {s.title_english && (
                                            <p className="text-xs text-muted-foreground">{s.title_english}</p>
                                        )}
                                    </TableCell>
                                    <TableCell><SeriesTypeBadge type={s.type} /></TableCell>
                                    <TableCell><SeriesStatusBadge status={s.status} /></TableCell>
                                    <TableCell className="text-right">
                                        {s.volumes_count}{s.total_volumes ? `/${s.total_volumes}` : ''}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {s.score !== null ? Number(s.score).toFixed(1) : '—'}
                                    </TableCell>
                                    <TableCell onClick={(e) => e.stopPropagation()}>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger
                                                className={cn(buttonVariants({ variant: 'ghost', size: 'icon' }), 'h-8 w-8')}
                                            >
                                                <MoreHorizontal className="h-4 w-4" />
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem
                                                    onClick={() => router.visit(route('admin.series.edit', s.id))}
                                                >
                                                    <Pencil className="mr-2 h-4 w-4" />
                                                    Edit
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    variant="destructive"
                                                    onClick={() => setDeleteTarget(s)}
                                                >
                                                    <Trash2 className="mr-2 h-4 w-4" />
                                                    Hapus
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </ContextMenuTrigger>
                                <ContextMenuContent>
                                    <ContextMenuItem onClick={() => router.visit(route('admin.series.show', s.id))}>
                                        <Eye className="mr-2 h-4 w-4" />
                                        Lihat
                                    </ContextMenuItem>
                                    <ContextMenuItem onClick={() => router.visit(route('admin.series.edit', s.id))}>
                                        <Pencil className="mr-2 h-4 w-4" />
                                        Edit
                                    </ContextMenuItem>
                                    <ContextMenuSeparator />
                                    <ContextMenuItem variant="destructive" onClick={() => setDeleteTarget(s)}>
                                        <Trash2 className="mr-2 h-4 w-4" />
                                        Hapus
                                    </ContextMenuItem>
                                </ContextMenuContent>
                            </ContextMenu>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <div className="mt-4">
                <Pagination
                    data={series}
                    routeName="admin.series.index"
                    filters={{ search, status: filters.status, type: filters.type }}
                />
            </div>

            {/* Single delete confirmation */}
            <Dialog open={!!deleteTarget} onOpenChange={(open) => !open && setDeleteTarget(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus Series</DialogTitle>
                        <DialogDescription>
                            Yakin ingin menghapus <strong>{deleteTarget?.title_romaji}</strong>?
                            Semua volume, koleksi, dan pinjaman terkait juga akan terhapus.
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

            {/* Bulk delete confirmation */}
            <Dialog open={bulkDeleteOpen} onOpenChange={setBulkDeleteOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus {selectedIds.size} Series</DialogTitle>
                        <DialogDescription>
                            Yakin ingin menghapus <strong>{selectedIds.size} series</strong> yang dipilih?
                            Semua volume, koleksi user, dan pinjaman terkait juga akan ikut terhapus.
                            Tindakan ini tidak dapat dibatalkan.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setBulkDeleteOpen(false)}>Batal</Button>
                        <Button variant="destructive" disabled={bulkDeleting} onClick={handleBulkDelete}>
                            {bulkDeleting ? 'Menghapus...' : `Hapus ${selectedIds.size} Series`}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
