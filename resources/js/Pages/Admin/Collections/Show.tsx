import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { SeriesTypeBadge } from '@/Components/app/StatusBadge';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import { PageProps } from '@/types';
import { type SeriesType, type CollectionCondition } from '@/lib/types';

interface CollectionRow {
    id: string;
    series_title: string;
    series_type: SeriesType;
    owned_volumes_count: number;
    total_volumes: number | null;
    condition: CollectionCondition;
}

interface UserData {
    id: string;
    name: string;
    email: string;
    avatar: string | null;
}

interface Props extends PageProps {
    user: UserData;
    collections: CollectionRow[];
}

const CONDITION_LABELS: Record<CollectionCondition, string> = {
    mint: 'Mint',
    good: 'Bagus',
    fair: 'Cukup',
    poor: 'Buruk',
};

export default function AdminCollectionsShow({ user, collections }: Props) {
    return (
        <AdminLayout
            header={
                <PageHeader
                    title={user.name}
                    description={`${collections.length} series dalam koleksi`}
                    breadcrumbs={[
                        { label: 'Semua Koleksi', href: route('admin.collections.index') },
                        { label: user.name },
                    ]}
                />
            }
        >
            <Head title={`Koleksi ${user.name}`} />

            <div className="mb-6 flex items-center gap-3">
                <Avatar className="h-10 w-10">
                    <AvatarImage src={user.avatar || undefined} alt={user.name} />
                    <AvatarFallback>{user.name.slice(0, 2).toUpperCase()}</AvatarFallback>
                </Avatar>
                <div>
                    <p className="font-medium">{user.name}</p>
                    <p className="text-sm text-muted-foreground">{user.email}</p>
                </div>
            </div>

            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Series</TableHead>
                            <TableHead className="w-28">Tipe</TableHead>
                            <TableHead className="w-32 text-right">Volume Dimiliki</TableHead>
                            <TableHead className="w-24">Kondisi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {collections.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={4} className="py-12 text-center text-muted-foreground">
                                    Belum ada koleksi.
                                </TableCell>
                            </TableRow>
                        ) : collections.map((c) => (
                            <TableRow key={c.id}>
                                <TableCell className="font-medium">{c.series_title}</TableCell>
                                <TableCell><SeriesTypeBadge type={c.series_type} /></TableCell>
                                <TableCell className="text-right">
                                    {c.owned_volumes_count}
                                    {c.total_volumes ? `/${c.total_volumes}` : ''}
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline">{CONDITION_LABELS[c.condition]}</Badge>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </AdminLayout>
    );
}
