import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { BookOpen, Heart, Library, Trash2 } from 'lucide-react';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle, EmptyDescription, EmptyContent } from '@/Components/ui/empty';
import { SeriesStatusBadge } from '@/Components/app/StatusBadge';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import { useTypeFilterOptions } from '@/lib/typeFilters';
import { PageProps } from '@/types';
import { type SeriesStatus, type SeriesType } from '@/lib/types';

interface WishlistRow {
    id: string;
    series_id: string;
    series_slug: string;
    title_romaji: string;
    title_english: string | null;
    cover_url: string | null;
    status: SeriesStatus;
    type: SeriesType;
    genres: string[];
}

interface Props extends PageProps {
    items: WishlistRow[];
}

export default function WishlistIndex({ items }: Props) {
    const { t } = useTranslation('user');
    const typeFilterOptions = useTypeFilterOptions();
    const [removingId, setRemovingId] = useState<string | null>(null);
    const [addingId, setAddingId] = useState<string | null>(null);
    const [typeFilter, setTypeFilter] = useState('all');

    const filteredItems = typeFilter === 'all' ? items : items.filter((i) => i.type === typeFilter);

    function handleRemove(id: string) {
        setRemovingId(id);
        router.delete(route('wishlist.destroy', id), {
            onFinish: () => setRemovingId(null),
        });
    }

    function handleAddToCollection(seriesId: string, wishlistId: string) {
        setAddingId(wishlistId);
        router.post(route('collection.store'), { series_ids: [seriesId] }, {
            onFinish: () => setAddingId(null),
        });
    }

    return (
        <UserLayout
            header={
                <PageHeader
                    title={t('wishlist.title')}
                    description={t('wishlist.description', { count: items.length })}
                />
            }
        >
            <Head title={t('wishlist.title')} />
            {items.length === 0 ? (
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <Heart />
                        </EmptyMedia>
                        <EmptyTitle>{t('wishlist.empty.title')}</EmptyTitle>
                        <EmptyDescription>
                            {t('wishlist.empty.description')}
                        </EmptyDescription>
                    </EmptyHeader>
                    <EmptyContent>
                        <Link href={route('catalog.index')} className="text-sm font-medium text-primary underline-offset-4 hover:underline">
                            {t('wishlist.empty.browseCatalog')}
                        </Link>
                    </EmptyContent>
                </Empty>
            ) : (
                <>
                    <div className="mb-4 overflow-x-auto">
                        <ToggleGroup
                            value={[typeFilter]}
                            onValueChange={(vals) => setTypeFilter(vals[0] ?? 'all')}
                            variant="outline"
                            size="sm"
                        >
                            {typeFilterOptions.map((opt) => (
                                <ToggleGroupItem key={opt.value} value={opt.value}>
                                    {opt.label}
                                </ToggleGroupItem>
                            ))}
                        </ToggleGroup>
                    </div>
                    {filteredItems.length === 0 ? (
                        <p className="py-12 text-center text-sm text-muted-foreground">
                            {t('wishlist.noneForType')}
                        </p>
                    ) : (
                <div className="grid gap-4 [grid-template-columns:repeat(auto-fill,minmax(160px,1fr))]">
                    {filteredItems.map((item) => (
                        <div key={item.id} className="flex flex-col overflow-hidden rounded-lg border bg-card">
                            <Link href={route('catalog.show', item.series_slug)} className="group block">
                                <div className="aspect-[2/3] w-full overflow-hidden bg-muted">
                                    {item.cover_url ? (
                                        <img
                                            src={item.cover_url}
                                            alt={item.title_romaji}
                                            className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        />
                                    ) : (
                                        <div className="flex h-full items-center justify-center">
                                            <BookOpen className="h-10 w-10 text-muted-foreground/40" />
                                        </div>
                                    )}
                                </div>
                            </Link>
                            <div className="flex flex-1 flex-col gap-1.5 p-3">
                                <Link href={route('catalog.show', item.series_slug)} className="hover:underline">
                                    <p className="line-clamp-2 text-sm font-medium leading-tight">{item.title_romaji}</p>
                                </Link>
                                {item.title_english && (
                                    <p className="line-clamp-1 text-xs text-muted-foreground">{item.title_english}</p>
                                )}
                                <div className="flex flex-wrap items-center gap-1.5">
                                    <SeriesStatusBadge status={item.status} />
                                </div>
                                {item.genres.length > 0 && (
                                    <div className="flex flex-wrap gap-1">
                                        {item.genres.slice(0, 2).map((g) => (
                                            <Badge key={g} variant="outline" className="text-[10px] px-1.5 py-0">{g}</Badge>
                                        ))}
                                    </div>
                                )}
                                <div className="mt-auto flex items-center gap-1.5 pt-1.5">
                                    <Button
                                        size="sm"
                                        className="flex-1 px-2"
                                        disabled={addingId === item.id}
                                        onClick={() => handleAddToCollection(item.series_id, item.id)}
                                        title={t('wishlist.addToCollection')}
                                    >
                                        <Library className="mr-1 h-3.5 w-3.5 shrink-0" />
                                        <span className="truncate">{addingId === item.id ? t('wishlist.adding') : t('wishlist.add')}</span>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-8 w-8 shrink-0 text-destructive/60 hover:text-destructive"
                                        disabled={removingId === item.id}
                                        onClick={() => handleRemove(item.id)}
                                    >
                                        <Trash2 className="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
                    )}
                </>
            )}
        </UserLayout>
    );
}
