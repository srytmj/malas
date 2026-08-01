import { FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Eye, Search } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import EmptyState from '@/Components/app/EmptyState';
import { Pagination } from '@/Components/app/Pagination';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
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
    avatar: string | null;
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

const ROLE_VARIANTS: Record<string, 'default' | 'secondary' | 'outline'> = {
    super_admin: 'default',
    admin:       'secondary',
    user:        'outline',
};

export default function UsersIndex({ users, filters }: Props) {
    const { t } = useTranslation('admin');
    const [search, setSearch] = useState(filters.search ?? '');

    const ROLE_LABELS: Record<string, string> = {
        super_admin: t('common:settings.roles.super_admin'),
        admin:       t('common:settings.roles.admin'),
        user:        t('common:settings.roles.user'),
    };

    const STATUS_FILTER_LABELS: Record<string, string> = {
        all: t('users.allStatuses'),
        active: t('users.statusActive'),
        banned: t('users.statusBanned'),
    };

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
                    title={t('users.title')}
                    description={t('users.description')}
                />
            }
        >
            <Head title={t('users.title')} />
            {/* Filters */}
            <div className="mb-4 flex flex-wrap gap-2">
                <form onSubmit={handleSearch} className="flex gap-2">
                    <div className="relative">
                        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            placeholder={t('users.searchPlaceholder')}
                            className="pl-8 w-64"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </div>
                    <Button type="submit" variant="outline" size="sm">{t('users.search')}</Button>
                </form>

                <Select
                    value={filters.role ?? 'all'}
                    onValueChange={(v) => applyFilter({ role: v === 'all' || !v ? '' : v })}
                >
                    <SelectTrigger className="w-36">
                        <SelectValue placeholder={t('users.allRoles')}>
                            {(value: string) => (value === 'all' ? t('users.allRoles') : ROLE_LABELS[value] ?? value)}
                        </SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">{t('users.allRoles')}</SelectItem>
                        <SelectItem value="super_admin">{ROLE_LABELS.super_admin}</SelectItem>
                        <SelectItem value="admin">{ROLE_LABELS.admin}</SelectItem>
                        <SelectItem value="user">{ROLE_LABELS.user}</SelectItem>
                    </SelectContent>
                </Select>

                <Select
                    value={filters.status ?? 'all'}
                    onValueChange={(v) => applyFilter({ status: v === 'all' || !v ? '' : v })}
                >
                    <SelectTrigger className="w-36">
                        <SelectValue placeholder={t('users.allStatuses')}>
                            {(value: string) => STATUS_FILTER_LABELS[value] ?? t('users.allStatuses')}
                        </SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">{t('users.allStatuses')}</SelectItem>
                        <SelectItem value="active">{t('users.statusActive')}</SelectItem>
                        <SelectItem value="banned">{t('users.statusBanned')}</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            {users.data.length === 0 ? (
                <EmptyState
                    title={t('users.emptyTitle')}
                    description={t('users.emptyDescription')}
                    icon={Search}
                />
            ) : (
                <>
                    <div className="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-10" />
                                    <TableHead>{t('users.table.name')}</TableHead>
                                    <TableHead>{t('users.table.email')}</TableHead>
                                    <TableHead className="w-28">{t('users.table.role')}</TableHead>
                                    <TableHead className="w-28">{t('users.table.status')}</TableHead>
                                    <TableHead className="w-32">{t('users.table.joined')}</TableHead>
                                    <TableHead className="w-16" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.data.map((u) => (
                                    <TableRow key={u.id}>
                                        <TableCell>
                                            <Avatar className="h-8 w-8">
                                                <AvatarImage src={u.avatar || undefined} alt={u.name} />
                                                <AvatarFallback className="text-xs">{u.name.slice(0, 2).toUpperCase()}</AvatarFallback>
                                            </Avatar>
                                        </TableCell>
                                        <TableCell className="font-medium">{u.name}</TableCell>
                                        <TableCell className="text-sm text-muted-foreground">{u.email}</TableCell>
                                        <TableCell>
                                            <Badge variant={ROLE_VARIANTS[u.role] ?? 'outline'}>
                                                {ROLE_LABELS[u.role] ?? u.role}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {u.is_banned
                                                ? <Badge variant="destructive">{t('users.statusBanned')}</Badge>
                                                : <Badge variant="outline">{t('users.statusActive')}</Badge>}
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

                    <div className="mt-4">
                        <Pagination
                            data={users}
                            routeName="admin.users.index"
                            filters={{ search, role: filters.role, status: filters.status }}
                        />
                    </div>
                </>
            )}
        </AdminLayout>
    );
}
