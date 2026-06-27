import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { BookOpen, Trash2, BookMarked, RotateCcw, AlertCircle } from 'lucide-react';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import { SeriesStatusBadge, SeriesTypeBadge } from '@/Components/app/StatusBadge';
import { Badge } from '@/Components/ui/badge';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import { type SeriesStatus, type SeriesType, type VolumeType } from '@/lib/types';

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
    type: VolumeType;
    isbn: string | null;
    published_at: string | null;
    cover_url: string | null;
    is_owned: boolean;
    active_loan: ActiveLoan | null;
}

interface CollectionData {
    id: string;
    series_id: string;
    acquired_at: string | null;
    notes: string | null;
}

interface SeriesData {
    id: string;
    title_romaji: string;
    title_english: string | null;
    status: SeriesStatus;
    type: SeriesType;
    total_volumes: number | null;
    cover_url: string | null;
}

interface Props extends PageProps {
    collection: CollectionData;
    series: SeriesData;
    volumes: VolumeRow[];
    owned_count: number;
}

const loanSchema = z.object({
    borrower_name: z.string().min(1, 'Wajib diisi'),
    loaned_at:     z.string().min(1, 'Wajib diisi'),
    due_at:        z.string().optional(),
    notes:         z.string().optional(),
});
type LoanFormValues = z.infer<typeof loanSchema>;

function FieldError({ message }: { message?: string }) {
    if (!message) return null;
    return <p className="text-sm text-destructive">{message}</p>;
}

function LoanStatusBadge({ loan }: { loan: ActiveLoan }) {
    if (loan.is_overdue) {
        return <Badge variant="destructive" className="text-xs">Terlambat</Badge>;
    }
    return <Badge variant="outline" className="text-xs border-yellow-500 text-yellow-600 dark:text-yellow-400">Dipinjam</Badge>;
}

