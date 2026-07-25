import { Head, Link, router } from '@inertiajs/react';
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
    return (
        <AdminLayout
            header={
                <PageHeader
                    title="Semua Koleksi"
                    description={`${users.total} pengguna memiliki koleksi`}
                />
            }
        >
            <Head title="Semua Koleksi" />
            <div className="max-h-[75vh] overflow-auto rounded-lg border">
                <Table>
                    <TableHeader className="sticky top-0 z-10 bg-card">
                        <TableRow>
                            <TableHead className="w-10" />
                            <TableHead>Pengguna</TableHead>
                            <TableHead className="w-32 text-right">Series</TableHead>
                            <TableHead className="w-32 text-right">Volume Dimiliki</TableHead>
                            <TableHead className="w-10" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {users.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={5} className="py-12 text-center text-muted-foreground">
                                    Belum ada koleksi.
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
                <Pagination data={users} />
            </div>
        </AdminLayout>
    );
}
