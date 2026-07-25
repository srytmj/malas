import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
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

const TYPE_LABELS: Record<string, string> = {
    info:    'Info',
    warning: 'Peringatan',
    danger:  'Bahaya',
    success: 'Sukses',
};

const TYPE_VARIANTS: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    info:    'secondary',
    warning: 'outline',
    danger:  'destructive',
    success: 'default',
};

export default function AnnouncementsIndex({ items }: Props) {
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
                    title="Pengumuman"
                    description="Kelola pengumuman yang tampil untuk semua pengguna."
                    actions={
                        <Link
                            href={route('admin.announcements.create')}
                            className={cn(buttonVariants({ size: 'sm' }))}
                        >
                            <Plus className="mr-1.5 h-4 w-4" />
                            Buat Pengumuman
                        </Link>
                    }
                />
            }
        >
            <Head title="Pengumuman" />
            {items.data.length === 0 ? (
                <EmptyState
                    title="Belum ada pengumuman"
                    description="Buat pengumuman pertama untuk ditampilkan ke pengguna."
                    icon={Plus}
                    action={
                        <Link href={route('admin.announcements.create')} className={cn(buttonVariants({ size: 'sm' }))}>
                            Buat Pengumuman
                        </Link>
                    }
                />
            ) : (
                <>
                    <div className="max-h-[75vh] overflow-auto rounded-lg border">
                        <Table>
                            <TableHeader className="sticky top-0 z-10 bg-card">
                                <TableRow>
                                    <TableHead>Judul</TableHead>
                                    <TableHead className="w-28">Tipe</TableHead>
                                    <TableHead className="w-24">Status</TableHead>
                                    <TableHead className="w-40">Mulai</TableHead>
                                    <TableHead className="w-40">Berakhir</TableHead>
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
                                                {a.is_active ? 'Aktif' : 'Nonaktif'}
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

                    <div className="mt-4"><Pagination data={items} /></div>
                </>
            )}

            <Dialog open={!!deleteTarget} onOpenChange={(open) => !open && setDeleteTarget(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus Pengumuman</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Yakin ingin menghapus <strong>"{deleteTarget?.title}"</strong>? Aksi ini tidak dapat dibatalkan.
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteTarget(null)}>Batal</Button>
                        <Button variant="destructive" disabled={deleting} onClick={handleDelete}>
                            {deleting ? 'Menghapus...' : 'Hapus'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
