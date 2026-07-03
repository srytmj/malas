import { Head, Link } from '@inertiajs/react';
import { BookMarked } from 'lucide-react';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import EmptyState from '@/Components/app/EmptyState';
import { Pagination } from '@/Components/app/Pagination';
import { Badge } from '@/Components/ui/badge';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import { PageProps } from '@/types';
import { type PaginatedData } from '@/lib/types';

interface LoanRow {
    id: string;
    collection_id: string;
    series_title: string;
    volume_number: number | null;
    borrower_name: string;
    loaned_at: string | null;
    due_at: string | null;
    returned_at: string | null;
    notes: string | null;
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

export default function LoansIndex({ loans }: Props) {
    return (
        <UserLayout
            header={
                <PageHeader
                    title="Riwayat Pinjaman"
                    description={`${loans.total} pinjaman tercatat`}
                />
            }
        >
            <Head title="Pinjaman Saya" />
            {loans.data.length === 0 ? (
                <EmptyState
                    title="Belum ada pinjaman"
                    description="Pinjamkan volume dari halaman koleksi untuk mulai mencatat."
                    icon={BookMarked}
                />
            ) : (
                <>
                    <div className="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Series / Volume</TableHead>
                                    <TableHead>Peminjam</TableHead>
                                    <TableHead className="w-36">Status</TableHead>
                                    <TableHead className="w-28">Dipinjam</TableHead>
                                    <TableHead className="w-28">Jatuh Tempo</TableHead>
                                    <TableHead className="w-28">Dikembalikan</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {loans.data.map((l) => (
                                    <TableRow key={l.id} className={l.is_overdue ? 'bg-destructive/5' : ''}>
                                        <TableCell>
                                            <Link
                                                href={route('collection.show', l.collection_id)}
                                                className="font-medium hover:underline"
                                            >
                                                {l.series_title}
                                            </Link>
                                            <p className="text-xs text-muted-foreground">Vol. {l.volume_number ?? '-'}</p>
                                        </TableCell>
                                        <TableCell>
                                            {l.borrower_name}
                                            {l.notes && (
                                                <p className="text-xs text-muted-foreground italic truncate max-w-[160px]">{l.notes}</p>
                                            )}
                                        </TableCell>
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
                </>
            )}
        </UserLayout>
    );
}
