import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import {
    BookMarked, BookOpen, LayoutGrid, List, Plus, RotateCcw, Trash2,
} from 'lucide-react';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import EmptyState from '@/Components/app/EmptyState';
import { AdultBlurOverlay } from '@/Components/app/AdultBlurOverlay';
import { SeriesStatusBadge, SeriesTypeBadge, VolumeFormatBadge } from '@/Components/app/StatusBadge';
import { Badge } from '@/Components/ui/badge';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
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
import {
    type SeriesStatus, type SeriesType, type CollectionVolumeFormat, type CollectionCondition,
} from '@/lib/types';

const VOLUME_VIEW_KEY = 'malas.collection.volumes.view';

const CONDITION_LABELS: Record<CollectionCondition, string> = {
    mint: 'Mint',
    good: 'Bagus',
    fair: 'Cukup',
    poor: 'Buruk',
};

const FORMAT_LABELS: Record<string, string> = {
    physical: 'Fisik',
    ebook: 'Ebook',
    online: 'Online',
    webtoon: 'Webtoon',
};

interface ActiveLoan {
    id: string;
    borrower_name: string;
    loaned_at: string | null;
    due_at: string | null;
    is_overdue: boolean;
}

interface VolumeRow {
    id: string;
    volume_number: number;
    format: CollectionVolumeFormat;
    active_loan: ActiveLoan | null;
}

interface CollectionData {
    id: string;
    series_id: string;
    condition: CollectionCondition;
}

interface SeriesData {
    id: string;
    title_romaji: string;
    title_english: string | null;
    status: SeriesStatus;
    type: SeriesType;
    total_volumes: number | null;
    cover_url: string | null;
    genres: string[];
    is_adult: boolean;
}

interface Props extends PageProps {
    collection: CollectionData;
    series: SeriesData;
    volumes: VolumeRow[];
}

const addVolumeSchema = z.object({
    volumes: z.string().min(1, 'Wajib diisi'),
    format:  z.enum(['physical', 'ebook', 'online', 'webtoon']),
});
type AddVolumeValues = z.infer<typeof addVolumeSchema>;

const loanSchema = z.object({
    borrower_name: z.string().min(1, 'Wajib diisi'),
    loaned_at:     z.string().min(1, 'Wajib diisi'),
    due_at:        z.string().optional(),
    notes:         z.string().optional(),
});
type LoanFormValues = z.infer<typeof loanSchema>;

function FieldError({ message }: { message?: string }) {
    if (!message) return null;
    return <p className="text-xs text-destructive">{message}</p>;
}

