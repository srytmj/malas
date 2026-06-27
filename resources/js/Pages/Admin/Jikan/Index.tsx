import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { Search, BookOpen, Download, RefreshCw, CheckCircle } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { PageProps } from '@/types';

interface JikanResult {
    mal_id: number;
    title: string;
    title_english: string | null;
    cover_url: string | null;
    type: string | null;
    status: string | null;
    volumes: number | null;
    score: number | null;
    synopsis: string | null;
    published_from: string | null;
    already_imported: boolean;
}

interface Props extends PageProps {
    results: JikanResult[];
    pagination: {
        last_visible_page?: number;
        has_next_page?: boolean;
        current_page?: number;
    };
    filters: { q: string };
    error: string | null;
}

export default function JikanIndex({ results, pagination, filters, error }: Props) {
    const [search, setSearch]       = useState(filters.q);
    const [preview, setPreview]     = useState<JikanResult | null>(null);
    const [importing, setImporting] = useState(false);
    const debounceRef               = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            if (search.trim()) {
                router.get(
                    route('admin.jikan.index'),
                    { q: search.trim() },
                    { preserveState: true, replace: true },
                );
            }
        }, 500);
        return () => { if (debounceRef.current) clearTimeout(debounceRef.current); };
    }, [search]);

    function handleImport(malId: number) {
        setImporting(true);
        router.post(
            route('admin.jikan.import'),
            { mal_id: malId },
            {
                onFinish: () => { setImporting(false); setPreview(null); },
            },
        );
    }

    function goToPage(page: number) {
        router.get(
            route('admin.jikan.index'),
            { q: search.trim(), page },
            { preserveState: true, replace: true },
        );
    }

    const currentPage = pagination.current_page ?? 1;

    return (
        <AdminLayout
            header={
                <PageHeader
                    title="Import dari MyAnimeList"
                    description="Cari dan import data manga/manhwa dari Jikan API (MyAnimeList)"
                />
            }
        >
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
                        onClick={() => router.get(route('admin.jikan.index'), { q: search }, { preserveState: true })}
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
                            <button
                                key={item.mal_id}
                                type="button"
                                onClick={() => setPreview(item)}
                                className="group flex flex-col overflow-hidden rounded-lg border bg-card text-left transition-shadow hover:shadow-md focus:outline-none"
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
                                    {item.type && (
                                        <Badge variant="outline" className="w-fit text-xs">{item.type}</Badge>
                                    )}
                                    {item.volumes && (
                                        <p className="text-xs text-muted-foreground">{item.volumes} vol</p>
                                    )}
                                </div>
                            </button>
                        ))}
                    </div>

                    {/* Pagination */}
                    {(pagination.current_page ?? 1) > 1 || pagination.has_next_page ? (
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
                                disabled={!pagination.has_next_page}
                                onClick={() => goToPage(currentPage + 1)}
                            >
                                Berikutnya
                            </Button>
                        </div>
                    ) : null}
                </>
            )}

            {/* Preview / import dialog */}
            <Dialog open={!!preview} onOpenChange={(open) => !open && setPreview(null)}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>{preview?.title}</DialogTitle>
                        {preview?.title_english && (
                            <DialogDescription>{preview.title_english}</DialogDescription>
                        )}
                    </DialogHeader>

                    {preview && (
                        <div className="grid gap-4 sm:grid-cols-[auto_1fr]">
                            {preview.cover_url && (
                                <img
                                    src={preview.cover_url}
                                    alt={preview.title}
                                    className="w-28 rounded-lg object-cover"
                                />
                            )}
                            <div className="space-y-2 text-sm">
                                <div className="flex flex-wrap gap-1.5">
                                    {preview.type && <Badge variant="outline">{preview.type}</Badge>}
                                    {preview.status && <Badge variant="secondary">{preview.status}</Badge>}
                                </div>
                                <div className="grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                                    <div>
                                        <p className="text-muted-foreground">Volume</p>
                                        <p className="font-medium">{preview.volumes ?? '—'}</p>
                                    </div>
                                    <div>
                                        <p className="text-muted-foreground">Skor</p>
                                        <p className="font-medium">{preview.score ?? '—'}</p>
                                    </div>
                                    <div>
                                        <p className="text-muted-foreground">MAL ID</p>
                                        <p className="font-medium">{preview.mal_id}</p>
                                    </div>
                                    <div>
                                        <p className="text-muted-foreground">Terbit</p>
                                        <p className="font-medium">{preview.published_from ?? '—'}</p>
                                    </div>
                                </div>
                                {preview.synopsis && (
                                    <p className="line-clamp-4 text-xs text-muted-foreground leading-relaxed">
                                        {preview.synopsis}
                                    </p>
                                )}
                                {preview.already_imported && (
                                    <p className="text-xs text-amber-600 dark:text-amber-400">
                                        ⚠ Series ini sudah ada di database. Import akan memperbarui data (cover tidak akan ditimpa).
                                    </p>
                                )}
                            </div>
                        </div>
                    )}

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setPreview(null)}>Batal</Button>
                        <Button
                            disabled={importing}
                            onClick={() => preview && handleImport(preview.mal_id)}
                        >
                            <Download className="mr-1.5 h-4 w-4" />
                            {importing ? 'Mengimpor...' : preview?.already_imported ? 'Perbarui Data' : 'Import'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
