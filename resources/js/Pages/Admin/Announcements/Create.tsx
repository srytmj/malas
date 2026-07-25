import { useState } from 'react';
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
import { Switch } from '@/Components/ui/switch';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { cn } from '@/lib/utils';

const schema = z.object({
    title:      z.string().min(1, 'Wajib diisi').max(255),
    body:       z.string().min(1, 'Wajib diisi'),
    type:       z.enum(['info', 'warning', 'danger', 'success']),
    is_active:  z.boolean(),
    starts_at:  z.string().optional(),
    expires_at: z.string().optional(),
});

type FormValues = z.infer<typeof schema>;

const ANNOUNCEMENT_TYPE_LABELS: Record<string, string> = {
    info: 'Info',
    warning: 'Peringatan',
    danger: 'Bahaya',
    success: 'Sukses',
};

function FieldError({ message }: { message?: string }) {
    if (!message) return null;
    return <p className="text-sm text-destructive">{message}</p>;
}

export default function AnnouncementCreate() {
    const [submitting, setSubmitting] = useState(false);

    const {
        register,
        control,
        handleSubmit,
        watch,
        setError,
        formState: { errors },
    } = useForm<FormValues>({
        resolver: zodResolver(schema),
        defaultValues: { type: 'info', is_active: true },
    });

    const bodyValue = watch('body', '');

    function onSubmit(values: FormValues) {
        setSubmitting(true);
        router.post(route('admin.announcements.store'), values, {
            onError: (errs) => {
                Object.entries(errs).forEach(([k, msg]) => {
                    setError(k as keyof FormValues, { message: msg });
                });
            },
            onFinish: () => setSubmitting(false),
        });
    }

    return (
        <AdminLayout
            header={
                <PageHeader
                    title="Buat Pengumuman"
                    breadcrumbs={[
                        { label: 'Pengumuman', href: route('admin.announcements.index') },
                        { label: 'Buat' },
                    ]}
                />
            }
        >
            <Head title="Buat Pengumuman" />
            <form onSubmit={handleSubmit(onSubmit)} className="max-w-2xl space-y-5">
                <div className="space-y-1.5">
                    <Label htmlFor="title">Judul <span className="text-destructive">*</span></Label>
                    <Input id="title" {...register('title')} />
                    <FieldError message={errors.title?.message} />
                </div>

                <div className="space-y-1.5">
                    <Label>Isi Pengumuman <span className="text-destructive">*</span></Label>
                    <Tabs defaultValue="write">
                        <TabsList className="mb-2">
                            <TabsTrigger value="write">Tulis</TabsTrigger>
                            <TabsTrigger value="preview">Preview</TabsTrigger>
                        </TabsList>
                        <TabsContent value="write">
                            <Textarea
                                rows={6}
                                className="resize-y"
                                {...register('body')}
                            />
                        </TabsContent>
                        <TabsContent value="preview">
                            <div className="min-h-[9rem] rounded-md border bg-muted/40 px-3 py-2 text-sm whitespace-pre-wrap">
                                {bodyValue || <span className="text-muted-foreground italic">Belum ada konten.</span>}
                            </div>
                        </TabsContent>
                    </Tabs>
                    <FieldError message={errors.body?.message} />
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-1.5">
                        <Label>Tipe <span className="text-destructive">*</span></Label>
                        <Controller<FormValues, 'type'>
                            control={control}
                            name="type"
                            render={({ field }) => (
                                <Select value={field.value} onValueChange={field.onChange}>
                                    <SelectTrigger><SelectValue>{(value: string) => ANNOUNCEMENT_TYPE_LABELS[value] ?? value}</SelectValue></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="info">Info</SelectItem>
                                        <SelectItem value="warning">Peringatan</SelectItem>
                                        <SelectItem value="danger">Bahaya</SelectItem>
                                        <SelectItem value="success">Sukses</SelectItem>
                                    </SelectContent>
                                </Select>
                            )}
                        />
                        <FieldError message={errors.type?.message} />
                    </div>

                    <div className="space-y-1.5">
                        <Label>Status</Label>
                        <div className="flex h-10 items-center gap-2">
                            <Controller<FormValues, 'is_active'>
                                control={control}
                                name="is_active"
                                render={({ field }) => (
                                    <Switch
                                        checked={field.value}
                                        onCheckedChange={field.onChange}
                                    />
                                )}
                            />
                            <span className="text-sm text-muted-foreground">Aktif</span>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="starts_at">Mulai Tampil</Label>
                        <Input id="starts_at" type="datetime-local" {...register('starts_at')} />
                        <FieldError message={errors.starts_at?.message} />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="expires_at">Berakhir</Label>
                        <Input id="expires_at" type="datetime-local" {...register('expires_at')} />
                        <FieldError message={errors.expires_at?.message} />
                    </div>
                </div>

                <div className="flex gap-3 pt-2">
                    <Button type="submit" disabled={submitting}>
                        {submitting ? 'Menyimpan...' : 'Simpan'}
                    </Button>
                    <Link
                        href={route('admin.announcements.index')}
                        className={cn(buttonVariants({ variant: 'outline' }))}
                    >
                        Batal
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}
