import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Search, BookOpen, Download, RefreshCw, CheckCircle, X } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { SeriesStatusBadge, SeriesTypeBadge } from '@/Components/app/StatusBadge';
import { PageProps } from '@/types';
import { type SeriesStatus, type SeriesType } from '@/lib/types';

interface AniListResult {
    anilist_id: number;
    title: string;
    title_english: string | null;
    cover_url: string | null;
    type: SeriesType;
    status: SeriesStatus;
    volumes: number | null;
    score: number | null;
    synopsis: string | null;
    published_from: string | null;
    already_imported: boolean;
}

interface Props extends PageProps {
    results: AniListResult[];
    pagination: {
        lastPage?: number;
        hasNextPage?: boolean;
        currentPage?: number;
    };
    filters: { q: string };
    error: string | null;
}

export default function AniListIndex({ results, pagination, filters, error }: Props) {
    const [search, setSearch]       = useState(filters.q);
    const [preview, setPreview]     = useState<AniListResult | null>(null);
    const [importing, setImporting] = useState(false);
    const debounceRef               = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            if (search.trim()) {
                router.get(
                    route('admin.anilist.index'),
                    { q: search.trim() },
                    { preserveState: true, replace: true },
                );
            }
        }, 500);
        return () => { if (debounceRef.current) clearTimeout(debounceRef.current); };
    }, [search]);

    function handleImport(item: AniListResult) {
        setImporting(true);
        router.post(
            route('admin.anilist.import'),
            {
                anilist_id:     item.anilist_id,
                title:          item.title,
                title_english:  item.title_english,
                cover_url:      item.cover_url,
                type:           item.type,
                status:         item.status,
                volumes:        item.volumes,
                score:          item.score,
                synopsis:       item.synopsis,
                published_from: item.published_from,
            },
            {
                onFinish: () => { setImporting(false); setPreview(null); },
            },
        );
    }

    function goToPage(page: number) {
        router.get(
            route('admin.anilist.index'),
            { q: search.trim(), page },
            { preserveState: true, replace: true },
        );
    }

    const currentPage = pagination.currentPage ?? 1;

    return (
        <AdminLayout
            header={
                <PageHeader
                    title="Import dari AniList"
                    description="Cari dan import data manga/manhwa/manhua dari AniList"
                />
            }
        >
            <Head title="Cari Manga (AniList)" />
            {/* Search */}
            <div className="relative mb-6 max-w-lg">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    className="pl-9"
                    placeholder="Cari judul manga, manhwa, novel..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                />
            </div>

            {/* Error state */}
            {error && (
                <div className="mb-6 flex items-start gap-3 rounded-lg border border-destructive/50 bg-destructive/10 p-4 text-sm text-destructive">
                    <span className="shrink-0 font-semibold">Error:</span>
                    <span>{error}</span>
                    <button
                        type="button"
                        className="ml-auto flex items-center gap-1 hover:underline"
                        onClick={() => router.get(route('admin.anilist.index'), { q: search }, { preserveState: true })}
                    >
                        <RefreshCw className="h-3 w-3" /> Coba lagi
                    </button>
                </div>
            )}

            {/* Empty / prompt state */}
            {!error && results.length === 0 && (
                <div className="flex flex-col items-center justify-center py-24 text-muted-foreground">
                    <BookOpen className="mb-4 h-12 w-12 opacity-30" />
                    <p className="text-sm">{filters.q ? 'Tidak ada hasil ditemukan.' : 'Ketik judul untuk memulai pencarian.'}</p>
                </div>
            )}

            {/* Results grid */}
            {results.length > 0 && (
                <>
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                        {results.map((item) => (
                            <div key={item.anilist_id} className="relative">
                                {/* Card */}
                                <button
                                    type="button"
                                    className="group flex w-full flex-col overflow-hidden rounded-lg border bg-card text-left transition-shadow hover:shadow-md focus:outline-none focus:ring-2 focus:ring-ring"
                                    onClick={() => setPreview(preview?.anilist_id === item.anilist_id ? null : item)}
                                >
                                    {/* Cover */}
                                    <div className="relative aspect-[2/3] overflow-hidden bg-muted">
                                        {item.cover_url ? (
                                            <img
                                                src={item.cover_url}
                                                alt={item.title}
                                                className="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                                            />
                                        ) : (
                                            <div className="flex h-full items-center justify-center">
                                                <BookOpen className="h-8 w-8 text-muted-foreground/30" />
                                            </div>
                                        )}
                                        {item.already_imported && (
                                            <div className="absolute inset-0 flex items-end bg-gradient-to-t from-black/60 to-transparent p-2">
                                                <span className="flex items-center gap-1 text-xs font-medium text-white">
                                                    <CheckCircle className="h-3.5 w-3.5 text-green-400" />
                                                    Sudah diimpor
                                                </span>
                                            </div>
                                        )}
                                        {item.score && (
                                            <div className="absolute top-2 right-2 rounded bg-black/60 px-1.5 py-0.5 text-xs font-medium text-white">
                                                ★ {item.score}
                                            </div>
                                        )}
                                    </div>

                                    {/* Info */}
                                    <div className="flex flex-col gap-1 p-3">
                                        <p className="line-clamp-2 text-sm font-medium leading-tight">{item.title}</p>
                                        <div className="flex flex-wrap gap-1">
                                            <SeriesTypeBadge type={item.type} />
                                        </div>
                                        {item.volumes && (
                                            <p className="text-xs text-muted-foreground">{item.volumes} vol</p>
                                        )}
                                    </div>
                                </button>

                                {/* Overlay detail — muncul tepat di atas card */}
                                {preview?.anilist_id === item.anilist_id && (
                                    <div className="absolute inset-0 z-20 flex flex-col justify-between overflow-hidden rounded-lg border border-border bg-card shadow-xl">
                                        <div className="flex items-start justify-between gap-2 border-b p-3">
                                            <div className="min-w-0">
                                                <p className="text-sm font-semibold leading-tight line-clamp-2">{item.title}</p>
                                                {item.title_english && (
                                                    <p className="mt-0.5 text-xs text-muted-foreground line-clamp-1">{item.title_english}</p>
                                                )}
                                            </div>
                                            <button
                                                type="button"
                                                className="shrink-0 rounded p-0.5 hover:bg-muted"
                                                onClick={() => setPreview(null)}
                                            >
                                                <X className="h-3.5 w-3.5 text-muted-foreground" />
                                            </button>
                                        </div>

                                        <div className="flex-1 overflow-y-auto p-3 space-y-2">
                                            <div className="flex flex-wrap gap-1">
                                                <SeriesTypeBadge type={item.type} />
                                                <SeriesStatusBadge status={item.status} />
                                            </div>
                                            <div className="grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
                                                <div>
                                                    <p className="text-muted-foreground">Volume</p>
                                                    <p className="font-medium">{item.volumes ?? '—'}</p>
                                                </div>
                                                <div>
                                                    <p className="text-muted-foreground">Skor</p>
                                                    <p className="font-medium">{item.score ?? '—'}</p>
                                                </div>
                                            </div>
                                            {item.synopsis && (
                                                <p className="line-clamp-4 text-xs text-muted-foreground leading-relaxed">
                                                    {item.synopsis}
                                                </p>
                                            )}
                                            {item.already_imported && (
                                                <p className="text-xs text-amber-600 dark:text-amber-400">
                                                    ⚠ Sudah ada di database. Import akan memperbarui data.
                                                </p>
                                            )}
                                        </div>

                                        <div className="flex gap-2 border-t p-3">
                                            <Button
                                                size="sm"
                                                className="w-full"
                                                disabled={importing}
                                                onClick={() => handleImport(item)}
                                            >
                                                <Download className="mr-1.5 h-3.5 w-3.5" />
                                                {importing ? 'Mengimpor...' : item.already_imported ? 'Perbarui' : 'Import'}
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>

                    {/* Pagination */}
                    {(pagination.currentPage ?? 1) > 1 || pagination.hasNextPage ? (
                        <div className="mt-6 flex items-center justify-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={currentPage <= 1}
                                onClick={() => goToPage(currentPage - 1)}
                            >
                                Sebelumnya
                            </Button>
                            <span className="text-sm text-muted-foreground">Hal. {currentPage}</span>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={!pagination.hasNextPage}
                                onClick={() => goToPage(currentPage + 1)}
                            >
                                Berikutnya
                            </Button>
                        </div>
                    ) : null}
                </>
            )}
        </AdminLayout>
    );
}
