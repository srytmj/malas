import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { BookOpen, ExternalLink, Library, Ticket } from 'lucide-react';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import { VolumeGrid } from '@/Components/app/VolumeGrid';
import { AdultBlurOverlay } from '@/Components/app/AdultBlurOverlay';
import { SeriesStatusBadge, SeriesTypeBadge } from '@/Components/app/StatusBadge';
import { Badge } from '@/Components/ui/badge';
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
    anilist_id: number | null;
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
    genres: string[] | null;
    authors: string[] | null;
    themes: string[] | null;
    demographics: string[] | null;
    is_adult: boolean;
}

interface MediaItem {
    id: string;
    image_url: string | null;
    caption: string | null;
}

interface Props extends PageProps {
    series: SeriesData;
    volumes: VolumeRow[];
    media: MediaItem[];
    collection: { id: string } | null;
}

export default function CatalogShow({ series, volumes, media, collection }: Props) {
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
                        <div className="flex flex-wrap gap-2">
                            <Link
                                href={`${route('tickets.create')}?series_id=${series.id}`}
                                className={cn(buttonVariants({ variant: 'outline' }))}
                            >
                                <Ticket className="mr-1.5 h-4 w-4" />
                                Buat Tiket
                            </Link>
                            {collection ? (
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
                            )}
                        </div>
                    }
                />
            }
        >
            <Head title={series.title_romaji} />
            {/* Series info */}
            <div className="grid gap-6 lg:grid-cols-[auto_1fr]">
                <div className="shrink-0">
                    <AdultBlurOverlay isAdult={series.is_adult} className="h-52 w-36 overflow-hidden rounded-lg bg-muted shadow-sm">
                        {series.cover_url ? (
                            <img src={series.cover_url} alt={series.title_romaji} className="h-full w-full object-cover" />
                        ) : (
                            <div className="flex h-full w-full items-center justify-center">
                                <BookOpen className="h-10 w-10 text-muted-foreground" />
                            </div>
                        )}
                    </AdultBlurOverlay>
                </div>

                <div className="space-y-3">
                    {series.title_english && <p className="text-muted-foreground">{series.title_english}</p>}
                    {series.title_japanese && <p className="text-sm text-muted-foreground">{series.title_japanese}</p>}
                    {series.authors && series.authors.length > 0 && (
                        <p className="text-sm text-muted-foreground">
                            <span className="text-xs">Author:</span> {series.authors.join(', ')}
                        </p>
                    )}

                    <div className="flex flex-wrap gap-2">
                        <SeriesStatusBadge status={series.status} />
                        <SeriesTypeBadge type={series.type} />
                        {series.demographics?.map((d) => (
                            <Badge key={d} variant="secondary">{d}</Badge>
                        ))}
                    </div>

                    {((series.genres?.length ?? 0) > 0 || (series.themes?.length ?? 0) > 0) && (
                        <div className="flex flex-wrap gap-1.5">
                            {series.genres?.map((g) => (
                                <Badge key={g} variant="outline">{g}</Badge>
                            ))}
                            {series.themes?.map((t) => (
                                <Badge key={t} variant="outline" className="text-muted-foreground">{t}</Badge>
                            ))}
                        </div>
                    )}

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

                    {series.anilist_id && (
                        <a
                            href={`https://anilist.co/manga/${series.anilist_id}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground hover:underline"
                        >
                            Lihat di AniList
                            <ExternalLink className="h-3 w-3" />
                        </a>
                    )}
                </div>
            </div>

            {/* Galeri media */}
            {media.length > 0 && (
                <div className="mt-8">
                    <h2 className="mb-3 text-base font-semibold">Galeri</h2>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                        {media.map((m) => (
                            <div key={m.id} className="aspect-video overflow-hidden rounded-lg border bg-muted">
                                {m.image_url && (
                                    <img src={m.image_url} alt={m.caption ?? series.title_romaji} className="h-full w-full object-cover" />
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Volume list */}
            <div className="mt-8">
                <h2 className="mb-3 text-base font-semibold">Volume ({volumes.length})</h2>
                <VolumeGrid volumes={volumes} />
            </div>
        </UserLayout>
    );
}
