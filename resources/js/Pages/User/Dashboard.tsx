import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { Bar, BarChart, CartesianGrid, XAxis } from 'recharts';
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
    BookOpen, Library, HandCoins, AlertTriangle, Loader2, Sparkles, Ticket as TicketIcon,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import { type SeriesStatus, type SeriesType, type TicketStatus } from '@/lib/types';

interface RecommendedSeries {
    id: string;
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

interface Props extends PageProps {
    stats: Stats;
    latest_ticket: LatestTicket | null;
    collections_by_status: Partial<Record<SeriesStatus, number>>;
    recommendations: RecommendedSeries[];
}

const STATUS_LABELS: Record<string, string> = {
    publishing:        'Publishing',
    finished:          'Selesai',
    on_hiatus:         'Hiatus',
    discontinued:      'Discontinued',
    not_yet_published: 'Belum Terbit',
};

const statusChartConfig = {
    total: { label: 'Series', color: 'var(--chart-2)' },
} satisfies ChartConfig;

export default function UserDashboard({ auth, stats, latest_ticket, collections_by_status, recommendations }: Props) {
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
        status: STATUS_LABELS[status] ?? status,
        total,
    }));
    return (
        <UserLayout header={<PageHeader title="Dashboard" description={`Selamat datang, ${auth.user?.name}.`} />}>
            <Head title="Dashboard" />
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">Series Koleksi</CardTitle>
                        <BookOpen className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-bold">{stats.series_count}</p>
                        <p className="text-xs text-muted-foreground mt-0.5">series di koleksimu</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">Volume Dimiliki</CardTitle>
                        <Library className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-bold">{stats.owned_volumes_count}</p>
                        <p className="text-xs text-muted-foreground mt-0.5">volume sudah dimiliki</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">Dipinjamkan</CardTitle>
                        <HandCoins className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-bold">{stats.active_loans_count}</p>
                        <p className="text-xs text-muted-foreground mt-0.5">pinjaman aktif</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">Terlambat</CardTitle>
                        <AlertTriangle className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p className={`text-2xl font-bold ${stats.overdue_count > 0 ? 'text-destructive' : ''}`}>
                            {stats.overdue_count}
                        </p>
                        <p className="text-xs text-muted-foreground mt-0.5">pinjaman melewati jatuh tempo</p>
                    </CardContent>
                </Card>
            </div>

            {statusChartData.length > 0 && (
                <div className="mt-6">
                    <Card className="lg:max-w-md">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <BookOpen className="h-4 w-4" />
                                Koleksi per Status
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
                </div>
            )}

            <div className="mt-6">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Sparkles className="h-4 w-4" />
                            Rekomendasi untukmu
                        </CardTitle>
                        <Button variant="outline" size="sm" onClick={handleSurpriseMe}>
                            <Sparkles className="mr-1.5 h-3.5 w-3.5" />
                            Surprise Me
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {recommendations.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Tambahkan beberapa series ke koleksimu dulu supaya kami bisa kasih rekomendasi berdasarkan genre favoritmu.
                            </p>
                        ) : (
                            <Carousel opts={{ align: 'start' }} className="px-8">
                                <CarouselContent>
                                    {recommendations.map((s) => (
                                        <CarouselItem key={s.id} className="sm:basis-1/2 lg:basis-1/3">
                                            <Link
                                                href={route('catalog.show', s.id)}
                                                className="group flex h-full gap-3 rounded-lg border p-3 transition-shadow hover:shadow-sm"
                                            >
                                                <div className="h-32 w-22 shrink-0 overflow-hidden rounded-lg bg-muted">
                                                    {s.cover_url ? (
                                                        <img
                                                            src={s.cover_url}
                                                            alt={s.title_romaji}
                                                            className="h-full w-full object-cover transition-transform group-hover:scale-105"
                                                        />
                                                    ) : (
                                                        <div className="flex h-full w-full items-center justify-center">
                                                            <BookOpen className="h-6 w-6 text-muted-foreground" />
                                                        </div>
                                                    )}
                                                </div>
                                                <div className="min-w-0 space-y-1">
                                                    <p className="line-clamp-2 text-sm font-medium leading-tight">{s.title_romaji}</p>
                                                    {s.authors.length > 0 && (
                                                        <p className="truncate text-xs text-muted-foreground">{s.authors.join(', ')}</p>
                                                    )}
                                                    {s.genres.length > 0 && (
                                                        <div className="flex flex-wrap gap-1">
                                                            {s.genres.slice(0, 3).map((g) => (
                                                                <Badge key={g} variant="outline" className="text-[10px] px-1.5 py-0">{g}</Badge>
                                                            ))}
                                                        </div>
                                                    )}
                                                    {s.synopsis && (
                                                        <p className="line-clamp-3 text-xs text-muted-foreground">{s.synopsis}</p>
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
                                    Tiket Terakhir
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
                        <DialogTitle>Coba nih, kemungkinan kamu bakal suka!</DialogTitle>
                        <DialogDescription>
                            Dipilih secara acak dari series yang belum ada di koleksimu.
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
                            Belum ada series lain yang bisa direkomendasikan.
                        </p>
                    )}
                    <DialogFooter>
                        <Button variant="outline" onClick={handleSurpriseMe} disabled={surpriseLoading}>
                            <Sparkles className="mr-1.5 h-3.5 w-3.5" />
                            Coba Lagi
                        </Button>
                        {surpriseSeries && (
                            <Link href={route('catalog.show', surpriseSeries.id)} className={cn(buttonVariants())}>
                                Lihat Series
                            </Link>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </UserLayout>
    );
}
