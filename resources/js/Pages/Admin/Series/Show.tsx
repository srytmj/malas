import { useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';
import { Pencil, Trash2, Plus, BookOpen, Users } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import EmptyState from '@/Components/app/EmptyState';
import { SeriesStatusBadge, SeriesTypeBadge, VolumeTypeBadge, VolumeFormatBadge } from '@/Components/app/StatusBadge';
import { Badge } from '@/Components/ui/badge';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/Components/ui/dialog';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import { type SeriesStatus, type SeriesType, type VolumeType, type CollectionVolumeFormat } from '@/lib/types';

interface VolumeRow {
    id: string;
    volume_number: number;
    type: VolumeType;
    isbn: string | null;
    published_at: string | null;
    cover_url: string | null;
}

interface SeriesDetail {
    id: string;
    slug: string;
    title_romaji: string;
    title_english: string | null;
    title_japanese: string | null;
    synopsis: string | null;
    status: SeriesStatus;
    type: SeriesType;
    published_from: string | null;
    published_to: string | null;
    total_volumes: number | null;
    score: number | null;
    rank: number | null;
    cover_url: string | null;
    genres: string[] | null;
    authors: string[] | null;
    themes: string[] | null;
    demographics: string[] | null;
}

interface OwnershipRow {
    id: string;
    volume_number: number;
    format: CollectionVolumeFormat;
    user_name: string;
    active_loan: { borrower_name: string } | null;
}

interface Props extends PageProps {
    series: SeriesDetail;
    volumes: VolumeRow[];
    can: { update: boolean; delete: boolean; createVolume: boolean };
    ownerships: OwnershipRow[];
}

const volumeSchema = z.object({
    volume_number: z.string().min(1),
    type:          z.enum(['regular', 'digital', 'bind_up']),
    isbn:          z.string().optional(),
    published_at:  z.string().optional(),
});

type VolumeFormValues = z.infer<typeof volumeSchema>;

function FieldError({ message }: { message?: string }) {
    if (!message) return null;
    return <p className="text-sm text-destructive">{message}</p>;
}

export default function SeriesShow({ series, volumes, can, ownerships }: Props) {
    const { t } = useTranslation('admin');

    const VOLUME_TYPE_LABELS: Record<string, string> = {
        regular: t('common:badge.volumeType.regular'),
        digital: t('common:badge.volumeType.digital'),
        bind_up: t('common:badge.volumeType.bind_up'),
    };

    const [addVolumeOpen, setAddVolumeOpen] = useState(false);
    const [deleteVolume, setDeleteVolume]   = useState<VolumeRow | null>(null);
    const [deleteSeries, setDeleteSeries]   = useState(false);
    const [submitting, setSubmitting]       = useState(false);
    const [deletingVol, setDeletingVol]     = useState(false);
    const [deletingSeries, setDeletingSeries] = useState(false);
    const [coverFile, setCoverFile]         = useState<File | null>(null);
    const fileRef = useRef<HTMLInputElement>(null);

    const {
        register,
        control,
        handleSubmit,
        setError,
        reset,
        formState: { errors },
    } = useForm<VolumeFormValues>({
        resolver: zodResolver(volumeSchema),
        defaultValues: { type: 'regular' },
    });

    function onAddVolume(values: VolumeFormValues) {
        setSubmitting(true);
        const fd = new FormData();
        Object.entries(values).forEach(([k, v]) => {
            if (v !== undefined && v !== '') fd.append(k, v);
        });
        if (coverFile) fd.append('cover', coverFile);

        router.post(route('admin.series.volumes.store', series.id), fd, {
            forceFormData: true,
            onSuccess: () => {
                reset({ type: 'regular' });
                setCoverFile(null);
                setAddVolumeOpen(false);
            },
            onError: (errs) => {
                Object.entries(errs).forEach(([k, msg]) => {
                    setError(k as keyof VolumeFormValues, { message: msg });
                });
            },
            onFinish: () => setSubmitting(false),
        });
    }

    function handleDeleteVolume() {
        if (!deleteVolume) return;
        setDeletingVol(true);
        router.delete(route('admin.volumes.destroy', deleteVolume.id), {
            onFinish: () => { setDeletingVol(false); setDeleteVolume(null); },
        });
    }

    function handleDeleteSeries() {
        setDeletingSeries(true);
        router.delete(route('admin.series.destroy', series.id), {
            onSuccess: () => router.visit(route('admin.series.index')),
            onFinish: () => setDeletingSeries(false),
        });
    }

    return (
        <AdminLayout
            header={
                <PageHeader
                    title={series.title_romaji}
                    breadcrumbs={[
                        { label: t('series.breadcrumb'), href: route('admin.series.index') },
                        { label: series.title_romaji },
                    ]}
                    actions={
                        can.update ? (
                            <div className="flex gap-2">
                                <Link
                                    href={route('admin.series.edit', series.slug)}
                                    className={cn(buttonVariants({ variant: 'outline' }))}
                                >
                                    <Pencil className="mr-1.5 h-4 w-4" />
                                    {t('common:common.edit')}
                                </Link>
                                {can.delete && (
                                    <Button variant="destructive" onClick={() => setDeleteSeries(true)}>
                                        <Trash2 className="mr-1.5 h-4 w-4" />
                                        {t('common:common.delete')}
                                    </Button>
                                )}
                            </div>
                        ) : undefined
                    }
                />
            }
        >
            <Head title={series.title_romaji} />
            {/* Series info */}
            <div className="grid gap-6 lg:grid-cols-[auto_1fr]">
                <div className="shrink-0">
                    {series.cover_url ? (
                        <img src={series.cover_url} alt={series.title_romaji} className="w-32 rounded-lg object-cover shadow-sm" />
                    ) : (
                        <div className="flex h-44 w-32 items-center justify-center rounded-lg bg-muted">
                            <BookOpen className="h-8 w-8 text-muted-foreground" />
                        </div>
                    )}
                </div>

                <div className="space-y-3">
                    {series.title_english && <p className="text-muted-foreground">{series.title_english}</p>}
                    {series.title_japanese && <p className="text-sm text-muted-foreground">{series.title_japanese}</p>}
                    {series.authors && series.authors.length > 0 && (
                        <p className="text-sm text-muted-foreground">
                            <span className="text-xs">{t('series.show.author')}</span> {series.authors.join(', ')}
                        </p>
                    )}

                    <div className="flex flex-wrap gap-2">
                        <SeriesStatusBadge status={series.status} />
                        <SeriesTypeBadge type={series.type} />
                        {series.demographics?.map((d) => (
                            <Badge key={d} variant="secondary">{d}</Badge>
                        ))}
                    </div>

                    {((series.genres?.length ?? 0) > 0 || (series.themes?.length ?? 0) > 0) && (
                        <div className="flex flex-wrap gap-1.5">
                            {series.genres?.map((g) => (
                                <Badge key={g} variant="outline">{g}</Badge>
                            ))}
                            {series.themes?.map((theme) => (
                                <Badge key={theme} variant="outline" className="text-muted-foreground">{theme}</Badge>
                            ))}
                        </div>
                    )}

                    <div className="grid grid-cols-2 gap-x-8 gap-y-1 text-sm sm:grid-cols-4">
                        <div>
                            <p className="text-xs text-muted-foreground">{t('series.totalVolumes')}</p>
                            <p className="font-medium">{series.total_volumes ?? '—'}</p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">{t('series.show.score')}</p>
                            <p className="font-medium">{series.score !== null ? Number(series.score).toFixed(2) : '—'}</p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">{t('series.rank')}</p>
                            <p className="font-medium">{series.rank ?? '—'}</p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">{t('series.published')}</p>
                            <p className="font-medium">
                                {series.published_from ?? '—'}
                                {series.published_to ? ` – ${series.published_to}` : ''}
                            </p>
                        </div>
                    </div>

                    {series.synopsis && (
                        <p className="text-sm text-muted-foreground leading-relaxed line-clamp-4">{series.synopsis}</p>
                    )}
                </div>
            </div>

            {/* Volumes */}
            <div className="mt-8">
                <div className="mb-3 flex items-center justify-between">
                    <h2 className="text-base font-semibold">{t('series.volumeSection', { count: volumes.length })}</h2>
                    {can.createVolume && (
                        <Button size="sm" onClick={() => setAddVolumeOpen(true)}>
                            <Plus className="mr-1.5 h-4 w-4" />
                            {t('series.addVolume')}
                        </Button>
                    )}
                </div>

                {volumes.length === 0 ? (
                    <EmptyState
                        title={t('series.emptyVolumesTitle')}
                        description={t('series.emptyVolumesDescription')}
                        icon={BookOpen}
                        action={can.createVolume
                            ? <Button size="sm" onClick={() => setAddVolumeOpen(true)}>{t('series.addVolume')}</Button>
                            : undefined}
                    />
                ) : (
                    <div className="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12" />
                                    <TableHead className="w-24">{t('series.table.volume')}</TableHead>
                                    <TableHead className="w-28">{t('series.table.type')}</TableHead>
                                    <TableHead>{t('series.table.isbn')}</TableHead>
                                    <TableHead className="w-32">{t('series.table.published')}</TableHead>
                                    <TableHead className="w-20" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {volumes.map((v) => (
                                    <TableRow key={v.id}>
                                        <TableCell>
                                            {v.cover_url
                                                ? <img src={v.cover_url} alt={`Vol ${v.volume_number}`} className="h-10 w-7 rounded object-cover" />
                                                : <div className="h-10 w-7 rounded bg-muted" />}
                                        </TableCell>
                                        <TableCell className="font-medium">#{v.volume_number}</TableCell>
                                        <TableCell><VolumeTypeBadge type={v.type} /></TableCell>
                                        <TableCell className="text-sm text-muted-foreground">{v.isbn ?? '—'}</TableCell>
                                        <TableCell className="text-sm text-muted-foreground">{v.published_at ?? '—'}</TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8"
                                                    onClick={() => router.visit(route('admin.volumes.edit', v.id))}
                                                >
                                                    <Pencil className="h-3.5 w-3.5" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-destructive hover:text-destructive"
                                                    onClick={() => setDeleteVolume(v)}
                                                >
                                                    <Trash2 className="h-3.5 w-3.5" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>

            {/* Ownership section */}
            {ownerships.length > 0 && (
                <div className="mt-8">
                    <div className="mb-3 flex items-center gap-2">
                        <Users className="h-4 w-4 text-muted-foreground" />
                        <h2 className="text-base font-semibold">{t('series.show.ownershipSection', { count: ownerships.length })}</h2>
                    </div>
                    <div className="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-20">{t('series.show.vol')}</TableHead>
                                    <TableHead>{t('series.show.owner')}</TableHead>
                                    <TableHead className="w-28">{t('common:common.type')}</TableHead>
                                    <TableHead className="w-40">{t('common:common.status')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {ownerships.map((o) => (
                                    <TableRow key={o.id}>
                                        <TableCell className="font-medium">#{o.volume_number}</TableCell>
                                        <TableCell className="text-sm">{o.user_name}</TableCell>
                                        <TableCell><VolumeFormatBadge format={o.format} /></TableCell>
                                        <TableCell>
                                            {o.active_loan ? (
                                                <span className="text-xs text-yellow-600 dark:text-yellow-400">
                                                    {t('series.show.onLoanTo', { name: o.active_loan.borrower_name })}
                                                </span>
                                            ) : (
                                                <span className="text-xs text-muted-foreground">{t('series.show.available')}</span>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </div>
            )}

            {/* Add Volume Dialog */}
            <Dialog open={addVolumeOpen} onOpenChange={(open) => { setAddVolumeOpen(open); if (!open) reset({ type: 'regular' }); }}>
                <DialogContent>
                    <DialogHeader><DialogTitle>{t('series.addVolume')}</DialogTitle></DialogHeader>
                    <form onSubmit={handleSubmit(onAddVolume)} className="space-y-4">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="volume_number">{t('series.volumeNumberLabel')} <span className="text-destructive">*</span></Label>
                                <Input id="volume_number" type="number" min={1} {...register('volume_number')} />
                                <FieldError message={errors.volume_number ? t('common:common.required') : undefined} />
                            </div>
                            <div className="space-y-1.5">
                                <Label>{t('series.typeLabel')} <span className="text-destructive">*</span></Label>
                                <Controller<VolumeFormValues, 'type'>
                                    control={control}
                                    name="type"
                                    render={({ field }) => (
                                        <Select value={field.value} onValueChange={field.onChange}>
                                            <SelectTrigger><SelectValue>{(value: string) => VOLUME_TYPE_LABELS[value] ?? value}</SelectValue></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="regular">{VOLUME_TYPE_LABELS.regular}</SelectItem>
                                                <SelectItem value="digital">{VOLUME_TYPE_LABELS.digital}</SelectItem>
                                                <SelectItem value="bind_up">{VOLUME_TYPE_LABELS.bind_up}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    )}
                                />
                            </div>
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="isbn">{t('series.isbnLabel')}</Label>
                            <Input id="isbn" {...register('isbn')} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="published_at">{t('series.publishedAtLabel')}</Label>
                            <Input id="published_at" type="date" {...register('published_at')} />
                        </div>
                        <div className="space-y-1.5">
                            <Label>{t('series.coverLabel')}</Label>
                            <Input
                                ref={fileRef}
                                type="file"
                                accept="image/*"
                                className="cursor-pointer"
                                onChange={(e) => setCoverFile(e.target.files?.[0] ?? null)}
                            />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setAddVolumeOpen(false)}>{t('common:common.cancel')}</Button>
                            <Button type="submit" disabled={submitting}>
                                {submitting ? t('common:common.saving') : t('common:common.save')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Volume Dialog */}
            <Dialog open={!!deleteVolume} onOpenChange={(open) => !open && setDeleteVolume(null)}>
                <DialogContent>
                    <DialogHeader><DialogTitle>{t('series.deleteVolumeTitle', { number: deleteVolume?.volume_number })}</DialogTitle></DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        {t('series.deleteVolumeConfirm')}
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteVolume(null)}>{t('common:common.cancel')}</Button>
                        <Button variant="destructive" disabled={deletingVol} onClick={handleDeleteVolume}>
                            {deletingVol ? t('common:common.deleting') : t('common:common.delete')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete Series Dialog */}
            <Dialog open={deleteSeries} onOpenChange={setDeleteSeries}>
                <DialogContent>
                    <DialogHeader><DialogTitle>{t('series.show.deleteSeriesTitle')}</DialogTitle></DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        {t('series.show.deleteSeriesConfirmPrefix')} <strong>{series.title_romaji}</strong>{t('series.show.deleteSeriesConfirmSuffix')}
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteSeries(false)}>{t('common:common.cancel')}</Button>
                        <Button variant="destructive" disabled={deletingSeries} onClick={handleDeleteSeries}>
                            {deletingSeries ? t('common:common.deleting') : t('common:common.delete')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
