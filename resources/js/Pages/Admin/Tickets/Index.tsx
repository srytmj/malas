import { Head, router } from '@inertiajs/react';
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
                    title="Tiket"
                    description={`${tickets.total} tiket dari pengguna`}
                />
            }
        >
            <Head title="Tiket" />

            <div className="mb-4">
                <Select value={filters.status ?? 'all'} onValueChange={applyFilter}>
                    <SelectTrigger className="w-44">
                        <SelectValue placeholder="Semua status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua status</SelectItem>
                        <SelectItem value="open">Terbuka</SelectItem>
                        <SelectItem value="in_progress">Diproses</SelectItem>
                        <SelectItem value="resolved">Selesai</SelectItem>
                        <SelectItem value="closed">Ditutup</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            {tickets.data.length === 0 ? (
                <EmptyState
                    title="Tidak ada tiket"
                    description="Tidak ada tiket yang sesuai dengan filter."
                    icon={Search}
                />
            ) : (
                <>
                    <div className="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Subjek</TableHead>
                                    <TableHead>Pengguna</TableHead>
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
                                        onClick={() => router.visit(route('admin.tickets.show', t.id))}
                                    >
                                        <TableCell>
                                            <p className="font-medium">{t.subject}</p>
                                            {t.series && (
                                                <p className="text-xs text-muted-foreground">Terkait: {t.series.title_romaji}</p>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-sm">{t.user_name}</TableCell>
                                        <TableCell><TicketTypeBadge type={t.type} /></TableCell>
                                        <TableCell><TicketStatusBadge status={t.status} /></TableCell>
                                        <TableCell className="text-sm text-muted-foreground">{t.created_at}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                    <div className="mt-4"><Pagination data={tickets} /></div>
                </>
            )}
        </AdminLayout>
    );
}
