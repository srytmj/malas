import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
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
    const { t } = useTranslation('admin');
    if (loan.returned_at) {
        return <Badge variant="secondary" className="text-xs">{t('dashboard.loan.returned')}</Badge>;
    }
    if (loan.is_overdue) {
        return <Badge variant="destructive" className="text-xs">{t('dashboard.loan.overdue')}</Badge>;
    }
    return <Badge variant="outline" className="text-xs border-yellow-500 text-yellow-600 dark:text-yellow-400">{t('dashboard.loan.active')}</Badge>;
}

export default function AdminLoansIndex({ loans }: Props) {
    const { t } = useTranslation('admin');
    return (
        <AdminLayout
            header={
                <PageHeader
                    title={t('loans.title')}
                    description={t('loans.description', { count: loans.total })}
                />
            }
        >
            <Head title={t('loans.title')} />
            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{t('loans.table.user')}</TableHead>
                            <TableHead>{t('loans.table.seriesVolume')}</TableHead>
                            <TableHead>{t('loans.table.borrower')}</TableHead>
                            <TableHead className="w-36">{t('loans.table.status')}</TableHead>
                            <TableHead className="w-28">{t('loans.table.loanedAt')}</TableHead>
                            <TableHead className="w-28">{t('loans.table.dueAt')}</TableHead>
                            <TableHead className="w-28">{t('loans.table.returnedAt')}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {loans.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={7} className="py-12 text-center text-muted-foreground">
                                    {t('loans.empty')}
                                </TableCell>
                            </TableRow>
                        ) : loans.data.map((l) => (
                            <TableRow key={l.id} className={l.is_overdue ? 'bg-destructive/5' : ''}>
                                <TableCell className="text-sm font-medium">{l.user_name}</TableCell>
                                <TableCell>
                                    <p className="font-medium">{l.series_title}</p>
                                    <p className="text-xs text-muted-foreground">{t('loans.volumePrefix', { number: l.volume_number ?? '-' })}</p>
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
                <Pagination data={loans} routeName="admin.loans.index" />
            </div>
        </AdminLayout>
    );
}
