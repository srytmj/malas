import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { BookMarked } from 'lucide-react';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle, EmptyDescription } from '@/Components/ui/empty';
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
    const { t } = useTranslation('user');
    if (loan.returned_at) {
        return <Badge variant="secondary" className="text-xs">{t('loans.statusReturned')}</Badge>;
    }
    if (loan.is_overdue) {
        return <Badge variant="destructive" className="text-xs">{t('loans.statusOverdue')}</Badge>;
    }
    return <Badge variant="outline" className="text-xs border-yellow-500 text-yellow-600 dark:text-yellow-400">{t('loans.statusOnLoan')}</Badge>;
}

export default function LoansIndex({ loans }: Props) {
    const { t } = useTranslation('user');
    return (
        <UserLayout
            header={
                <PageHeader
                    title={t('loans.title')}
                    description={t('loans.count', { count: loans.total })}
                />
            }
        >
            <Head title={t('loans.title')} />
            {loans.data.length === 0 ? (
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <BookMarked />
                        </EmptyMedia>
                        <EmptyTitle>{t('loans.emptyTitle')}</EmptyTitle>
                        <EmptyDescription>
                            {t('loans.emptyDescription')}
                        </EmptyDescription>
                    </EmptyHeader>
                </Empty>
            ) : (
                <>
                    <div className="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{t('loans.seriesVolume')}</TableHead>
                                    <TableHead>{t('loans.borrower')}</TableHead>
                                    <TableHead className="w-36">{t('loans.status')}</TableHead>
                                    <TableHead className="w-28">{t('loans.loanedAt')}</TableHead>
                                    <TableHead className="w-28">{t('loans.dueAt')}</TableHead>
                                    <TableHead className="w-28">{t('loans.returnedAt')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {loans.data.map((loan) => (
                                    <TableRow key={loan.id} className={loan.is_overdue ? 'bg-destructive/5' : ''}>
                                        <TableCell>
                                            <Link
                                                href={route('collection.show', loan.collection_id)}
                                                className="font-medium hover:underline"
                                            >
                                                {loan.series_title}
                                            </Link>
                                            <p className="text-xs text-muted-foreground">{t('loans.volumeShort', { number: loan.volume_number ?? '-' })}</p>
                                        </TableCell>
                                        <TableCell>
                                            {loan.borrower_name}
                                            {loan.notes && (
                                                <p className="text-xs text-muted-foreground italic truncate max-w-[160px]">{loan.notes}</p>
                                            )}
                                        </TableCell>
                                        <TableCell><LoanBadge loan={loan} /></TableCell>
                                        <TableCell className="text-sm text-muted-foreground">{loan.loaned_at ?? '—'}</TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {loan.due_at
                                                ? <span className={loan.is_overdue ? 'text-destructive font-medium' : ''}>{loan.due_at}</span>
                                                : '—'}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">{loan.returned_at ?? '—'}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                    <div className="mt-4">
                        <Pagination data={loans} routeName="loans.index" />
                    </div>
                </>
            )}
        </UserLayout>
    );
}
