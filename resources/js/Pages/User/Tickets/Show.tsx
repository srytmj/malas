import { Head, Link } from '@inertiajs/react';
import { MessageSquare } from 'lucide-react';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import { TicketStatusBadge, TicketTypeBadge } from '@/Components/app/StatusBadge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
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
    created_at: string;
    series: { id: string; title_romaji: string } | null;
}

interface Props extends PageProps {
    ticket: TicketDetail;
}

export default function TicketShow({ ticket }: Props) {
    return (
        <UserLayout
            header={
                <PageHeader
                    title={ticket.subject}
                    breadcrumbs={[
                        { label: 'Tiket', href: route('tickets.index') },
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
                        <Link href={route('catalog.show', ticket.series.id)} className="text-sm text-muted-foreground hover:underline">
                            Terkait: {ticket.series.title_romaji}
                        </Link>
                    )}
                    <span className="ml-auto text-xs text-muted-foreground">Dibuat {ticket.created_at}</span>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Pesan Kamu</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="whitespace-pre-wrap text-sm text-muted-foreground leading-relaxed">{ticket.message}</p>
                    </CardContent>
                </Card>

                {ticket.admin_response ? (
                    <Card className="border-primary/30 bg-primary/5">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <MessageSquare className="h-4 w-4" />
                                Respons Admin
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1">
                            <p className="whitespace-pre-wrap text-sm leading-relaxed">{ticket.admin_response}</p>
                            {ticket.responded_at && (
                                <p className="text-xs text-muted-foreground">Dibalas {ticket.responded_at}</p>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <p className="text-sm text-muted-foreground">Menunggu respons dari admin.</p>
                )}
            </div>
        </UserLayout>
    );
}
