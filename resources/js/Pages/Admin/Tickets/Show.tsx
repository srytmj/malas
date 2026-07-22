import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Send } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { TicketStatusBadge, TicketTypeBadge } from '@/Components/app/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { PageProps } from '@/types';
import { type TicketStatus, type TicketType } from '@/lib/types';

interface TicketDetail {
    id: string;
    subject: string;
    type: TicketType;
    message: string;
    status: TicketStatus;
    admin_response: string | null;
    responded_at: string | null;
    responded_by: string | null;
    created_at: string;
    user: { name: string; email: string };
    series: { id: string; title_romaji: string } | null;
}

interface Props extends PageProps {
    ticket: TicketDetail;
    can: { respond: boolean };
}

const respondSchema = z.object({
    admin_response: z.string().min(1, 'Wajib diisi').max(5000),
    status:         z.enum(['open', 'in_progress', 'resolved', 'closed']),
});
type RespondFormValues = z.infer<typeof respondSchema>;

function FieldError({ message }: { message?: string }) {
    if (!message) return null;
    return <p className="text-xs text-destructive">{message}</p>;
}

export default function AdminTicketShow({ ticket, can }: Props) {
    const [submitting, setSubmitting] = useState(false);

    const {
        register, control, handleSubmit,
        formState: { errors },
    } = useForm<RespondFormValues>({
        resolver: zodResolver(respondSchema),
        defaultValues: {
            admin_response: ticket.admin_response ?? '',
            status:         ticket.status === 'open' ? 'in_progress' : ticket.status,
        },
    });

    function onSubmit(values: RespondFormValues) {
        setSubmitting(true);
        router.patch(route('admin.tickets.respond', ticket.id), values, {
            onFinish: () => setSubmitting(false),
        });
    }

    return (
        <AdminLayout
            header={
                <PageHeader
                    title={ticket.subject}
                    breadcrumbs={[
                        { label: 'Tiket', href: route('admin.tickets.index') },
                        { label: ticket.subject },
                    ]}
                />
            }
        >
            <Head title={ticket.subject} />

            <div className="max-w-2xl space-y-4">
                <div className="flex flex-wrap items-center gap-2">
                    <TicketStatusBadge status={ticket.status} />
                    <TicketTypeBadge type={ticket.type} />
                    {ticket.series && (
                        <Link href={route('admin.series.show', ticket.series.id)} className="text-sm text-muted-foreground hover:underline">
                            Terkait: {ticket.series.title_romaji}
                        </Link>
                    )}
                    <span className="ml-auto text-xs text-muted-foreground">Dibuat {ticket.created_at}</span>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            {ticket.user.name}
                            <span className="ml-2 text-xs font-normal text-muted-foreground">{ticket.user.email}</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="whitespace-pre-wrap text-sm text-muted-foreground leading-relaxed">{ticket.message}</p>
                    </CardContent>
                </Card>

                {can.respond && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                {ticket.admin_response ? 'Perbarui Respons' : 'Beri Respons'}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                                <div className="space-y-1.5">
                                    <Label htmlFor="admin_response">Respons <span className="text-destructive">*</span></Label>
                                    <Textarea id="admin_response" rows={5} className="resize-none" {...register('admin_response')} />
                                    <FieldError message={errors.admin_response?.message} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label>Status <span className="text-destructive">*</span></Label>
                                    <Controller<RespondFormValues, 'status'>
                                        control={control}
                                        name="status"
                                        render={({ field }) => (
                                            <Select value={field.value} onValueChange={field.onChange}>
                                                <SelectTrigger className="w-48">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="open">Terbuka</SelectItem>
                                                    <SelectItem value="in_progress">Diproses</SelectItem>
                                                    <SelectItem value="resolved">Selesai</SelectItem>
                                                    <SelectItem value="closed">Ditutup</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        )}
                                    />
                                </div>

                                <Button type="submit" disabled={submitting}>
                                    <Send className="mr-1.5 h-3.5 w-3.5" />
                                    {submitting ? 'Mengirim...' : 'Kirim Respons'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {ticket.responded_at && (
                    <p className="text-xs text-muted-foreground">
                        Terakhir dibalas oleh {ticket.responded_by} pada {ticket.responded_at}
                    </p>
                )}
            </div>
        </AdminLayout>
    );
}
