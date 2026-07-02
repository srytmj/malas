import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { BookOpen, Library } from 'lucide-react';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import { VolumeGrid } from '@/Components/app/VolumeGrid';
import { SeriesStatusBadge, SeriesTypeBadge } from '@/Components/app/StatusBadge';
import { Button, buttonVariants } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import { type SeriesStatus, type SeriesType, type VolumeType } from '@/lib/types';

interface VolumeRow {
    id: string;
    volume_number: number;
    type: VolumeType;
    isbn: string | null;
    published_at: string | null;
    cover_url: string | null;
}

interface SeriesData {
    id: string;
    title_romaji: string;
    title_english: string | null;
    title_japanese: string | null;
    synopsis: string | null;
    status: SeriesStatus;
    type: SeriesType;
    published_from: string | null;
    published_to: string | null;
    total_volumes: number | null;
    score: number | null;
    rank: number | null;
    cover_url: string | null;
}

interface Props extends PageProps {
    series: SeriesData;
    volumes: VolumeRow[];
    collection: { id: string } | null;
}

export default function CatalogShow({ series, volumes, collection }: Props) {
    const [adding, setAdding] = useState(false);

    function handleAddToCollection() {
        setAdding(true);
        router.post(
            route('collection.store'),
            { series_ids: [series.id] },
            {
                onFinish: () => setAdding(false),
            },
        );
    }

    return (
        <UserLayout
            header={
                <PageHeader
                    title={series.title_romaji}
                    breadcrumbs={[
                        { label: 'Katalog', href: route('catalog.index') },
                        { label: series.title_romaji },
                    ]}
                    actions={
                        collection ? (
                            <Link
                                href={route('collection.show', collection.id)}
                                className={cn(buttonVariants({ variant: 'outline' }))}
                            >
                                <Library className="mr-1.5 h-4 w-4" />
                                Lihat di Koleksi
                            </Link>
                        ) : (
                            <Button onClick={handleAddToCollection} disabled={adding}>
                                <Library className="mr-1.5 h-4 w-4" />
                                {adding ? 'Menambahkan...' : 'Tambah ke Koleksi'}
                            </Button>
                        )
                    }
                />
            }
        >
            {/* Series info */}
            <div className="grid gap-6 lg:grid-cols-[auto_1fr]">
                <div className="shrink-0">
                    {series.cover_url ? (
                        <img src={series.cover_url} alt={series.title_romaji} className="w-36 rounded-lg object-cover shadow-sm" />
                    ) : (
                        <div className="flex h-52 w-36 items-center justify-center rounded-lg bg-muted">
                            <BookOpen className="h-10 w-10 text-muted-foreground" />
                        </div>
                    )}
                </div>

                <div className="space-y-3">
                    {series.title_english && <p className="text-muted-foreground">{series.title_english}</p>}
                    {series.title_japanese && <p className="text-sm text-muted-foreground">{series.title_japanese}</p>}

                    <div className="flex flex-wrap gap-2">
                        <SeriesStatusBadge status={series.status} />
                        <SeriesTypeBadge type={series.type} />
                    </div>

                    <div className="grid grid-cols-2 gap-x-8 gap-y-1 text-sm sm:grid-cols-4">
                        <div>
                            <p className="text-xs text-muted-foreground">Total Volume</p>
                            <p className="font-medium">{series.total_volumes ?? '—'}</p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">Skor</p>
                            <p className="font-medium">
                                {series.score !== null ? Number(series.score).toFixed(2) : '—'}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">Rank</p>
                            <p className="font-medium">{series.rank ?? '—'}</p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">Terbit</p>
                            <p className="font-medium">
                                {series.published_from ?? '—'}
                                {series.published_to ? ` – ${series.published_to}` : ''}
                            </p>
                        </div>
                    </div>

                    {series.synopsis && (
                        <p className="text-sm text-muted-foreground leading-relaxed">{series.synopsis}</p>
                    )}
                </div>
            </div>

            {/* Volume list */}
            <div className="mt-8">
                <h2 className="mb-3 text-base font-semibold">Volume ({volumes.length})</h2>
                <VolumeGrid volumes={volumes} />
            </div>
        </UserLayout>
    );
}
