import { useEffect, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { toast } from 'sonner';
import { useTranslation } from 'react-i18next';
import { generateWithPuter } from '@/lib/puter';
import { Bar, BarChart, CartesianGrid, XAxis } from 'recharts';
import Autoplay from 'embla-carousel-autoplay';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { SeriesStatusBadge, SeriesTypeBadge, TicketStatusBadge } from '@/Components/app/StatusBadge';
import { Button, buttonVariants } from '@/Components/ui/button';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import {
    ChartContainer, ChartTooltip, ChartTooltipContent, type ChartConfig,
} from '@/Components/ui/chart';
import {
    Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious,
} from '@/Components/ui/carousel';
import {
    BookOpen, Library, HandCoins, AlertTriangle, Loader2, PlayCircle, RefreshCw, Sparkles, Ticket as TicketIcon,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import { type SeriesStatus, type SeriesType, type TicketStatus } from '@/lib/types';

interface RecommendedSeries {
    id: string;
    slug: string;
    title_romaji: string;
    cover_url: string | null;
    type: SeriesType;
    status: SeriesStatus;
    authors: string[];
    genres: string[];
    synopsis: string | null;
}

interface Stats {
    series_count: number;
    owned_volumes_count: number;
    active_loans_count: number;
    overdue_count: number;
}

interface LatestTicket {
    id: string;
    subject: string;
    status: TicketStatus;
}

interface GenreDistributionItem {
    genre: string;
    count: number;
    percent: number;
}

interface FunfactData {
    eligible: boolean;
    content: string | null;
    generated_at: string | null;
    quota_remaining: number;
    quota_max: number;
    provider: 'puter' | 'gemini' | 'openai' | 'claude';
    needs_auto_generate: boolean;
    prompt: string | null;
}

interface ContinueReadingItem {
    collection_id: string;
    series_title: string;
    cover_url: string | null;
    next_volume: number;
}

interface Props extends PageProps {
    stats: Stats;
    latest_ticket: LatestTicket | null;
    collections_by_status: Partial<Record<SeriesStatus, number>>;
    recommendations: RecommendedSeries[];
    continue_reading: ContinueReadingItem[];
    genre_distribution: GenreDistributionItem[];
    funfact: FunfactData;
}

const statusChartConfig = {
    total: { label: 'Series', color: 'var(--chart-2)' },
} satisfies ChartConfig;

const WORD_CLOUD_SIZE_TIERS = ['text-xs', 'text-sm', 'text-base', 'text-lg', 'text-xl'];
const WORD_CLOUD_OPACITY_TIERS = ['opacity-50', 'opacity-65', 'opacity-80', 'opacity-90', 'opacity-100'];
const WORD_CLOUD_COLOR_TIERS = [
    'text-[var(--chart-1)]', 'text-[var(--chart-2)]', 'text-[var(--chart-3)]', 'text-[var(--chart-4)]', 'text-[var(--chart-5)]',
];

function GenreWordCloud({ data, selected, onSelect }: {
    data: GenreDistributionItem[]; selected: string | null; onSelect: (genre: string) => void;
}) {
    const { t } = useTranslation('dashboard');

    if (data.length === 0) {
        return <p className="py-6 text-center text-sm text-muted-foreground">{t('funfact.noGenreData')}</p>;
    }

    const max = Math.max(...data.map((d) => d.percent));
    const min = Math.min(...data.map((d) => d.percent));

    return (
        <div className="flex flex-wrap items-center justify-center gap-x-2.5 gap-y-1.5 py-6">
            {data.map((d, i) => {
                const ratio = max === min ? 1 : (d.percent - min) / (max - min);
                const tier = Math.min(WORD_CLOUD_SIZE_TIERS.length - 1, Math.floor(ratio * WORD_CLOUD_SIZE_TIERS.length));
                const isSelected = selected === d.genre;
                return (
                    <button
                        type="button"
                        key={d.genre}
                        title={`${d.genre} — ${d.percent}% (${d.count} series)`}
                        onClick={() => onSelect(d.genre)}
                        className={cn(
                            'cursor-pointer rounded font-semibold leading-none transition-opacity hover:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                            WORD_CLOUD_SIZE_TIERS[tier],
                            isSelected ? 'underline decoration-2 underline-offset-4 opacity-100' : WORD_CLOUD_OPACITY_TIERS[tier],
                            WORD_CLOUD_COLOR_TIERS[i % WORD_CLOUD_COLOR_TIERS.length],
                        )}
                    >
                        {d.genre}
                    </button>
                );
            })}
        </div>
    );
}

function GenreMiniCard({ series }: { series: RecommendedSeries }) {
    return (
        <Link
            href={route('catalog.show', series.slug)}
            className="group flex flex-col overflow-hidden rounded-lg border bg-card transition-shadow hover:shadow-sm"
        >
            <div className="aspect-[2/3] overflow-hidden bg-muted">
                {series.cover_url ? (
                    <img
                        src={series.cover_url}
                        alt={series.title_romaji}
                        className="h-full w-full object-cover transition-transform group-hover:scale-105"
                    />
                ) : (
                    <div className="flex h-full items-center justify-center">
                        <BookOpen className="h-5 w-5 text-muted-foreground/40" />
                    </div>
                )}
            </div>
            <p className="line-clamp-2 p-1.5 text-xs font-medium leading-tight">{series.title_romaji}</p>
        </Link>
    );
}

interface GenreDetail {
    genre: string;
    owned: RecommendedSeries[];
    recommended: RecommendedSeries[];
}

function GenreDetailSection({ genre }: { genre: string }) {
    const { t } = useTranslation('dashboard');
    const [loading, setLoading] = useState(true);
    const [detail, setDetail] = useState<GenreDetail | null>(null);

    useEffect(() => {
        let cancelled = false;
        setLoading(true);
        setDetail(null);

        (async () => {
            try {
                const url = new URL(route('dashboard.genre-detail'), window.location.origin);
                url.searchParams.set('genre', genre);
                const res = await fetch(url.toString(), { credentials: 'same-origin' });
                const data: GenreDetail = await res.json();
                if (!cancelled) setDetail(data);
            } catch {
                if (!cancelled) setDetail({ genre, owned: [], recommended: [] });
            } finally {
                if (!cancelled) setLoading(false);
            }
        })();

        return () => { cancelled = true; };
    }, [genre]);

    if (loading) {
        return (
            <div className="flex items-center justify-center py-8 text-muted-foreground">
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                {t('funfact.loadingDetail')}
            </div>
        );
    }

    if (!detail || (detail.owned.length === 0 && detail.recommended.length === 0)) {
        return (
            <p className="border-t pt-4 text-sm text-muted-foreground">
                {t('funfact.noSeriesForGenre', { genre })}
            </p>
        );
    }

    return (
        <div className="space-y-4 border-t pt-4">
            {detail.owned.length > 0 && (
                <div>
                    <p className="mb-2 text-xs font-medium text-muted-foreground">
                        {t('funfact.ownedWithGenre', { genre: detail.genre })}
                    </p>
                    <div className="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6">
                        {detail.owned.map((s) => <GenreMiniCard key={s.id} series={s} />)}
                    </div>
                </div>
            )}
            {detail.recommended.length > 0 && (
                <div>
                    <p className="mb-2 text-xs font-medium text-muted-foreground">
                        {t('funfact.recommendedWithGenre', { genre: detail.genre })}
                    </p>
                    <div className="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6">
                        {detail.recommended.map((s) => <GenreMiniCard key={s.id} series={s} />)}
                    </div>
                </div>
            )}
        </div>
    );
}

function GenreFunfactCard({ genreDistribution, funfact }: { genreDistribution: GenreDistributionItem[]; funfact: FunfactData }) {
    const { t } = useTranslation('dashboard');
    const [regenerating, setRegenerating] = useState(false);
    const [selectedGenre, setSelectedGenre] = useState<string | null>(null);
    const autoGenerateAttempted = useRef(false);

    function toggleGenre(genre: string) {
        setSelectedGenre((prev) => (prev === genre ? null : genre));
    }

    // Auto-generate untuk provider Puter jalan di browser (bukan server) — silent, di background,
    // begitu dashboard dimuat dan server bilang funfact-nya perlu di-refresh.
    useEffect(() => {
        if (autoGenerateAttempted.current) return;
        if (funfact.provider !== 'puter' || !funfact.needs_auto_generate || !funfact.prompt) return;

        autoGenerateAttempted.current = true;

        generateWithPuter(funfact.prompt)
            .then((content) => {
                router.post(route('dashboard.funfact.auto-save'), { content }, {
                    preserveScroll: true,
                    preserveState: true,
                    only: ['funfact'],
                });
            })
            .catch((err: unknown) => {
                const message = err instanceof Error ? err.message : t('funfact.generateFailed');
                router.post(route('dashboard.funfact.report-error'), { context: 'auto', message }, {
                    preserveScroll: true,
                    preserveState: true,
                    only: ['funfact'],
                });
            });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    function handleRegenerate() {
        setRegenerating(true);

        if (funfact.provider === 'puter') {
            if (!funfact.prompt) {
                toast.error(t('funfact.generateFailed'));
                setRegenerating(false);
                return;
            }

            generateWithPuter(funfact.prompt)
                .then((content) => {
                    router.post(route('dashboard.funfact.regenerate'), { content }, {
                        preserveScroll: true,
                        onFinish: () => setRegenerating(false),
                    });
                })
                .catch((err: unknown) => {
                    const message = err instanceof Error ? err.message : t('funfact.generateFailed');
                    toast.error(t('funfact.generateFailed'));
                    router.post(route('dashboard.funfact.report-error'), { context: 'manual', message }, {
                        preserveScroll: true,
                        preserveState: true,
                        only: ['funfact'],
                    });
                    setRegenerating(false);
                });
            return;
        }

        router.post(route('dashboard.funfact.regenerate'), {}, {
            preserveScroll: true,
            onFinish: () => setRegenerating(false),
        });
    }

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between">
                <CardTitle className="flex items-center gap-2 text-base">
                    <Sparkles className="h-4 w-4" />
                    {t('funfact.title')}
                </CardTitle>
                {funfact.eligible && (
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={regenerating || funfact.quota_remaining === 0}
                        onClick={handleRegenerate}
                    >
                        <RefreshCw className={cn('mr-1.5 h-3.5 w-3.5', regenerating && 'animate-spin')} />
                        {regenerating ? t('funfact.generating') : t('funfact.generate')}
                    </Button>
                )}
            </CardHeader>
            <CardContent className="space-y-4">
                {!funfact.eligible ? (
                    <p className="text-sm text-muted-foreground">
                        {t('funfact.notEligible')}
                    </p>
                ) : (
                    <>
                        <GenreWordCloud data={genreDistribution} selected={selectedGenre} onSelect={toggleGenre} />
                        {genreDistribution.length > 0 && (
                            <p className="-mt-3 text-center text-xs text-muted-foreground">
                                {t('funfact.clickGenreHint')}
                            </p>
                        )}
                        {funfact.content && (
                            <p className="rounded-lg border bg-muted/40 p-3 text-sm leading-relaxed">
                                {funfact.content}
                            </p>
                        )}
                        <p className="text-xs text-muted-foreground">
                            {t('funfact.quotaRemaining', { remaining: funfact.quota_remaining, max: funfact.quota_max })}
                        </p>
                        {selectedGenre && <GenreDetailSection genre={selectedGenre} />}
                    </>
                )}
            </CardContent>
        </Card>
    );
}

function ContinueReadingCard({ items }: { items: ContinueReadingItem[] }) {
    const { t } = useTranslation('dashboard');
    if (items.length === 0) return null;

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                    <PlayCircle className="h-4 w-4" />
                    {t('continueReading')}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    {items.map((item) => (
                        <Link
                            key={item.collection_id}
                            href={route('collection.show', item.collection_id)}
                            className="group flex flex-col overflow-hidden rounded-lg border bg-card transition-shadow hover:shadow-sm"
                        >
                            <div className="aspect-[2/3] overflow-hidden bg-muted">
                                {item.cover_url ? (
                                    <img
                                        src={item.cover_url}
                                        alt={item.series_title}
                                        className="h-full w-full object-cover transition-transform group-hover:scale-105"
                                    />
                                ) : (
                                    <div className="flex h-full items-center justify-center">
                                        <BookOpen className="h-6 w-6 text-muted-foreground/40" />
                                    </div>
                                )}
                            </div>
                            <div className="p-2">
                                <p className="line-clamp-2 text-xs font-medium leading-tight">{item.series_title}</p>
                                <p className="mt-0.5 text-xs text-muted-foreground">{t('volumeUnread', { number: item.next_volume })}</p>
                            </div>
                        </Link>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}

export default function UserDashboard({
    auth, stats, latest_ticket, collections_by_status, recommendations, continue_reading, genre_distribution, funfact,
}: Props) {
    const { t } = useTranslation('dashboard');
    const [surpriseOpen, setSurpriseOpen] = useState(false);
    const [surpriseLoading, setSurpriseLoading] = useState(false);
    const [surpriseSeries, setSurpriseSeries] = useState<RecommendedSeries | null>(null);

    async function handleSurpriseMe() {
        setSurpriseOpen(true);
        setSurpriseLoading(true);
        try {
            const res = await fetch(route('dashboard.surprise-me'), { credentials: 'same-origin' });
            const data: { series: RecommendedSeries | null } = await res.json();
            setSurpriseSeries(data.series);
        } catch {
            setSurpriseSeries(null);
        } finally {
            setSurpriseLoading(false);
        }
    }

    const statusChartData = Object.entries(collections_by_status).map(([status, total]) => ({
        status: t(`status.${status}`, { defaultValue: status }),
        total,
    }));
    return (
        <UserLayout header={<PageHeader title={t('title')} description={t('welcome', { name: auth.user?.name })} />}>
            <Head title={t('title')} />
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">{t('stats.seriesInCollection')}</CardTitle>
                        <BookOpen className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-bold">{stats.series_count}</p>
                        <p className="text-xs text-muted-foreground mt-0.5">{t('stats.seriesInCollectionHint')}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">{t('stats.volumesOwned')}</CardTitle>
                        <Library className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-bold">{stats.owned_volumes_count}</p>
                        <p className="text-xs text-muted-foreground mt-0.5">{t('stats.volumesOwnedHint')}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">{t('stats.onLoan')}</CardTitle>
                        <HandCoins className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-bold">{stats.active_loans_count}</p>
                        <p className="text-xs text-muted-foreground mt-0.5">{t('stats.onLoanHint')}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">{t('stats.overdue')}</CardTitle>
                        <AlertTriangle className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p className={`text-2xl font-bold ${stats.overdue_count > 0 ? 'text-destructive' : ''}`}>
                            {stats.overdue_count}
                        </p>
                        <p className="text-xs text-muted-foreground mt-0.5">{t('stats.overdueHint')}</p>
                    </CardContent>
                </Card>
            </div>

            {continue_reading.length > 0 && (
                <div className="mt-6">
                    <ContinueReadingCard items={continue_reading} />
                </div>
            )}

            <div className="mt-6 grid gap-4 lg:grid-cols-2">
                {statusChartData.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <BookOpen className="h-4 w-4" />
                                {t('collectionsByStatus')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ChartContainer config={statusChartConfig} className="h-56 w-full">
                                <BarChart data={statusChartData} margin={{ left: -20 }}>
                                    <CartesianGrid vertical={false} />
                                    <XAxis
                                        dataKey="status"
                                        tickLine={false}
                                        axisLine={false}
                                        tickMargin={8}
                                        interval={0}
                                        fontSize={11}
                                    />
                                    <ChartTooltip content={<ChartTooltipContent hideLabel />} />
                                    <Bar dataKey="total" fill="var(--color-total)" radius={4} />
                                </BarChart>
                            </ChartContainer>
                        </CardContent>
                    </Card>
                )}

                <GenreFunfactCard genreDistribution={genre_distribution} funfact={funfact} />
            </div>

            <div className="mt-6">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Sparkles className="h-4 w-4" />
                            {t('recommendations.title')}
                        </CardTitle>
                        <Button variant="outline" size="sm" onClick={handleSurpriseMe}>
                            <Sparkles className="mr-1.5 h-3.5 w-3.5" />
                            {t('recommendations.surpriseMe')}
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {recommendations.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                {t('recommendations.empty')}
                            </p>
                        ) : (
                            <Carousel
                                opts={{ align: 'start', loop: true }}
                                plugins={[Autoplay({ delay: 6000 })]}
                                className="px-8"
                            >
                                <CarouselContent>
                                    {recommendations.map((s) => (
                                        <CarouselItem key={s.id}>
                                            <Link
                                                href={route('catalog.show', s.slug)}
                                                className="group flex items-center gap-5 rounded-lg border bg-gradient-to-r from-muted/60 to-transparent p-5 transition-shadow hover:shadow-md"
                                            >
                                                <div className="h-44 w-32 shrink-0 overflow-hidden rounded-lg bg-muted shadow-sm">
                                                    {s.cover_url ? (
                                                        <img
                                                            src={s.cover_url}
                                                            alt={s.title_romaji}
                                                            className="h-full w-full object-cover transition-transform group-hover:scale-105"
                                                        />
                                                    ) : (
                                                        <div className="flex h-full w-full items-center justify-center">
                                                            <BookOpen className="h-8 w-8 text-muted-foreground" />
                                                        </div>
                                                    )}
                                                </div>
                                                <div className="min-w-0 space-y-2">
                                                    <p className="text-lg font-semibold leading-tight">{s.title_romaji}</p>
                                                    {s.authors.length > 0 && (
                                                        <p className="truncate text-sm text-muted-foreground">{s.authors.join(', ')}</p>
                                                    )}
                                                    {s.genres.length > 0 && (
                                                        <div className="flex flex-wrap gap-1">
                                                            {s.genres.slice(0, 4).map((g) => (
                                                                <Badge key={g} variant="outline" className="text-xs">{g}</Badge>
                                                            ))}
                                                        </div>
                                                    )}
                                                    {s.synopsis && (
                                                        <p className="line-clamp-2 text-sm text-muted-foreground">{s.synopsis}</p>
                                                    )}
                                                </div>
                                            </Link>
                                        </CarouselItem>
                                    ))}
                                </CarouselContent>
                                <CarouselPrevious />
                                <CarouselNext />
                            </Carousel>
                        )}
                    </CardContent>
                </Card>
            </div>

            {latest_ticket && (
                <div className="mt-6">
                    <Link href={route('tickets.show', latest_ticket.id)} className="block">
                        <Card className="transition-shadow hover:shadow-sm">
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                    <TicketIcon className="h-4 w-4" />
                                    {t('latestTicket')}
                                </CardTitle>
                                <TicketStatusBadge status={latest_ticket.status} />
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm font-medium">{latest_ticket.subject}</p>
                            </CardContent>
                        </Card>
                    </Link>
                </div>
            )}

            <Dialog open={surpriseOpen} onOpenChange={setSurpriseOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('surprise.title')}</DialogTitle>
                        <DialogDescription>
                            {t('surprise.description')}
                        </DialogDescription>
                    </DialogHeader>
                    {surpriseLoading ? (
                        <div className="flex h-40 items-center justify-center">
                            <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                        </div>
                    ) : surpriseSeries ? (
                        <div className="flex gap-4">
                            <div className="h-40 w-28 shrink-0 overflow-hidden rounded-lg bg-muted">
                                {surpriseSeries.cover_url ? (
                                    <img
                                        src={surpriseSeries.cover_url}
                                        alt={surpriseSeries.title_romaji}
                                        className="h-full w-full object-cover"
                                    />
                                ) : (
                                    <div className="flex h-full w-full items-center justify-center">
                                        <BookOpen className="h-6 w-6 text-muted-foreground" />
                                    </div>
                                )}
                            </div>
                            <div className="min-w-0 space-y-2">
                                <p className="font-medium leading-tight">{surpriseSeries.title_romaji}</p>
                                {surpriseSeries.authors.length > 0 && (
                                    <p className="text-xs text-muted-foreground">{surpriseSeries.authors.join(', ')}</p>
                                )}
                                <div className="flex flex-wrap gap-1.5">
                                    <SeriesTypeBadge type={surpriseSeries.type} />
                                    <SeriesStatusBadge status={surpriseSeries.status} />
                                </div>
                                {surpriseSeries.genres.length > 0 && (
                                    <div className="flex flex-wrap gap-1">
                                        {surpriseSeries.genres.slice(0, 4).map((g) => (
                                            <Badge key={g} variant="outline" className="text-[10px] px-1.5 py-0">{g}</Badge>
                                        ))}
                                    </div>
                                )}
                                {surpriseSeries.synopsis && (
                                    <p className="line-clamp-3 text-xs text-muted-foreground">{surpriseSeries.synopsis}</p>
                                )}
                            </div>
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            {t('surprise.empty')}
                        </p>
                    )}
                    <DialogFooter>
                        <Button variant="outline" onClick={handleSurpriseMe} disabled={surpriseLoading}>
                            <Sparkles className="mr-1.5 h-3.5 w-3.5" />
                            {t('surprise.tryAgain')}
                        </Button>
                        {surpriseSeries && (
                            <Link href={route('catalog.show', surpriseSeries.slug)} className={cn(buttonVariants())}>
                                {t('surprise.viewSeries')}
                            </Link>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </UserLayout>
    );
}
