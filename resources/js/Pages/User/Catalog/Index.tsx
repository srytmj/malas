import { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import { SeriesCard } from '@/Components/app/SeriesCard';
import { Pagination } from '@/Components/app/Pagination';
import { Input } from '@/Components/ui/input';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
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
}

interface Props extends PageProps {
    series: PaginatedData<SeriesRow>;
    collectionSeriesIds: string[];
    filters: { search?: string | null; status?: string | null; type?: string | null };
}

export default function CatalogIndex({ series, collectionSeriesIds, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    useEffect(() => {
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

    const collectionSet = new Set(collectionSeriesIds);

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
            <div className="mb-5 flex flex-wrap gap-2">
                <Input
                    placeholder="Cari judul..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="w-56"
                />
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
            </div>

            {/* Grid */}
            {series.data.length === 0 ? (
                <p className="py-20 text-center text-muted-foreground">Tidak ada series ditemukan.</p>
            ) : (
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                    {series.data.map((s) => (
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
