import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Plus, Pencil, Trash2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import EmptyState from '@/Components/app/EmptyState';
import { Pagination } from '@/Components/app/Pagination';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import {
    Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { cn } from '@/lib/utils';
import { type PaginatedData } from '@/lib/types';
import { PageProps } from '@/types';

interface AnnouncementRow {
    id: string;
    title: string;
    type: 'info' | 'warning' | 'danger' | 'success';
    is_active: boolean;
    starts_at: string | null;
    expires_at: string | null;
    created_at: string;
}

interface Props extends PageProps {
    items: PaginatedData<AnnouncementRow>;
}

const TYPE_VARIANTS: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    info:    'secondary',
    warning: 'outline',
    danger:  'destructive',
    success: 'default',
};

export default function AnnouncementsIndex({ items }: Props) {
    const { t } = useTranslation('admin');

    const TYPE_LABELS: Record<string, string> = {
        info:    t('announcements.types.info'),
        warning: t('announcements.types.warning'),
        danger:  t('announcements.types.danger'),
        success: t('announcements.types.success'),
    };

    const [deleteTarget, setDeleteTarget] = useState<AnnouncementRow | null>(null);
    const [deleting, setDeleting]         = useState(false);

    function handleDelete() {
        if (!deleteTarget) return;
        setDeleting(true);
        router.delete(route('admin.announcements.destroy', deleteTarget.id), {
            onFinish: () => { setDeleting(false); setDeleteTarget(null); },
        });
    }

    return (
        <AdminLayout
            header={
                <PageHeader
                    title={t('announcements.title')}
                    description={t('announcements.description')}
                    actions={
                        <Link
                            href={route('admin.announcements.create')}
                            className={cn(buttonVariants({ size: 'sm' }))}
                        >
                            <Plus className="mr-1.5 h-4 w-4" />
                            {t('announcements.create')}
                        </Link>
                    }
                />
            }
        >
            <Head title={t('announcements.title')} />
            {items.data.length === 0 ? (
                <EmptyState
                    title={t('announcements.emptyTitle')}
                    description={t('announcements.emptyDescription')}
                    icon={Plus}
                    action={
                        <Link href={route('admin.announcements.create')} className={cn(buttonVariants({ size: 'sm' }))}>
                            {t('announcements.create')}
                        </Link>
                    }
                />
            ) : (
                <>
                    <div className="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{t('announcements.table.title')}</TableHead>
                                    <TableHead className="w-28">{t('announcements.table.type')}</TableHead>
                                    <TableHead className="w-24">{t('announcements.table.status')}</TableHead>
                                    <TableHead className="w-40">{t('announcements.table.starts')}</TableHead>
                                    <TableHead className="w-40">{t('announcements.table.expires')}</TableHead>
                                    <TableHead className="w-20" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.data.map((a) => (
                                    <TableRow key={a.id}>
                                        <TableCell className="font-medium">{a.title}</TableCell>
                                        <TableCell>
                                            <Badge variant={TYPE_VARIANTS[a.type] ?? 'secondary'}>
                                                {TYPE_LABELS[a.type] ?? a.type}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={a.is_active ? 'default' : 'outline'}>
                                                {a.is_active ? t('announcements.active') : t('announcements.inactive')}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {a.starts_at ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {a.expires_at ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8"
                                                    onClick={() => router.visit(route('admin.announcements.edit', a.id))}
                                                >
                                                    <Pencil className="h-3.5 w-3.5" />
                                                </Button>
                                                <div className="mx-1 h-5 w-px bg-border" />
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-destructive hover:text-destructive"
                                                    onClick={() => setDeleteTarget(a)}
                                                >
                                                    <Trash2 className="h-3.5 w-3.5" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>

                    <div className="mt-4"><Pagination data={items} routeName="admin.announcements.index" /></div>
                </>
            )}

            <Dialog open={!!deleteTarget} onOpenChange={(open) => !open && setDeleteTarget(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('announcements.deleteTitle')}</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        {t('announcements.deleteConfirmPrefix')} <strong>&quot;{deleteTarget?.title}&quot;</strong>{t('announcements.deleteConfirmSuffix')}
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteTarget(null)}>{t('common:common.cancel')}</Button>
                        <Button variant="destructive" disabled={deleting} onClick={handleDelete}>
                            {deleting ? t('common:common.deleting') : t('common:common.delete')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
