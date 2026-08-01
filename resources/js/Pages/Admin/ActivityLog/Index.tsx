import { useEffect, useMemo, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Activity, Check, ChevronsUpDown, Maximize2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import EmptyState from '@/Components/app/EmptyState';
import { Pagination } from '@/Components/app/Pagination';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import { type PaginatedData } from '@/lib/types';

interface ActivityLogRow {
    id: string;
    action: string;
    description: string;
    user_name: string;
    user_avatar: string | null;
    created_at: string;
}

interface UserOption {
    id: string;
    name: string;
}

interface Props extends PageProps {
    logs: PaginatedData<ActivityLogRow>;
    categories: string[];
    users: UserOption[];
    filters: { search: string; category: string | null; user_id: string | null };
}

const DESCRIPTION_PREVIEW_LIMIT = 140;

function actionBadgeVariant(action: string): 'default' | 'destructive' | 'secondary' | 'outline' {
    if (action.includes('error')) return 'destructive';
    if (action.includes('delete') || action.includes('remove') || action.includes('ban')) return 'destructive';
    if (action.includes('update') || action.includes('role_change') || action.includes('override')) return 'secondary';
    return 'outline';
}

function UserFilterCombobox({ users, value, onChange }: {
    users: UserOption[];
    value: string | null;
    onChange: (userId: string) => void;
}) {
    const { t } = useTranslation('admin');
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');

    const filtered = useMemo(
        () => users.filter((u) => u.name.toLowerCase().includes(query.trim().toLowerCase())),
        [users, query],
    );

    const selectedName = users.find((u) => u.id === value)?.name;

    function handleSelect(userId: string) {
        onChange(userId);
        setOpen(false);
        setQuery('');
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger
                render={
                    <Button variant="outline" className="w-44 justify-between font-normal">
                        <span className="truncate">{selectedName ?? t('activityLog.allUsers')}</span>
                        <ChevronsUpDown className="h-3.5 w-3.5 shrink-0 opacity-50" />
                    </Button>
                }
            />
            <PopoverContent className="w-56 p-1.5" align="start">
                <Input
                    placeholder={t('activityLog.typeUserName')}
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    className="mb-1.5 h-8 text-sm"
                    autoFocus
                />
                <div className="max-h-56 space-y-0.5 overflow-y-auto">
                    <button
                        type="button"
                        onClick={() => handleSelect('')}
                        className="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent"
                    >
                        <Check className={cn('h-3.5 w-3.5', !value ? 'opacity-100' : 'opacity-0')} />
                        {t('activityLog.allUsers')}
                    </button>
                    {filtered.length === 0 ? (
                        <p className="px-2 py-1.5 text-xs text-muted-foreground">{t('activityLog.noMatchingUser')}</p>
                    ) : (
                        filtered.map((u) => (
                            <button
                                key={u.id}
                                type="button"
                                onClick={() => handleSelect(u.id)}
                                className="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent"
                            >
                                <Check className={cn('h-3.5 w-3.5', value === u.id ? 'opacity-100' : 'opacity-0')} />
                                <span className="truncate">{u.name}</span>
                            </button>
                        ))
                    )}
                </div>
            </PopoverContent>
        </Popover>
    );
}

function LogRow({ log }: { log: ActivityLogRow }) {
    const { t } = useTranslation('admin');
    const [detailOpen, setDetailOpen] = useState(false);
    const isLong = log.description.length > DESCRIPTION_PREVIEW_LIMIT;

    return (
        <div className="flex items-start gap-3 rounded-lg border bg-card p-3">
            <Avatar className="h-8 w-8 shrink-0">
                <AvatarImage src={log.user_avatar || undefined} alt={log.user_name} />
                <AvatarFallback className="text-xs">{log.user_name.slice(0, 2).toUpperCase()}</AvatarFallback>
            </Avatar>
            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-sm font-medium">{log.user_name}</span>
                    <Badge variant={actionBadgeVariant(log.action)} className="text-[10px] px-1.5 py-0">
                        {log.action}
                    </Badge>
                </div>
                <p className="mt-0.5 line-clamp-2 text-sm text-muted-foreground">{log.description}</p>
                {isLong && (
                    <Dialog open={detailOpen} onOpenChange={setDetailOpen}>
                        <Button
                            variant="link"
                            size="sm"
                            className="mt-0.5 h-auto p-0 text-xs"
                            onClick={() => setDetailOpen(true)}
                        >
                            <Maximize2 className="mr-1 h-3 w-3" />
                            {t('activityLog.viewMore')}
                        </Button>
                        <DialogContent className="max-h-[85vh] w-full max-w-2xl overflow-y-auto sm:max-w-2xl">
                            <DialogHeader>
                                <DialogTitle className="flex items-center gap-2">
                                    {log.user_name}
                                    <Badge variant={actionBadgeVariant(log.action)} className="text-[10px] px-1.5 py-0">
                                        {log.action}
                                    </Badge>
                                </DialogTitle>
                                <DialogDescription>
                                    {new Date(log.created_at).toLocaleString('id-ID')}
                                </DialogDescription>
                            </DialogHeader>
                            <pre className="whitespace-pre-wrap break-words rounded-lg bg-muted p-3 font-mono text-xs text-foreground">
                                {log.description}
                            </pre>
                        </DialogContent>
                    </Dialog>
                )}
            </div>
            <span className="shrink-0 text-xs text-muted-foreground">
                {new Date(log.created_at).toLocaleString('id-ID')}
            </span>
        </div>
    );
}

export default function ActivityLogIndex({ logs, categories, users, filters }: Props) {
    const { t } = useTranslation('admin');
    const categoryLabels: Record<string, string> = {
        ai: t('activityLog.categories.ai'),
        admin: t('activityLog.categories.admin'),
        collection: t('activityLog.categories.collection'),
        database: t('activityLog.categories.database'),
        loan: t('activityLog.categories.loan'),
        profile: t('activityLog.categories.profile'),
        series: t('activityLog.categories.series'),
        site_settings: t('activityLog.categories.site_settings'),
        storage_settings: t('activityLog.categories.storage_settings'),
        ticket: t('activityLog.categories.ticket'),
        user: t('activityLog.categories.user'),
        wishlist: t('activityLog.categories.wishlist'),
    };
    const [search, setSearch] = useState(filters.search);
    const isFirstRender = useRef(true);

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }
        const t = setTimeout(() => {
            router.get(route('admin.activity-logs.index'), { ...filters, search: search || undefined }, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 400);
        return () => clearTimeout(t);
    }, [search]);

    function handleFilter(key: string, value: string) {
        router.get(route('admin.activity-logs.index'), { ...filters, search, [key]: value || undefined }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    return (
        <AdminLayout
            header={
                <PageHeader
                    title={t('activityLog.title')}
                    description={t('activityLog.description')}
                />
            }
        >
            <Head title={t('activityLog.title')} />

            <div className="mb-4 flex flex-wrap items-center gap-2">
                <Input
                    placeholder={t('activityLog.searchPlaceholder')}
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="w-64"
                />
                <Select value={filters.category ?? ''} onValueChange={(v) => handleFilter('category', v ?? '')}>
                    <SelectTrigger className="w-44">
                        <SelectValue placeholder={t('activityLog.allCategories')}>
                            {(value: string) => (categoryLabels[value] ?? value) || t('activityLog.allCategories')}
                        </SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">{t('activityLog.allCategories')}</SelectItem>
                        {categories.map((c) => (
                            <SelectItem key={c} value={c}>{categoryLabels[c] ?? c}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <UserFilterCombobox
                    users={users}
                    value={filters.user_id}
                    onChange={(userId) => handleFilter('user_id', userId)}
                />
            </div>

            {logs.data.length === 0 ? (
                <EmptyState
                    title={t('activityLog.empty.title')}
                    description={t('activityLog.empty.description')}
                    icon={Activity}
                />
            ) : (
                <>
                    <div className="space-y-2">
                        {logs.data.map((log) => (
                            <LogRow key={log.id} log={log} />
                        ))}
                    </div>

                    <div className="mt-4">
                        <Pagination data={logs} routeName="admin.activity-logs.index" filters={{ ...filters, search }} />
                    </div>
                </>
            )}
        </AdminLayout>
    );
}
