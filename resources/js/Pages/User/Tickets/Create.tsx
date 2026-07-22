import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { BookOpen, Send } from 'lucide-react';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent } from '@/Components/ui/card';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { PageProps } from '@/types';

interface SeriesRef {
    id: string;
    title_romaji: string;
    cover_url: string | null;
}

interface Props extends PageProps {
    series: SeriesRef | null;
}

const ticketSchema = z.object({
    subject: z.string().min(1, 'Wajib diisi').max(200),
    type:    z.enum(['catalog_request', 'title_revision', 'other']),
    message: z.string().min(1, 'Wajib diisi').max(5000),
});
type TicketFormValues = z.infer<typeof ticketSchema>;

function FieldError({ message }: { message?: string }) {
    if (!message) return null;
    return <p className="text-xs text-destructive">{message}</p>;
}

export default function TicketCreate({ series }: Props) {
    const [submitting, setSubmitting] = useState(false);

    const {
        register, control, handleSubmit, setError,
        formState: { errors },
    } = useForm<TicketFormValues>({
        resolver: zodResolver(ticketSchema),
        defaultValues: {
            type:    series ? 'catalog_request' : 'other',
            subject: series ? `Terkait: ${series.title_romaji}` : '',
        },
    });

    function onSubmit(values: TicketFormValues) {
        setSubmitting(true);
        router.post(
            route('tickets.store'),
            { ...values, series_id: series?.id ?? null },
            {
                onError: (errs) => {
                    Object.entries(errs).forEach(([k, msg]) => {
                        setError(k as keyof TicketFormValues, { message: msg as string });
                    });
                },
                onFinish: () => setSubmitting(false),
            },
        );
    }

    return (
        <UserLayout
            header={
                <PageHeader
                    title="Buat Tiket"
                    breadcrumbs={[
                        { label: 'Tiket', href: route('tickets.index') },
                        { label: 'Buat Tiket' },
                    ]}
                />
            }
        >
            <Head title="Buat Tiket" />

            <div className="max-w-xl">
                {series && (
                    <Card className="mb-4">
                        <CardContent className="flex items-center gap-3 py-4">
                            {series.cover_url ? (
                                <img src={series.cover_url} alt={series.title_romaji} className="h-16 w-11 rounded object-cover" />
                            ) : (
                                <div className="flex h-16 w-11 items-center justify-center rounded bg-muted">
                                    <BookOpen className="h-5 w-5 text-muted-foreground" />
                                </div>
                            )}
                            <div>
                                <p className="text-xs text-muted-foreground">Tiket ini terkait dengan</p>
                                <p className="text-sm font-medium">{series.title_romaji}</p>
                            </div>
                        </CardContent>
                    </Card>
                )}

                <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="subject">Subjek <span className="text-destructive">*</span></Label>
                        <Input id="subject" {...register('subject')} />
                        <FieldError message={errors.subject?.message} />
                    </div>

                    <div className="space-y-1.5">
                        <Label>Tipe <span className="text-destructive">*</span></Label>
                        <Controller<TicketFormValues, 'type'>
                            control={control}
                            name="type"
                            render={({ field }) => (
                                <Select value={field.value} onValueChange={field.onChange}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="catalog_request">Request Katalog</SelectItem>
                                        <SelectItem value="title_revision">Revisi Judul</SelectItem>
                                        <SelectItem value="other">Lainnya</SelectItem>
                                    </SelectContent>
                                </Select>
                            )}
                        />
                        <FieldError message={errors.type?.message} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="message">Pesan <span className="text-destructive">*</span></Label>
                        <Textarea id="message" rows={6} className="resize-none" {...register('message')} />
                        <FieldError message={errors.message?.message} />
                    </div>

                    <Button type="submit" disabled={submitting}>
                        <Send className="mr-1.5 h-3.5 w-3.5" />
                        {submitting ? 'Mengirim...' : 'Kirim Tiket'}
                    </Button>
                </form>
            </div>
        </UserLayout>
    );
}
