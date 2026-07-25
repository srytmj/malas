import { useEffect, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import { SeriesCard } from '@/Components/app/SeriesCard';
import { Pagination } from '@/Components/app/Pagination';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import { type PaginatedData, type SeriesStatus, type SeriesType } from '@/lib/types';

interface SeriesRow {
    id: string;
    title_romaji: string;
    title_english: string | null;
    cover_url: string | null;
    status: SeriesStatus;
    type: SeriesType;
    total_volumes: number | null;
    volumes_count: number;
    score: number | null;
    is_adult: boolean;
}

interface Props extends PageProps {
    series: PaginatedData<SeriesRow>;
    collectionSeriesIds: string[];
    filters: { search?: string | null; status?: string | null; type?: string | null };
}

export default function CatalogIndex({ series, collectionSeriesIds, filters }: Props) {
    const [search, setSearch]     = useState(filters.search ?? '');
    const [ownership, setOwnership] = useState('');
    const [refreshing, setRefreshing] = useState(false);
    const isFirstRender = useRef(true);

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }
        const t = setTimeout(() => {
            router.get(
                route('catalog.index'),
                { ...filters, search: search || undefined },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 400);
        return () => clearTimeout(t);
    }, [search]);

    function handleFilter(key: string, value: string) {
        router.get(
            route('catalog.index'),
            { ...filters, search, [key]: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function handleRefresh() {
        setRefreshing(true);
        router.reload({ onFinish: () => setRefreshing(false) });
    }

    const collectionSet = new Set(collectionSeriesIds);

    const visibleSeries = series.data.filter((s) => {
        if (ownership === 'owned') return collectionSet.has(s.id);
        if (ownership === 'not_owned') return !collectionSet.has(s.id);
        return true;
    });

    return (
        <UserLayout
            header={
                <PageHeader
                    title="Katalog"
                    description={`${series.total} series tersedia`}
                />
            }
        >
            <Head title="Katalog" />
            {/* Filters */}
            <div className="mb-5 flex flex-wrap items-center gap-2">
                <Input
                    placeholder="Cari judul..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="w-56"
                />
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    onClick={handleRefresh}
                    disabled={refreshing}
                    aria-label="Segarkan"
                >
                    <RefreshCw className={cn('h-4 w-4', refreshing && 'animate-spin')} />
                </Button>
                <Select
                    value={filters.status ?? ''}
                    onValueChange={(v) => handleFilter('status', v ?? '')}
                >
                    <SelectTrigger className="w-36">
                        <SelectValue placeholder="Semua status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">Semua status</SelectItem>
                        <SelectItem value="publishing">Publishing</SelectItem>
                        <SelectItem value="finished">Selesai</SelectItem>
                        <SelectItem value="on_hiatus">Hiatus</SelectItem>
                        <SelectItem value="discontinued">Discontinued</SelectItem>
                        <SelectItem value="not_yet_published">Belum Terbit</SelectItem>
                    </SelectContent>
                </Select>
                <Select
                    value={filters.type ?? ''}
                    onValueChange={(v) => handleFilter('type', v ?? '')}
                >
                    <SelectTrigger className="w-36">
                        <SelectValue placeholder="Semua tipe" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">Semua tipe</SelectItem>
                        <SelectItem value="manga">Manga</SelectItem>
                        <SelectItem value="manhwa">Manhwa</SelectItem>
                        <SelectItem value="manhua">Manhua</SelectItem>
                        <SelectItem value="novel">Novel</SelectItem>
                        <SelectItem value="one_shot">One Shot</SelectItem>
                        <SelectItem value="doujinshi">Doujinshi</SelectItem>
                    </SelectContent>
                </Select>
                <Select value={ownership} onValueChange={(v) => setOwnership(v ?? '')}>
                    <SelectTrigger className="w-44">
                        <SelectValue placeholder="Semua koleksi" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">Semua koleksi</SelectItem>
                        <SelectItem value="owned">Sudah di koleksi</SelectItem>
                        <SelectItem value="not_owned">Belum di koleksi</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            {/* Grid */}
            {visibleSeries.length === 0 ? (
                <div className="py-20 text-center">
                    <p className="text-muted-foreground">Tidak ada series ditemukan.</p>
                    {filters.search && (
                        <p className="mt-2 text-sm text-muted-foreground">
                            Judul yang kamu cari belum ada di katalog?{' '}
                            <Link
                                href={route('tickets.create')}
                                className="font-medium text-primary underline-offset-4 hover:underline"
                            >
                                Buat tiket request
                            </Link>{' '}
                            ke admin.
                        </p>
                    )}
                </div>
            ) : (
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                    {visibleSeries.map((s) => (
                        <SeriesCard
                            key={s.id}
                            {...s}
                            href={route('catalog.show', s.id)}
                            inCollection={collectionSet.has(s.id)}
                        />
                    ))}
                </div>
            )}

            <div className="mt-6">
                <Pagination data={series} />
            </div>
        </UserLayout>
    );
}
