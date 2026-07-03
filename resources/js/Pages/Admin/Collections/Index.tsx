import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Pagination } from '@/Components/app/Pagination';
import { SeriesTypeBadge } from '@/Components/app/StatusBadge';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import { PageProps } from '@/types';
import { type PaginatedData, type SeriesType } from '@/lib/types';

interface CollectionRow {
    id: string;
    user_name: string;
    user_email: string;
    series_title: string;
    series_type: SeriesType;
    owned_volumes_count: number;
    total_volumes: number | null;
    acquired_at: string | null;
}

interface Props extends PageProps {
    collections: PaginatedData<CollectionRow>;
}

export default function AdminCollectionsIndex({ collections }: Props) {
    return (
        <AdminLayout
            header={
                <PageHeader
                    title="Semua Koleksi"
                    description={`${collections.total} koleksi terdaftar`}
                />
            }
        >
            <Head title="Semua Koleksi" />
            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>User</TableHead>
                            <TableHead>Series</TableHead>
                            <TableHead className="w-28">Tipe</TableHead>
                            <TableHead className="w-32 text-right">Volume Dimiliki</TableHead>
                            <TableHead className="w-32">Tanggal</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {collections.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={5} className="py-12 text-center text-muted-foreground">
                                    Belum ada koleksi.
                                </TableCell>
                            </TableRow>
                        ) : collections.data.map((c) => (
                            <TableRow key={c.id}>
                                <TableCell>
                                    <p className="font-medium">{c.user_name}</p>
                                    <p className="text-xs text-muted-foreground">{c.user_email}</p>
                                </TableCell>
                                <TableCell className="font-medium">{c.series_title}</TableCell>
                                <TableCell><SeriesTypeBadge type={c.series_type} /></TableCell>
                                <TableCell className="text-right">
                                    {c.owned_volumes_count}
                                    {c.total_volumes ? `/${c.total_volumes}` : ''}
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {c.acquired_at ?? '—'}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <div className="mt-4">
                <Pagination data={collections} />
            </div>
        </AdminLayout>
    );
}
