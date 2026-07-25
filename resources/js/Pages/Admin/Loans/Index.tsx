import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Pagination } from '@/Components/app/Pagination';
import { Badge } from '@/Components/ui/badge';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import { PageProps } from '@/types';
import { type PaginatedData } from '@/lib/types';

interface LoanRow {
    id: string;
    user_name: string;
    series_title: string;
    volume_number: number | null;
    borrower_name: string;
    loaned_at: string | null;
    due_at: string | null;
    returned_at: string | null;
    is_overdue: boolean;
}

interface Props extends PageProps {
    loans: PaginatedData<LoanRow>;
}

function LoanBadge({ loan }: { loan: LoanRow }) {
    if (loan.returned_at) {
        return <Badge variant="secondary" className="text-xs">Dikembalikan</Badge>;
    }
    if (loan.is_overdue) {
        return <Badge variant="destructive" className="text-xs">Terlambat</Badge>;
    }
    return <Badge variant="outline" className="text-xs border-yellow-500 text-yellow-600 dark:text-yellow-400">Dipinjam</Badge>;
}

export default function AdminLoansIndex({ loans }: Props) {
    return (
        <AdminLayout
            header={
                <PageHeader
                    title="Semua Pinjaman"
                    description={`${loans.total} pinjaman tercatat`}
                />
            }
        >
            <Head title="Semua Pinjaman" />
            <div className="max-h-[75vh] overflow-auto rounded-lg border">
                <Table>
                    <TableHeader className="sticky top-0 z-10 bg-card">
                        <TableRow>
                            <TableHead>User</TableHead>
                            <TableHead>Series / Volume</TableHead>
                            <TableHead>Peminjam</TableHead>
                            <TableHead className="w-36">Status</TableHead>
                            <TableHead className="w-28">Dipinjam</TableHead>
                            <TableHead className="w-28">Jatuh Tempo</TableHead>
                            <TableHead className="w-28">Dikembalikan</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {loans.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={7} className="py-12 text-center text-muted-foreground">
                                    Belum ada pinjaman.
                                </TableCell>
                            </TableRow>
                        ) : loans.data.map((l) => (
                            <TableRow key={l.id} className={l.is_overdue ? 'bg-destructive/5' : ''}>
                                <TableCell className="text-sm font-medium">{l.user_name}</TableCell>
                                <TableCell>
                                    <p className="font-medium">{l.series_title}</p>
                                    <p className="text-xs text-muted-foreground">Vol. {l.volume_number ?? '-'}</p>
                                </TableCell>
                                <TableCell>{l.borrower_name}</TableCell>
                                <TableCell><LoanBadge loan={l} /></TableCell>
                                <TableCell className="text-sm text-muted-foreground">{l.loaned_at ?? '—'}</TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {l.due_at
                                        ? <span className={l.is_overdue ? 'text-destructive font-medium' : ''}>{l.due_at}</span>
                                        : '—'}
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">{l.returned_at ?? '—'}</TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <div className="mt-4">
                <Pagination data={loans} />
            </div>
        </AdminLayout>
    );
}
