import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Plus, Ticket as TicketIcon } from 'lucide-react';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import EmptyState from '@/Components/app/EmptyState';
import { Pagination } from '@/Components/app/Pagination';
import { TicketStatusBadge, TicketTypeBadge } from '@/Components/app/StatusBadge';
import { buttonVariants } from '@/Components/ui/button';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import { type PaginatedData, type TicketStatus, type TicketType } from '@/lib/types';

interface TicketRow {
    id: string;
    subject: string;
    type: TicketType;
    status: TicketStatus;
    series: { id: string; title_romaji: string } | null;
    created_at: string;
}

interface Props extends PageProps {
    tickets: PaginatedData<TicketRow>;
}

export default function TicketsIndex({ tickets }: Props) {
    const { t } = useTranslation('user');
    return (
        <UserLayout
            header={
                <PageHeader
                    title={t('tickets.title')}
                    description={t('tickets.count', { count: tickets.total })}
                    actions={
                        <Link href={route('tickets.create')} className={cn(buttonVariants({ size: 'sm' }))}>
                            <Plus className="mr-1.5 h-3.5 w-3.5" />
                            {t('tickets.newTicket')}
                        </Link>
                    }
                />
            }
        >
            <Head title={t('tickets.title')} />
            {tickets.data.length === 0 ? (
                <EmptyState
                    title={t('tickets.emptyTitle')}
                    description={t('tickets.emptyDescription')}
                    icon={TicketIcon}
                    action={
                        <Link href={route('tickets.create')} className={cn(buttonVariants())}>
                            <Plus className="mr-1.5 h-4 w-4" />
                            {t('tickets.newTicket')}
                        </Link>
                    }
                />
            ) : (
                <>
                    <div className="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{t('tickets.subject')}</TableHead>
                                    <TableHead className="w-40">{t('tickets.type')}</TableHead>
                                    <TableHead className="w-32">{t('tickets.status')}</TableHead>
                                    <TableHead className="w-28">{t('tickets.createdColumn')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {tickets.data.map((ticket) => (
                                    <TableRow
                                        key={ticket.id}
                                        className="cursor-pointer"
                                        onClick={() => router.visit(route('tickets.show', ticket.id))}
                                    >
                                        <TableCell>
                                            <p className="font-medium">{ticket.subject}</p>
                                            {ticket.series && (
                                                <p className="text-xs text-muted-foreground">{t('tickets.related', { title: ticket.series.title_romaji })}</p>
                                            )}
                                        </TableCell>
                                        <TableCell><TicketTypeBadge type={ticket.type} /></TableCell>
                                        <TableCell><TicketStatusBadge status={ticket.status} /></TableCell>
                                        <TableCell className="text-sm text-muted-foreground">{ticket.created_at}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                    <div className="mt-4">
                        <Pagination data={tickets} routeName="tickets.index" />
                    </div>
                </>
            )}
        </UserLayout>
    );
}
