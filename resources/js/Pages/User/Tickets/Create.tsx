import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useTranslation } from 'react-i18next';
import { AlertTriangle, BookOpen, Send } from 'lucide-react';
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
    activeTicketsCount: number;
    maxActiveTickets: number;
    canCreate: boolean;
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

export default function TicketCreate({ series, activeTicketsCount, maxActiveTickets, canCreate }: Props) {
    const { t } = useTranslation('user');
    const ticketTypeLabels: Record<string, string> = {
        catalog_request: t('common:badge.ticketType.catalog_request'),
        title_revision: t('common:badge.ticketType.title_revision'),
        other: t('common:badge.ticketType.other'),
    };
    const [submitting, setSubmitting] = useState(false);

    const {
        register, control, handleSubmit, setError,
        formState: { errors },
    } = useForm<TicketFormValues>({
        resolver: zodResolver(ticketSchema),
        defaultValues: {
            type:    series ? 'catalog_request' : 'other',
            subject: series ? t('tickets.form.defaultSubject', { title: series.title_romaji }) : '',
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
                    title={t('tickets.newTicket')}
                    breadcrumbs={[
                        { label: t('tickets.title'), href: route('tickets.index') },
                        { label: t('tickets.newTicket') },
                    ]}
                />
            }
        >
            <Head title={t('tickets.newTicket')} />

            <div className="max-w-xl">
                <div className={`mb-4 flex items-start gap-2 rounded-lg border p-3 text-sm ${
                    canCreate
                        ? 'border-amber-500/40 bg-amber-500/10 text-amber-700 dark:text-amber-400'
                        : 'border-destructive/50 bg-destructive/10 text-destructive'
                }`}
                >
                    <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />
                    <span>
                        {t('tickets.form.limitWarning', { max: maxActiveTickets })}
                        {!canCreate && ` ${t('tickets.form.limitReached', { active: activeTicketsCount, max: maxActiveTickets })}`}
                    </span>
                </div>

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
                                <p className="text-xs text-muted-foreground">{t('tickets.form.relatedTo')}</p>
                                <p className="text-sm font-medium">{series.title_romaji}</p>
                            </div>
                        </CardContent>
                    </Card>
                )}

                <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="subject">{t('tickets.subject')} <span className="text-destructive">*</span></Label>
                        <Input id="subject" {...register('subject')} />
                        <FieldError message={errors.subject?.message} />
                    </div>

                    <div className="space-y-1.5">
                        <Label>{t('tickets.type')} <span className="text-destructive">*</span></Label>
                        <Controller<TicketFormValues, 'type'>
                            control={control}
                            name="type"
                            render={({ field }) => (
                                <Select value={field.value} onValueChange={field.onChange}>
                                    <SelectTrigger>
                                        <SelectValue>
                                            {(value: string) => ticketTypeLabels[value] ?? value}
                                        </SelectValue>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="catalog_request">{t('common:badge.ticketType.catalog_request')}</SelectItem>
                                        <SelectItem value="title_revision">{t('common:badge.ticketType.title_revision')}</SelectItem>
                                        <SelectItem value="other">{t('common:badge.ticketType.other')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            )}
                        />
                        <FieldError message={errors.type?.message} />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="message">{t('tickets.form.message')} <span className="text-destructive">*</span></Label>
                        <Textarea id="message" rows={6} className="resize-none" {...register('message')} />
                        <FieldError message={errors.message?.message} />
                    </div>

                    <Button type="submit" disabled={submitting || !canCreate}>
                        <Send className="mr-1.5 h-3.5 w-3.5" />
                        {submitting ? t('tickets.form.submitting') : t('tickets.form.submit')}
                    </Button>
                </form>
            </div>
        </UserLayout>
    );
}
