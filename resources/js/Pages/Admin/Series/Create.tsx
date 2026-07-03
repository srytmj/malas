import { useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { cn } from '@/lib/utils';

const schema = z.object({
    title_romaji:   z.string().min(1, 'Wajib diisi'),
    title_english:  z.string().optional(),
    title_japanese: z.string().optional(),
    synopsis:       z.string().optional(),
    status:         z.enum(['publishing', 'finished', 'on_hiatus', 'discontinued', 'not_yet_published']),
    type:           z.enum(['manga', 'manhwa', 'manhua', 'novel', 'one_shot', 'doujinshi']),
    published_from: z.string().optional(),
    published_to:   z.string().optional(),
    total_volumes:  z.string().optional(),
    score:          z.string().optional(),
    rank:           z.string().optional(),
}).refine(
    (data) => !data.published_from || !data.published_to || data.published_to >= data.published_from,
    { message: 'Tanggal akhir tidak boleh sebelum tanggal mulai', path: ['published_to'] },
);

type FormValues = z.infer<typeof schema>;

function FieldError({ message }: { message?: string }) {
    if (!message) return null;
    return <p className="text-sm text-destructive">{message}</p>;
}

export default function SeriesCreate() {
    const [coverFile, setCoverFile] = useState<File | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const fileRef = useRef<HTMLInputElement>(null);

    const {
        register,
        control,
        handleSubmit,
        setError,
        formState: { errors },
    } = useForm<FormValues>({
        resolver: zodResolver(schema),
        defaultValues: { status: 'publishing', type: 'manga' },
    });

    function onSubmit(values: FormValues) {
        setSubmitting(true);
        const fd = new FormData();
        Object.entries(values).forEach(([k, v]) => {
            if (v !== undefined && v !== '') fd.append(k, v);
        });
        if (coverFile) fd.append('cover', coverFile);

        router.post(route('admin.series.store'), fd, {
            forceFormData: true,
            onError: (errs) => {
                Object.entries(errs).forEach(([k, msg]) => {
                    setError(k as keyof FormValues, { message: msg });
                });
                setSubmitting(false);
            },
            onFinish: () => setSubmitting(false),
        });
    }

    return (
        <AdminLayout
            header={
                <PageHeader
                    title="Tambah Series"
                    breadcrumbs={[
                        { label: 'Series', href: route('admin.series.index') },
                        { label: 'Tambah' },
                    ]}
                />
            }
        >
            <Head title="Tambah Series" />
            <form onSubmit={handleSubmit(onSubmit)} className="max-w-2xl space-y-5">
                <div className="space-y-1.5">
                    <Label htmlFor="title_romaji">Judul Romaji <span className="text-destructive">*</span></Label>
                    <Input id="title_romaji" {...register('title_romaji')} />
                    <FieldError message={errors.title_romaji?.message} />
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="title_english">Judul Inggris</Label>
                    <Input id="title_english" {...register('title_english')} />
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="title_japanese">Judul Jepang</Label>
                    <Input id="title_japanese" {...register('title_japanese')} />
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-1.5">
                        <Label>Status <span className="text-destructive">*</span></Label>
                        <Controller<FormValues, 'status'>
                            control={control}
                            name="status"
                            render={({ field }) => (
                                <Select value={field.value} onValueChange={field.onChange}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="publishing">Publishing</SelectItem>
                                        <SelectItem value="finished">Selesai</SelectItem>
                                        <SelectItem value="on_hiatus">Hiatus</SelectItem>
                                        <SelectItem value="discontinued">Discontinued</SelectItem>
                                        <SelectItem value="not_yet_published">Belum Terbit</SelectItem>
                                    </SelectContent>
                                </Select>
                            )}
                        />
                        <FieldError message={errors.status?.message} />
                    </div>

                    <div className="space-y-1.5">
                        <Label>Tipe <span className="text-destructive">*</span></Label>
                        <Controller<FormValues, 'type'>
                            control={control}
                            name="type"
                            render={({ field }) => (
                                <Select value={field.value} onValueChange={field.onChange}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="manga">Manga</SelectItem>
                                        <SelectItem value="manhwa">Manhwa</SelectItem>
                                        <SelectItem value="manhua">Manhua</SelectItem>
                                        <SelectItem value="novel">Novel</SelectItem>
                                        <SelectItem value="one_shot">One Shot</SelectItem>
                                        <SelectItem value="doujinshi">Doujinshi</SelectItem>
                                    </SelectContent>
                                </Select>
                            )}
                        />
                        <FieldError message={errors.type?.message} />
                    </div>
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="synopsis">Sinopsis</Label>
                    <Textarea id="synopsis" rows={4} className="resize-none" {...register('synopsis')} />
                </div>

                <div className="space-y-1.5">
                    <Label>Cover</Label>
                    <Input
                        ref={fileRef}
                        type="file"
                        accept="image/*"
                        className="cursor-pointer"
                        onChange={(e) => setCoverFile(e.target.files?.[0] ?? null)}
                    />
                    {coverFile && <p className="text-xs text-muted-foreground">{coverFile.name}</p>}
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="published_from">Terbit Mulai</Label>
                        <Input id="published_from" type="date" {...register('published_from')} />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="published_to">Terbit Sampai</Label>
                        <Input id="published_to" type="date" {...register('published_to')} />
                    </div>
                </div>

                <div className="grid grid-cols-3 gap-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="total_volumes">Total Volume</Label>
                        <Input id="total_volumes" type="number" min={1} {...register('total_volumes')} />
                        <FieldError message={errors.total_volumes?.message} />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="score">Skor (0–10)</Label>
                        <Input id="score" type="number" step="0.01" min={0} max={10} {...register('score')} />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="rank">Rank</Label>
                        <Input id="rank" type="number" min={1} {...register('rank')} />
                    </div>
                </div>

                <div className="flex gap-3 pt-2">
                    <Button type="submit" disabled={submitting}>
                        {submitting ? 'Menyimpan...' : 'Simpan'}
                    </Button>
                    <Link
                        href={route('admin.series.index')}
                        className={cn(buttonVariants({ variant: 'outline' }))}
                    >
                        Batal
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}
