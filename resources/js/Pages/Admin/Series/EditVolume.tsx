import { useRef, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import { type VolumeType } from '@/lib/types';

interface VolumeData {
    id: string;
    series_id: string;
    volume_number: number;
    type: VolumeType;
    isbn: string | null;
    published_at: string | null;
    cover_url: string | null;
}

interface Props extends PageProps {
    volume: VolumeData;
    series: { id: string; title_romaji: string };
}

const schema = z.object({
    volume_number: z.string().min(1, 'Wajib diisi'),
    type:          z.enum(['regular', 'digital', 'bind_up']),
    isbn:          z.string().optional(),
    published_at:  z.string().optional(),
});

type FormValues = z.infer<typeof schema>;

function FieldError({ message }: { message?: string }) {
    if (!message) return null;
    return <p className="text-sm text-destructive">{message}</p>;
}

export default function EditVolume({ volume, series }: Props) {
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
        defaultValues: {
            volume_number: String(volume.volume_number),
            type:          volume.type,
            isbn:          volume.isbn ?? '',
            published_at:  volume.published_at ?? '',
        },
    });

    function onSubmit(values: FormValues) {
        setSubmitting(true);
        const fd = new FormData();
        fd.append('_method', 'PUT');
        Object.entries(values).forEach(([k, v]) => {
            if (v !== undefined && v !== '') fd.append(k, v);
        });
        if (coverFile) fd.append('cover', coverFile);

        router.post(route('admin.volumes.update', volume.id), fd, {
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
                    title={`Edit Volume #${volume.volume_number}`}
                    breadcrumbs={[
                        { label: 'Series', href: route('admin.series.index') },
                        { label: series.title_romaji, href: route('admin.series.show', series.id) },
                        { label: `Volume #${volume.volume_number}` },
                    ]}
                />
            }
        >
            <form onSubmit={handleSubmit(onSubmit)} className="max-w-md space-y-4">
                <div className="grid grid-cols-2 gap-3">
                    <div className="space-y-1.5">
                        <Label htmlFor="volume_number">Nomor Volume <span className="text-destructive">*</span></Label>
                        <Input id="volume_number" type="number" min={1} {...register('volume_number')} />
                        <FieldError message={errors.volume_number?.message} />
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
                                        <SelectItem value="regular">Regular</SelectItem>
                                        <SelectItem value="digital">Digital</SelectItem>
                                        <SelectItem value="bind_up">Bind-up</SelectItem>
                                    </SelectContent>
                                </Select>
                            )}
                        />
                        <FieldError message={errors.type?.message} />
                    </div>
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="isbn">ISBN</Label>
                    <Input id="isbn" {...register('isbn')} />
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="published_at">Tanggal Terbit</Label>
                    <Input id="published_at" type="date" {...register('published_at')} />
                </div>

                <div className="space-y-1.5">
                    <Label>Cover</Label>
                    {volume.cover_url && !coverFile && (
                        <img
                            src={volume.cover_url}
                            alt={`Cover Volume #${volume.volume_number}`}
                            className="h-24 w-16 rounded object-cover"
                        />
                    )}
                    <Input
                        ref={fileRef}
                        type="file"
                        accept="image/*"
                        className="cursor-pointer"
                        onChange={(e) => setCoverFile(e.target.files?.[0] ?? null)}
                    />
                    {coverFile && <p className="text-xs text-muted-foreground">Baru: {coverFile.name}</p>}
                </div>

                <div className="flex gap-3 pt-2">
                    <Button type="submit" disabled={submitting}>
                        {submitting ? 'Menyimpan...' : 'Simpan Perubahan'}
                    </Button>
                    <Link
                        href={route('admin.series.show', series.id)}
                        className={cn(buttonVariants({ variant: 'outline' }))}
                    >
                        Batal
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}
