import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { ChevronRight } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Pagination } from '@/Components/app/Pagination';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import { PageProps } from '@/types';
import { type PaginatedData } from '@/lib/types';

interface UserCollectionRow {
    id: string;
    name: string;
    email: string;
    avatar: string | null;
    collections_count: number;
    owned_volumes_count: number;
}

interface Props extends PageProps {
    users: PaginatedData<UserCollectionRow>;
}

export default function AdminCollectionsIndex({ users }: Props) {
    const { t } = useTranslation('admin');
    return (
        <AdminLayout
            header={
                <PageHeader
                    title={t('collections.title')}
                    description={t('collections.description', { count: users.total })}
                />
            }
        >
            <Head title={t('collections.title')} />
            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-10" />
                            <TableHead>{t('collections.table.user')}</TableHead>
                            <TableHead className="w-32 text-right">{t('collections.table.series')}</TableHead>
                            <TableHead className="w-32 text-right">{t('collections.table.ownedVolumes')}</TableHead>
                            <TableHead className="w-10" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {users.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={5} className="py-12 text-center text-muted-foreground">
                                    {t('collections.empty')}
                                </TableCell>
                            </TableRow>
                        ) : users.data.map((u) => (
                            <TableRow
                                key={u.id}
                                className="cursor-pointer"
                                onClick={() => router.visit(route('admin.collections.show', u.id))}
                            >
                                <TableCell>
                                    <Avatar className="h-8 w-8">
                                        <AvatarImage src={u.avatar || undefined} alt={u.name} />
                                        <AvatarFallback className="text-xs">{u.name.slice(0, 2).toUpperCase()}</AvatarFallback>
                                    </Avatar>
                                </TableCell>
                                <TableCell>
                                    <p className="font-medium">{u.name}</p>
                                    <p className="text-xs text-muted-foreground">{u.email}</p>
                                </TableCell>
                                <TableCell className="text-right">{u.collections_count}</TableCell>
                                <TableCell className="text-right">{u.owned_volumes_count}</TableCell>
                                <TableCell>
                                    <Link href={route('admin.collections.show', u.id)}>
                                        <ChevronRight className="h-4 w-4 text-muted-foreground" />
                                    </Link>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <div className="mt-4">
                <Pagination data={users} routeName="admin.collections.index" />
            </div>
        </AdminLayout>
    );
}
