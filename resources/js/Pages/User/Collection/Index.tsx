import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { BookOpen, Library, Trash2 } from 'lucide-react';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import EmptyState from '@/Components/app/EmptyState';
import { SeriesStatusBadge } from '@/Components/app/StatusBadge';
import { Button, buttonVariants } from '@/Components/ui/button';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import { type SeriesStatus, type SeriesType } from '@/lib/types';

interface CollectionRow {
    id: string;
    series_id: string;
    title_romaji: string;
    title_english: string | null;
    cover_url: string | null;
    total_volumes: number | null;
    volumes_count: number;
    owned_volumes_count: number;
    status: SeriesStatus;
    type: SeriesType;
    acquired_at: string | null;
    notes: string | null;
}

interface Props extends PageProps {
    collections: CollectionRow[];
}

export default function CollectionIndex({ collections }: Props) {
    const [deleteTarget, setDeleteTarget] = useState<CollectionRow | null>(null);
    const [deleting, setDeleting] = useState(false);

    function handleDelete() {
        if (!deleteTarget) return;
        setDeleting(true);
        router.delete(route('collection.destroy', deleteTarget.id), {
            onFinish: () => { setDeleting(false); setDeleteTarget(null); },
        });
    }

    return (
        <UserLayout
            header={
                <PageHeader
                    title="Koleksiku"
                    description={`${collections.length} series dalam koleksi`}
                />
            }
        >
            {collections.length === 0 ? (
                <EmptyState
                    title="Koleksi masih kosong"
                    description="Tambahkan series dari katalog untuk mulai melacak koleksimu."
                    icon={Library}
                    action={
                        <Link href={route('catalog.index')} className={buttonVariants()}>
                            Jelajahi Katalog
                        </Link>
                    }
                />
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {collections.map((c) => (
                        <div key={c.id} className="flex overflow-hidden rounded-lg border bg-card transition-shadow hover:shadow-sm">
                            {/* Cover */}
                            <div className="w-20 shrink-0 overflow-hidden bg-muted">
                                {c.cover_url ? (
                                    <img src={c.cover_url} alt={c.title_romaji} className="h-full w-full object-cover" />
                                ) : (
                                    <div className="flex h-full items-center justify-center">
                                        <BookOpen className="h-6 w-6 text-muted-foreground/40" />
                                    </div>
                                )}
                            </div>

                            {/* Info */}
                            <div className="flex flex-1 flex-col justify-between p-3 min-w-0">
                                <div>
                                    <Link
                                        href={route('collection.show', c.id)}
                                        className="line-clamp-2 text-sm font-medium hover:underline"
                                    >
                                        {c.title_romaji}
                                    </Link>
                                    {c.title_english && (
                                        <p className="line-clamp-1 text-xs text-muted-foreground">{c.title_english}</p>
                                    )}
                                    <div className="mt-1.5">
                                        <SeriesStatusBadge status={c.status} />
                                    </div>
                                </div>

                                <div className="flex items-center justify-between mt-2">
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Dimiliki: <span className="font-medium text-foreground">{c.owned_volumes_count}</span>
                                            {c.total_volumes ? `/${c.total_volumes}` : ''}
                                        </p>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-7 w-7 text-destructive/60 hover:text-destructive"
                                        onClick={() => setDeleteTarget(c)}
                                    >
                                        <Trash2 className="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Delete dialog */}
            <Dialog open={!!deleteTarget} onOpenChange={(open) => !open && setDeleteTarget(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus dari Koleksi</DialogTitle>
                        <DialogDescription>
                            Yakin ingin menghapus <strong>{deleteTarget?.title_romaji}</strong> dari koleksimu?
                            Data volume yang dimiliki juga akan hilang.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteTarget(null)}>Batal</Button>
                        <Button variant="destructive" disabled={deleting} onClick={handleDelete}>
                            {deleting ? 'Menghapus...' : 'Hapus'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </UserLayout>
    );
}