export default function CollectionShow({ collection, series, volumes, owned_count }: Props) {
    const [loanTarget, setLoanTarget]   = useState<VolumeRow | null>(null);
    const [deleteOpen, setDeleteOpen]   = useState(false);
    const [deleting, setDeleting]       = useState(false);
    const [submitting, setSubmitting]   = useState(false);
    const [returningId, setReturningId] = useState<string | null>(null);

    // Volume toggle state (optimistic)
    const [ownedState, setOwnedState] = useState<Record<string, boolean>>(
        Object.fromEntries(volumes.map((v) => [v.id, v.is_owned])),
    );
    const [togglingId, setTogglingId] = useState<string | null>(null);

    const today = new Date().toISOString().split('T')[0];

    const {
        register,
        handleSubmit,
        reset,
        setError,
        formState: { errors },
    } = useForm<LoanFormValues>({
        resolver: zodResolver(loanSchema),
        defaultValues: { loaned_at: today },
    });

    function toggleOwned(volume: VolumeRow) {
        if (togglingId) return;
        setTogglingId(volume.id);
        setOwnedState((prev) => ({ ...prev, [volume.id]: !prev[volume.id] }));
        router.put(
            route('collection.volumes.toggle', { collection: collection.id, volume: volume.id }),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onError: () => setOwnedState((prev) => ({ ...prev, [volume.id]: !prev[volume.id] })),
                onFinish: () => setTogglingId(null),
            },
        );
    }

    function onLoanSubmit(values: LoanFormValues) {
        if (!loanTarget) return;
        setSubmitting(true);
        router.post(
            route('loans.store', collection.id),
            { ...values, volume_id: loanTarget.id },
            {
                onSuccess: () => { reset({ loaned_at: today }); setLoanTarget(null); },
                onError: (errs) => {
                    Object.entries(errs).forEach(([k, msg]) => {
                        setError(k as keyof LoanFormValues, { message: msg });
                    });
                },
                onFinish: () => setSubmitting(false),
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

    const ownedCount = Object.values(ownedState).filter(Boolean).length;

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
                        <div className="flex gap-2">
                            <Link
                                href={route('catalog.show', series.id)}
                                className={cn(buttonVariants({ variant: 'outline' }))}
                            >
                                Lihat Katalog
                            </Link>
                            <Button variant="destructive" size="sm" onClick={() => setDeleteOpen(true)}>
                                <Trash2 className="mr-1.5 h-4 w-4" />
                                Hapus
                            </Button>
                        </div>
                    }
                />
            }
        >
            {/* Series info & progress */}
            <div className="grid gap-6 lg:grid-cols-[auto_1fr]">
                <div className="shrink-0">
                    {series.cover_url ? (
                        <img src={series.cover_url} alt={series.title_romaji} className="w-28 rounded-lg object-cover shadow-sm" />
                    ) : (
                        <div className="flex h-40 w-28 items-center justify-center rounded-lg bg-muted">
                            <BookOpen className="h-8 w-8 text-muted-foreground" />
                        </div>
                    )}
                </div>

                <div className="space-y-3">
                    {series.title_english && <p className="text-muted-foreground">{series.title_english}</p>}
                    <div className="flex flex-wrap gap-2">
                        <SeriesStatusBadge status={series.status} />
                        <SeriesTypeBadge type={series.type} />
                    </div>
                    <div className="text-sm">
                        <p className="text-xs text-muted-foreground">Progress</p>
                        <p className="font-medium">
                            {ownedCount} / {series.total_volumes ?? volumes.length} volume dimiliki
                        </p>
                        {(series.total_volumes ?? volumes.length) > 0 && (
                            <div className="mt-1.5 h-2 w-48 overflow-hidden rounded-full bg-muted">
                                <div
                                    className="h-full rounded-full bg-primary transition-all"
                                    style={{ width: `${Math.min(100, (ownedCount / (series.total_volumes ?? volumes.length)) * 100)}%` }}
                                />
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Volume grid */}
            <div className="mt-8">
                <div className="mb-3 flex items-center gap-2">
                    <h2 className="text-base font-semibold">Volume</h2>
                    <span className="text-xs text-muted-foreground">— klik cover untuk tandai dimiliki</span>
                </div>

                {volumes.length === 0 ? (
                    <p className="py-8 text-center text-sm text-muted-foreground">Belum ada volume.</p>
                ) : (
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                        {volumes.map((v) => {
                            const owned = ownedState[v.id] ?? false;
                            return (
                                <div key={v.id} className={`flex flex-col overflow-hidden rounded-lg border transition-all ${owned ? 'border-primary/50' : ''}`}>
                                    {/* Cover + toggle */}
                                    <button
                                        type="button"
                                        className="relative aspect-[2/3] overflow-hidden bg-muted cursor-pointer focus:outline-none"
                                        onClick={() => toggleOwned(v)}
                                        disabled={togglingId === v.id}
                                        aria-label={owned ? 'Tandai belum dimiliki' : 'Tandai dimiliki'}
                                    >
                                        {v.cover_url ? (
                                            <img src={v.cover_url} alt={`Vol ${v.volume_number}`} className="h-full w-full object-cover" />
                                        ) : (
                                            <div className="flex h-full items-center justify-center">
                                                <BookOpen className="h-6 w-6 text-muted-foreground/30" />
                                            </div>
                                        )}
                                        {owned && (
                                            <div className="absolute inset-0 flex items-center justify-center bg-primary/20">
                                                <div className="rounded-full bg-primary p-1.5">
                                                    <svg className="h-4 w-4 text-primary-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </div>
                                            </div>
                                        )}
                                    </button>

                                    {/* Info + loan */}
                                    <div className="flex flex-col gap-1 p-2">
                                        <p className="text-xs font-medium">Vol. {v.volume_number}</p>

                                        {v.active_loan ? (
                                            <div className="space-y-1">
                                                <LoanStatusBadge loan={v.active_loan} />
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
                                        ) : owned ? (
                                            <button
                                                type="button"
                                                className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                                                onClick={() => setLoanTarget(v)}
                                            >
                                                <BookMarked className="h-3 w-3" />
                                                Pinjamkan
                                            </button>
                                        ) : null}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>

            {/* Loan dialog */}
            <Dialog open={!!loanTarget} onOpenChange={(open) => { if (!open) { setLoanTarget(null); reset({ loaned_at: today }); } }}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Pinjamkan Volume #{loanTarget?.volume_number}</DialogTitle>
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
                            <Button type="submit" disabled={submitting}>
                                {submitting ? 'Menyimpan...' : 'Catat Pinjaman'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete collection dialog */}
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
