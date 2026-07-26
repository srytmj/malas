import { Head, Link, router } from '@inertiajs/react';
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
    return (
        <UserLayout
            header={
                <PageHeader
                    title="Tiket"
                    description={`${tickets.total} tiket dikirim`}
                    actions={
                        <Link href={route('tickets.create')} className={cn(buttonVariants({ size: 'sm' }))}>
                            <Plus className="mr-1.5 h-3.5 w-3.5" />
                            Buat Tiket
                        </Link>
                    }
                />
            }
        >
            <Head title="Tiket" />
            {tickets.data.length === 0 ? (
                <EmptyState
                    title="Belum ada tiket"
                    description="Punya request katalog atau laporan lain? Buat tiket untuk kirim ke admin."
                    icon={TicketIcon}
                    action={
                        <Link href={route('tickets.create')} className={cn(buttonVariants())}>
                            <Plus className="mr-1.5 h-4 w-4" />
                            Buat Tiket
                        </Link>
                    }
                />
            ) : (
                <>
                    <div className="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Subjek</TableHead>
                                    <TableHead className="w-40">Tipe</TableHead>
                                    <TableHead className="w-32">Status</TableHead>
                                    <TableHead className="w-28">Dibuat</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {tickets.data.map((t) => (
                                    <TableRow
                                        key={t.id}
                                        className="cursor-pointer"
                                        onClick={() => router.visit(route('tickets.show', t.id))}
                                    >
                                        <TableCell>
                                            <p className="font-medium">{t.subject}</p>
                                            {t.series && (
                                                <p className="text-xs text-muted-foreground">Terkait: {t.series.title_romaji}</p>
                                            )}
                                        </TableCell>
                                        <TableCell><TicketTypeBadge type={t.type} /></TableCell>
                                        <TableCell><TicketStatusBadge status={t.status} /></TableCell>
                                        <TableCell className="text-sm text-muted-foreground">{t.created_at}</TableCell>
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