export default function CollectionShow({ collection, series, volumes }: Props) {
    const [addVolumeOpen, setAddVolumeOpen]   = useState(false);
    const [loanTarget, setLoanTarget]         = useState<VolumeRow | null>(null);
    const [deleteVolume, setDeleteVolume]     = useState<VolumeRow | null>(null);
    const [deleteOpen, setDeleteOpen]         = useState(false);
    const [addingVolume, setAddingVolume]     = useState(false);
    const [submittingLoan, setSubmittingLoan] = useState(false);
    const [deletingVol, setDeletingVol]       = useState(false);
    const [deleting, setDeleting]             = useState(false);
    const [returningId, setReturningId]       = useState<string | null>(null);
    const [selectedIds, setSelectedIds]       = useState<Set<string>>(new Set());
    const [bulkDeleteOpen, setBulkDeleteOpen] = useState(false);
    const [bulkDeleting, setBulkDeleting]     = useState(false);
    const [volumeView, setVolumeView]         = useState<'grid' | 'table'>(() => {
        if (typeof window === 'undefined') return 'grid';
        return (window.localStorage.getItem(VOLUME_VIEW_KEY) as 'grid' | 'table') || 'grid';
    });
    const [updatingCondition, setUpdatingCondition] = useState(false);

    const today = new Date().toISOString().split('T')[0];

    function handleVolumeViewChange(mode: 'grid' | 'table') {
        setVolumeView(mode);
        window.localStorage.setItem(VOLUME_VIEW_KEY, mode);
    }

    function handleConditionChange(condition: string | null) {
        if (!condition) return;
        setUpdatingCondition(true);
        router.patch(route('collection.condition.update', collection.id), { condition }, {
            preserveScroll: true,
            onFinish: () => setUpdatingCondition(false),
        });
    }

    // Add volume form
    const {
        register: avReg, control: avCtrl, handleSubmit: avSubmit,
        setError: avSetError, reset: avReset,
        formState: { errors: avErrors },
    } = useForm<AddVolumeValues>({
        resolver: zodResolver(addVolumeSchema),
        defaultValues: { format: 'physical' },
    });

    // Loan form
    const {
        register, handleSubmit, reset, setError,
        formState: { errors },
    } = useForm<LoanFormValues>({
        resolver: zodResolver(loanSchema),
        defaultValues: { loaned_at: today },
    });

    function onAddVolume(values: AddVolumeValues) {
        setAddingVolume(true);
        router.post(route('collection.volumes.store', collection.id), values, {
            onSuccess: () => { avReset({ format: 'physical' }); setAddVolumeOpen(false); },
            onError: (errs) => {
                Object.entries(errs).forEach(([k, msg]) => {
                    avSetError(k as keyof AddVolumeValues, { message: msg });
                });
            },
            onFinish: () => setAddingVolume(false),
        });
    }

    function handleDeleteVolume() {
        if (!deleteVolume) return;
        setDeletingVol(true);
        router.delete(route('collection.volumes.destroy', { collection: collection.id, collectionVolume: deleteVolume.id }), {
            onFinish: () => { setDeletingVol(false); setDeleteVolume(null); },
        });
    }

    function onLoanSubmit(values: LoanFormValues) {
        if (!loanTarget) return;
        setSubmittingLoan(true);
        router.post(
            route('loans.store', collection.id),
            { ...values, collection_volume_id: loanTarget.id },
            {
                onSuccess: () => { reset({ loaned_at: today }); setLoanTarget(null); },
                onError: (errs) => {
                    Object.entries(errs).forEach(([k, msg]) => {
                        setError(k as keyof LoanFormValues, { message: msg });
                    });
                },
                onFinish: () => setSubmittingLoan(false),
            },
        );
    }

    function markReturned(loanId: string) {
        setReturningId(loanId);
        router.put(route('loans.return', loanId), {}, {
            preserveScroll: true,
            onFinish: () => setReturningId(null),
        });
    }

    function handleDelete() {
        setDeleting(true);
        router.delete(route('collection.destroy', collection.id), {
            onFinish: () => { setDeleting(false); setDeleteOpen(false); },
        });
    }

    function toggleVolumeSelect(id: string) {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            next.has(id) ? next.delete(id) : next.add(id);
            return next;
        });
    }

    function handleBulkDelete() {
        setBulkDeleting(true);
        router.delete(route('collection.volumes.destroyBulk', collection.id), {
            data: { volume_ids: Array.from(selectedIds) },
            onSuccess: () => setSelectedIds(new Set()),
            onFinish: () => { setBulkDeleting(false); setBulkDeleteOpen(false); },
        });
    }

    const ownedCount = volumes.length;

    return (
        <UserLayout
            header={
                <PageHeader
                    title={series.title_romaji}
                    breadcrumbs={[
                        { label: 'Koleksiku', href: route('collection.index') },
                        { label: series.title_romaji },
                    ]}
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <Link
                                href={route('catalog.show', series.id)}
                                className={cn(buttonVariants({ variant: 'outline', size: 'sm' }))}
                            >
                                Lihat Katalog
                            </Link>
                            <Button variant="destructive" size="sm" onClick={() => setDeleteOpen(true)}>
                                <Trash2 className="mr-1.5 h-3.5 w-3.5" />
                                Hapus Koleksi
                            </Button>
                        </div>
                    }
                />
            }
        >
            <Head title={series.title_romaji} />
            {/* Series info & progress */}
            <div className="grid gap-6 lg:grid-cols-[auto_1fr]">
                <div className="shrink-0">
                    <AdultBlurOverlay isAdult={series.is_adult} className="h-40 w-28 overflow-hidden rounded-lg bg-muted shadow-sm">
                        {series.cover_url ? (
                            <img src={series.cover_url} alt={series.title_romaji} className="h-full w-full object-cover" />
                        ) : (
                            <div className="flex h-full w-full items-center justify-center">
                                <BookOpen className="h-8 w-8 text-muted-foreground" />
                            </div>
                        )}
                    </AdultBlurOverlay>
                </div>

                <div className="space-y-3">
                    {series.title_english && <p className="text-muted-foreground">{series.title_english}</p>}
                    <div className="flex flex-wrap gap-2">
                        <SeriesStatusBadge status={series.status} />
                        <SeriesTypeBadge type={series.type} />
                    </div>
                    {series.genres.length > 0 && (
                        <div className="flex flex-wrap gap-1.5">
                            {series.genres.map((g) => (
                                <Badge key={g} variant="outline">{g}</Badge>
                            ))}
                        </div>
                    )}
                    <div className="text-sm">
                        <p className="text-xs text-muted-foreground">Progress</p>
                        <p className="font-medium">
                            {ownedCount}{series.total_volumes ? `/${series.total_volumes}` : ''} volume dimiliki
                        </p>
                        {series.total_volumes && series.total_volumes > 0 && (
                            <div className="mt-1.5 h-2 w-48 overflow-hidden rounded-full bg-muted">
                                <div
                                    className="h-full rounded-full bg-primary transition-all [width:var(--w)]"
                                    style={{ '--w': `${Math.min(100, (ownedCount / series.total_volumes!) * 100)}%` } as React.CSSProperties}
                                />
                            </div>
                        )}
                    </div>
                    <div className="flex items-center gap-2 text-sm">
                        <span className="text-xs text-muted-foreground">Kondisi (opsional)</span>
                        <Select
                            value={collection.condition}
                            onValueChange={handleConditionChange}
                            disabled={updatingCondition}
                        >
                            <SelectTrigger className="h-7 w-28 text-xs">
                                <SelectValue>
                                    {(value: CollectionCondition) => CONDITION_LABELS[value]}
                                </SelectValue>
                            </SelectTrigger>
                            <SelectContent>
                                {(Object.keys(CONDITION_LABELS) as CollectionCondition[]).map((c) => (
                                    <SelectItem key={c} value={c}>{CONDITION_LABELS[c]}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>

            {/* Volume list */}
            <div className="mt-8">
                <div className="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-base font-semibold">Volume yang Dimiliki ({ownedCount})</h2>
                    <div className="flex flex-wrap items-center gap-2">
                        {selectedIds.size > 0 && (
                            <Button size="sm" variant="destructive" onClick={() => setBulkDeleteOpen(true)}>
                                <Trash2 className="mr-1.5 h-3.5 w-3.5" />
                                Hapus ({selectedIds.size})
                            </Button>
                        )}
                        <div className="flex items-center gap-1 rounded-md border p-0.5">
                            <Button
                                type="button"
                                variant={volumeView === 'grid' ? 'secondary' : 'ghost'}
                                size="icon"
                                className="h-7 w-7"
                                onClick={() => handleVolumeViewChange('grid')}
                                aria-label="Tampilan grid"
                            >
                                <LayoutGrid className="h-3.5 w-3.5" />
                            </Button>
                            <Button
                                type="button"
                                variant={volumeView === 'table' ? 'secondary' : 'ghost'}
                                size="icon"
                                className="h-7 w-7"
                                onClick={() => handleVolumeViewChange('table')}
                                aria-label="Tampilan tabel"
                            >
                                <List className="h-3.5 w-3.5" />
                            </Button>
                        </div>
                        <Button size="sm" onClick={() => setAddVolumeOpen(true)}>
                            <Plus className="mr-1.5 h-3.5 w-3.5" />
                            Tambah Volume
                        </Button>
                    </div>
                </div>

                {volumes.length === 0 ? (
                    <EmptyState
                        title="Belum ada volume"
                        description="Tambahkan volume yang kamu miliki, misalnya: 1,2,3,5"
                        icon={BookOpen}
                        action={
                            <Button size="sm" onClick={() => setAddVolumeOpen(true)}>
                                <Plus className="mr-1.5 h-4 w-4" />
                                Tambah Volume
                            </Button>
                        }
                    />
                ) : volumeView === 'grid' ? (
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                        {volumes.map((v) => (
                            <div
                                key={v.id}
                                className={cn(
                                    'flex flex-col overflow-hidden rounded-lg border bg-card',
                                    selectedIds.has(v.id) && 'ring-2 ring-primary',
                                )}
                            >
                                {/* Cover placeholder + number — klik di mana saja untuk pilih */}
                                <div
                                    className="relative flex aspect-[2/3] cursor-pointer items-center justify-center bg-muted"
                                    onClick={() => toggleVolumeSelect(v.id)}
                                >
                                    <Checkbox
                                        checked={selectedIds.has(v.id)}
                                        className="pointer-events-none absolute left-1.5 top-1.5 bg-background"
                                        aria-label={`Pilih volume ${v.volume_number}`}
                                    />
                                    <div className="text-center">
                                        <p className="text-2xl font-bold text-muted-foreground/40">{v.volume_number}</p>
                                        <p className="text-xs text-muted-foreground/40">Vol.</p>
                                    </div>
                                </div>

                                <div className="flex flex-col gap-1.5 p-2">
                                    <div className="flex items-center justify-between">
                                        <p className="text-xs font-medium">Vol. {v.volume_number}</p>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            className="h-5 w-5 text-destructive/60 hover:text-destructive"
                                            onClick={() => setDeleteVolume(v)}
                                        >
                                            <Trash2 className="h-3 w-3" />
                                        </Button>
                                    </div>

                                    <VolumeFormatBadge format={v.format} />

                                    {v.active_loan ? (
                                        <div className="space-y-1">
                                            <Badge
                                                variant="outline"
                                                className={cn(
                                                    'text-xs w-full justify-center',
                                                    v.active_loan.is_overdue
                                                        ? 'border-destructive text-destructive'
                                                        : 'border-yellow-500 text-yellow-600 dark:text-yellow-400',
                                                )}
                                            >
                                                {v.active_loan.is_overdue ? 'Terlambat' : 'Dipinjam'}
                                            </Badge>
                                            <p className="truncate text-xs text-muted-foreground">{v.active_loan.borrower_name}</p>
                                            <button
                                                type="button"
                                                className="flex items-center gap-1 text-xs text-primary hover:underline disabled:opacity-50"
                                                disabled={returningId === v.active_loan.id}
                                                onClick={() => markReturned(v.active_loan!.id)}
                                            >
                                                <RotateCcw className="h-3 w-3" />
                                                {returningId === v.active_loan.id ? 'Menyimpan...' : 'Tandai kembali'}
                                            </button>
                                        </div>
                                    ) : (
                                        <button
                                            type="button"
                                            className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                                            onClick={() => setLoanTarget(v)}
                                        >
                                            <BookMarked className="h-3 w-3" />
                                            Pinjamkan
                                        </button>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="max-h-[70vh] overflow-auto rounded-lg border">
                        <Table>
                            <TableHeader className="sticky top-0 z-10 bg-card">
                                <TableRow>
                                    <TableHead className="w-10" />
                                    <TableHead className="w-20">Volume</TableHead>
                                    <TableHead>Format</TableHead>
                                    <TableHead>Status Pinjaman</TableHead>
                                    <TableHead className="w-12" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {volumes.map((v) => (
                                    <TableRow
                                        key={v.id}
                                        className={cn('cursor-pointer', selectedIds.has(v.id) && 'bg-muted/50')}
                                        onClick={() => toggleVolumeSelect(v.id)}
                                    >
                                        <TableCell onClick={(e) => e.stopPropagation()}>
                                            <Checkbox
                                                checked={selectedIds.has(v.id)}
                                                onCheckedChange={() => toggleVolumeSelect(v.id)}
                                                aria-label={`Pilih volume ${v.volume_number}`}
                                            />
                                        </TableCell>
                                        <TableCell className="font-medium">Vol. {v.volume_number}</TableCell>
                                        <TableCell><VolumeFormatBadge format={v.format} /></TableCell>
                                        <TableCell onClick={(e) => e.stopPropagation()}>
                                            {v.active_loan ? (
                                                <div className="flex items-center gap-2">
                                                    <Badge
                                                        variant="outline"
                                                        className={cn(
                                                            'text-xs',
                                                            v.active_loan.is_overdue
                                                                ? 'border-destructive text-destructive'
                                                                : 'border-yellow-500 text-yellow-600 dark:text-yellow-400',
                                                        )}
                                                    >
                                                        {v.active_loan.is_overdue ? 'Terlambat' : 'Dipinjam'}
                                                    </Badge>
                                                    <span className="text-xs text-muted-foreground">{v.active_loan.borrower_name}</span>
                                                    <button
                                                        type="button"
                                                        className="flex items-center gap-1 text-xs text-primary hover:underline disabled:opacity-50"
                                                        disabled={returningId === v.active_loan.id}
                                                        onClick={() => markReturned(v.active_loan!.id)}
                                                    >
                                                        <RotateCcw className="h-3 w-3" />
                                                        Kembali
                                                    </button>
                                                </div>
                                            ) : (
                                                <button
                                                    type="button"
                                                    className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                                                    onClick={() => setLoanTarget(v)}
                                                >
                                                    <BookMarked className="h-3 w-3" />
                                                    Pinjamkan
                                                </button>
                                            )}
                                        </TableCell>
                                        <TableCell onClick={(e) => e.stopPropagation()}>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="h-7 w-7 text-destructive/60 hover:text-destructive"
                                                onClick={() => setDeleteVolume(v)}
                                            >
                                                <Trash2 className="h-3.5 w-3.5" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>

            {/* Add Volume Dialog */}
            <Dialog open={addVolumeOpen} onOpenChange={(open) => { setAddVolumeOpen(open); if (!open) avReset({ format: 'physical' }); }}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tambah Volume</DialogTitle>
                        <DialogDescription>
                            Pisahkan dengan koma. Gunakan tanda hubung untuk range. Contoh: <strong>1,2,3,5-9,11,12,15-18</strong>
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={avSubmit(onAddVolume)} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="volumes">Nomor Volume <span className="text-destructive">*</span></Label>
                            <Input
                                id="volumes"
                                placeholder="1,2,3,5-9,11,12"
                                {...avReg('volumes')}
                            />
                            <FieldError message={avErrors.volumes?.message} />
                        </div>
                        <div className="space-y-1.5">
                            <Label>Format <span className="text-destructive">*</span></Label>
                            <Controller<AddVolumeValues, 'format'>
                                control={avCtrl}
                                name="format"
                                render={({ field }) => (
                                    <Select value={field.value} onValueChange={field.onChange}>
                                        <SelectTrigger>
                                            <SelectValue>
                                                {(value: string) => FORMAT_LABELS[value] ?? value}
                                            </SelectValue>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="physical">Fisik</SelectItem>
                                            <SelectItem value="ebook">Ebook</SelectItem>
                                            <SelectItem value="online">Online</SelectItem>
                                            <SelectItem value="webtoon">Webtoon</SelectItem>
                                        </SelectContent>
                                    </Select>
                                )}
                            />
                            <FieldError message={avErrors.format?.message} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setAddVolumeOpen(false)}>Batal</Button>
                            <Button type="submit" disabled={addingVolume}>
                                {addingVolume ? 'Menyimpan...' : 'Tambah'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Volume Dialog */}
            <Dialog open={!!deleteVolume} onOpenChange={(open) => !open && setDeleteVolume(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus Volume #{deleteVolume?.volume_number}</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Yakin ingin menghapus volume ini dari koleksimu? Riwayat pinjaman terkait juga akan hilang.
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteVolume(null)}>Batal</Button>
                        <Button variant="destructive" disabled={deletingVol} onClick={handleDeleteVolume}>
                            {deletingVol ? 'Menghapus...' : 'Hapus'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Bulk Delete Volumes Dialog */}
            <Dialog open={bulkDeleteOpen} onOpenChange={setBulkDeleteOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus {selectedIds.size} Volume</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Yakin ingin menghapus {selectedIds.size} volume terpilih dari koleksimu? Riwayat pinjaman terkait juga akan hilang.
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setBulkDeleteOpen(false)}>Batal</Button>
                        <Button variant="destructive" disabled={bulkDeleting} onClick={handleBulkDelete}>
                            {bulkDeleting ? 'Menghapus...' : 'Hapus'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Loan Dialog */}
            <Dialog open={!!loanTarget} onOpenChange={(open) => { if (!open) { setLoanTarget(null); reset({ loaned_at: today }); } }}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Pinjamkan Vol. #{loanTarget?.volume_number}</DialogTitle>
                        <DialogDescription>Catat siapa yang meminjam volume ini.</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleSubmit(onLoanSubmit)} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="borrower_name">Nama Peminjam <span className="text-destructive">*</span></Label>
                            <Input id="borrower_name" {...register('borrower_name')} />
                            <FieldError message={errors.borrower_name?.message} />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="loaned_at">Tanggal Pinjam <span className="text-destructive">*</span></Label>
                                <Input id="loaned_at" type="date" {...register('loaned_at')} />
                                <FieldError message={errors.loaned_at?.message} />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="due_at">Jatuh Tempo</Label>
                                <Input id="due_at" type="date" {...register('due_at')} />
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="notes">Catatan</Label>
                            <Textarea id="notes" rows={2} className="resize-none" {...register('notes')} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setLoanTarget(null)}>Batal</Button>
                            <Button type="submit" disabled={submittingLoan}>
                                {submittingLoan ? 'Menyimpan...' : 'Catat Pinjaman'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Collection Dialog */}
            <Dialog open={deleteOpen} onOpenChange={setDeleteOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus dari Koleksi</DialogTitle>
                        <DialogDescription>
                            Yakin ingin menghapus <strong>{series.title_romaji}</strong> dari koleksimu?
                            Semua data volume dan pinjaman aktif akan hilang.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteOpen(false)}>Batal</Button>
                        <Button variant="destructive" disabled={deleting} onClick={handleDelete}>
                            {deleting ? 'Menghapus...' : 'Hapus'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </UserLayout>
    );
}
