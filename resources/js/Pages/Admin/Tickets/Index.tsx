import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Search } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import EmptyState from '@/Components/app/EmptyState';
import { Pagination } from '@/Components/app/Pagination';
import { TicketStatusBadge, TicketTypeBadge } from '@/Components/app/StatusBadge';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import { type PaginatedData, type TicketStatus, type TicketType } from '@/lib/types';
import { PageProps } from '@/types';

interface TicketRow {
    id: string;
    subject: string;
    type: TicketType;
    status: TicketStatus;
    user_name: string;
    series: { id: string; title_romaji: string } | null;
    created_at: string;
}

interface Props extends PageProps {
    tickets: PaginatedData<TicketRow>;
    filters: { status: string | null };
}

export default function AdminTicketsIndex({ tickets, filters }: Props) {
    const { t } = useTranslation('admin');

    const STATUS_FILTER_LABELS: Record<string, string> = {
        all: t('tickets.allStatuses'),
        open: t('common:badge.ticketStatus.open'),
        in_progress: t('common:badge.ticketStatus.in_progress'),
        resolved: t('common:badge.ticketStatus.resolved'),
        closed: t('common:badge.ticketStatus.closed'),
    };

    function applyFilter(status: string | null) {
        router.get(
            route('admin.tickets.index'),
            { status: !status || status === 'all' ? '' : status },
            { preserveState: true, replace: true },
        );
    }

    return (
        <AdminLayout
            header={
                <PageHeader
                    title={t('tickets.title')}
                    description={t('tickets.description', { count: tickets.total })}
                />
            }
        >
            <Head title={t('tickets.title')} />

            <div className="mb-4">
                <Select value={filters.status ?? 'all'} onValueChange={applyFilter}>
                    <SelectTrigger className="w-44">
                        <SelectValue placeholder={t('tickets.allStatuses')}>
                            {(value: string) => STATUS_FILTER_LABELS[value] ?? t('tickets.allStatuses')}
                        </SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">{t('tickets.allStatuses')}</SelectItem>
                        <SelectItem value="open">{STATUS_FILTER_LABELS.open}</SelectItem>
                        <SelectItem value="in_progress">{STATUS_FILTER_LABELS.in_progress}</SelectItem>
                        <SelectItem value="resolved">{STATUS_FILTER_LABELS.resolved}</SelectItem>
                        <SelectItem value="closed">{STATUS_FILTER_LABELS.closed}</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            {tickets.data.length === 0 ? (
                <EmptyState
                    title={t('tickets.emptyTitle')}
                    description={t('tickets.emptyDescription')}
                    icon={Search}
                />
            ) : (
                <>
                    <div className="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{t('tickets.table.subject')}</TableHead>
                                    <TableHead>{t('tickets.table.user')}</TableHead>
                                    <TableHead className="w-40">{t('tickets.table.type')}</TableHead>
                                    <TableHead className="w-32">{t('tickets.table.status')}</TableHead>
                                    <TableHead className="w-28">{t('tickets.table.created')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {tickets.data.map((ticket) => (
                                    <TableRow
                                        key={ticket.id}
                                        className="cursor-pointer"
                                        onClick={() => router.visit(route('admin.tickets.show', ticket.id))}
                                    >
                                        <TableCell>
                                            <p className="font-medium">{ticket.subject}</p>
                                            {ticket.series && (
                                                <p className="text-xs text-muted-foreground">{t('tickets.relatedTo', { title: ticket.series.title_romaji })}</p>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-sm">{ticket.user_name}</TableCell>
                                        <TableCell><TicketTypeBadge type={ticket.type} /></TableCell>
                                        <TableCell><TicketStatusBadge status={ticket.status} /></TableCell>
                                        <TableCell className="text-sm text-muted-foreground">{ticket.created_at}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                    <div className="mt-4">
                        <Pagination
                            data={tickets}
                            routeName="admin.tickets.index"
                            filters={{ status: filters.status }}
                        />
                    </div>
                </>
            )}
        </AdminLayout>
    );
}
