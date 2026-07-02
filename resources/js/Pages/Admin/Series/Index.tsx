import { useEffect, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Plus, MoreHorizontal, Pencil, Trash2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Pagination } from '@/Components/app/Pagination';
import { SeriesStatusBadge, SeriesTypeBadge } from '@/Components/app/StatusBadge';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger,
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
    const [search, setSearch]             = useState(filters.search ?? '');
    const [deleteTarget, setDeleteTarget] = useState<SeriesRow | null>(null);
    const [deleting, setDeleting]         = useState(false);

    useEffect(() => {
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

    function handleDelete() {
        if (!deleteTarget) return;
        setDeleting(true);
        router.delete(route('admin.series.destroy', deleteTarget.id), {
            onFinish: () => { setDeleting(false); setDeleteTarget(null); },
        });
    }

    return (
        <AdminLayout
            header={
                <PageHeader
                    title="Series"
                    description={`${series.total} series terdaftar`}
                    actions={
                        <Link
                            href={route('admin.series.create')}
                            className={buttonVariants()}
                        >
                            <Plus className="h-4 w-4 mr-1.5" />
                            Tambah Series
                        </Link>
                    }
                />
            }
        >
            {/* Filters */}
            <div className="mb-4 flex flex-wrap gap-2">
                <Input
                    placeholder="Cari judul..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="w-56"
                />
                <Select
                    value={filters.status ?? ''}
                    onValueChange={(v) => handleFilter('status', v ?? '')}
                >
                    <SelectTrigger className="w-40">
                        <SelectValue placeholder="Semua status" />
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
                        <SelectValue placeholder="Semua tipe" />
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
                                <TableCell colSpan={7} className="py-12 text-center text-muted-foreground">
                                    Tidak ada series ditemukan.
                                </TableCell>
                            </TableRow>
                        ) : series.data.map((s) => (
                            <TableRow
                                key={s.id}
                                className="cursor-pointer"
                                onClick={() => router.visit(route('admin.series.show', s.id))}
                            >
                                <TableCell>
                                    {s.cover_url ? (
                                        <img src={s.cover_url} alt={s.title_romaji} className="h-10 w-7 rounded object-cover" />
                                    ) : (
                                        <div className="h-10 w-7 rounded bg-muted" />
                                    )}
                                </TableCell>
                                <TableCell>
                                    <p className="font-medium">{s.title_romaji}</p>
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
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <div className="mt-4">
                <Pagination data={series} />
            </div>

            {/* Delete confirmation */}
            <Dialog open={!!deleteTarget} onOpenChange={(open) => !open && setDeleteTarget(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus Series</DialogTitle>
                        <DialogDescription>
                            Yakin ingin menghapus <strong>{deleteTarget?.title_romaji}</strong>?
                            Aksi ini tidak dapat dibatalkan.
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
        </AdminLayout>
    );
}
