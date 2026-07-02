import { FormEvent, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Eye, Search } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import EmptyState from '@/Components/app/EmptyState';
import { Pagination } from '@/Components/app/Pagination';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import { cn } from '@/lib/utils';
import { type PaginatedData } from '@/lib/types';
import { PageProps } from '@/types';

interface UserRow {
    id: string;
    name: string;
    email: string;
    role: 'super_admin' | 'admin' | 'user';
    is_banned: boolean;
    created_at: string;
}

interface Filters {
    search: string;
    role: string | null;
    status: string | null;
}

interface Props extends PageProps {
    users: PaginatedData<UserRow>;
    filters: Filters;
}

const ROLE_LABELS: Record<string, string> = {
    super_admin: 'Super Admin',
    admin:       'Admin',
    user:        'User',
};

const ROLE_VARIANTS: Record<string, 'default' | 'secondary' | 'outline'> = {
    super_admin: 'default',
    admin:       'secondary',
    user:        'outline',
};

export default function UsersIndex({ users, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    function applyFilter(overrides: Partial<Filters>) {
        router.get(route('admin.users.index'), {
            search: search,
            role:   filters.role ?? '',
            status: filters.status ?? '',
            ...overrides,
        }, { preserveState: true, replace: true });
    }

    function handleSearch(e: FormEvent) {
        e.preventDefault();
        applyFilter({ search });
    }

    return (
        <AdminLayout
            header={
                <PageHeader
                    title="Pengguna"
                    description="Kelola akun pengguna dan akses mereka."
                />
            }
        >
            {/* Filters */}
            <div className="mb-4 flex flex-wrap gap-2">
                <form onSubmit={handleSearch} className="flex gap-2">
                    <div className="relative">
                        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            placeholder="Cari nama atau email..."
                            className="pl-8 w-64"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </div>
                    <Button type="submit" variant="outline" size="sm">Cari</Button>
                </form>

                <Select
                    value={filters.role ?? 'all'}
                    onValueChange={(v) => applyFilter({ role: v === 'all' || !v ? '' : v })}
                >
                    <SelectTrigger className="w-36">
                        <SelectValue placeholder="Semua role" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua role</SelectItem>
                        <SelectItem value="super_admin">Super Admin</SelectItem>
                        <SelectItem value="admin">Admin</SelectItem>
                        <SelectItem value="user">User</SelectItem>
                    </SelectContent>
                </Select>

                <Select
                    value={filters.status ?? 'all'}
                    onValueChange={(v) => applyFilter({ status: v === 'all' || !v ? '' : v })}
                >
                    <SelectTrigger className="w-36">
                        <SelectValue placeholder="Semua status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua status</SelectItem>
                        <SelectItem value="active">Aktif</SelectItem>
                        <SelectItem value="banned">Di-ban</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            {users.data.length === 0 ? (
                <EmptyState
                    title="Tidak ada pengguna"
                    description="Tidak ada pengguna yang sesuai dengan filter."
                    icon={Search}
                />
            ) : (
                <>
                    <div className="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead className="w-28">Role</TableHead>
                                    <TableHead className="w-28">Status</TableHead>
                                    <TableHead className="w-32">Bergabung</TableHead>
                                    <TableHead className="w-16" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.data.map((u) => (
                                    <TableRow key={u.id}>
                                        <TableCell className="font-medium">{u.name}</TableCell>
                                        <TableCell className="text-sm text-muted-foreground">{u.email}</TableCell>
                                        <TableCell>
                                            <Badge variant={ROLE_VARIANTS[u.role] ?? 'outline'}>
                                                {ROLE_LABELS[u.role] ?? u.role}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {u.is_banned
                                                ? <Badge variant="destructive">Di-ban</Badge>
                                                : <Badge variant="outline">Aktif</Badge>}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">{u.created_at}</TableCell>
                                        <TableCell>
                                            <Link
                                                href={route('admin.users.show', u.id)}
                                                className={cn(buttonVariants({ variant: 'ghost', size: 'icon' }), 'h-8 w-8')}
                                            >
                                                <Eye className="h-3.5 w-3.5" />
                                            </Link>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>

                    <div className="mt-4"><Pagination data={users} /></div>
                </>
            )}
        </AdminLayout>
    );
}
