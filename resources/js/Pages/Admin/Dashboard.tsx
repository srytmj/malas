import { Head, Link } from '@inertiajs/react';
import { Bar, BarChart, CartesianGrid, Cell, Pie, PieChart, XAxis } from 'recharts';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { buttonVariants } from '@/Components/ui/button';
import {
    ChartContainer, ChartTooltip, ChartTooltipContent, type ChartConfig,
} from '@/Components/ui/chart';
import { BookOpen, Library, HandCoins, Ticket, Users, TrendingUp } from 'lucide-react';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';

interface Stats {
    series_count: number;
    volumes_count: number;
    collections_count: number;
    users_count: number;
    active_loans_count: number;
    open_tickets_count: number;
    in_progress_tickets_count: number;
}

interface LoansByStatus {
    returned: number;
    overdue: number;
    active: number;
}

interface Props extends PageProps {
    stats: Stats;
    series_by_status: Record<string, number>;
    collections_by_type: Record<string, number>;
    loans_by_status: LoansByStatus;
}

const STATUS_LABELS: Record<string, string> = {
    publishing:        'Publishing',
    finished:          'Selesai',
    on_hiatus:         'Hiatus',
    discontinued:      'Discontinued',
    not_yet_published: 'Belum Terbit',
};

const TYPE_LABELS: Record<string, string> = {
    manga:     'Manga',
    manhwa:    'Manhwa',
    manhua:    'Manhua',
    novel:     'Novel',
    one_shot:  'One Shot',
    doujinshi: 'Doujinshi',
};

const LOAN_STATUS_LABELS: Record<keyof LoansByStatus, string> = {
    active:   'Dipinjam',
    overdue:  'Terlambat',
    returned: 'Dikembalikan',
};

const seriesChartConfig = {
    total: { label: 'Series', color: 'var(--chart-1)' },
} satisfies ChartConfig;

const collectionsChartConfig = {
    total: { label: 'Koleksi', color: 'var(--chart-2)' },
} satisfies ChartConfig;

const loanChartConfig = {
    active:   { label: 'Dipinjam', color: 'var(--chart-1)' },
    overdue:  { label: 'Terlambat', color: 'var(--destructive)' },
    returned: { label: 'Dikembalikan', color: 'var(--chart-3)' },
} satisfies ChartConfig;

export default function AdminDashboard({ auth, stats, series_by_status, collections_by_type, loans_by_status }: Props) {
    const seriesChartData = Object.entries(series_by_status).map(([status, total]) => ({
        status: STATUS_LABELS[status] ?? status,
        total,
    }));

    const collectionsChartData = Object.entries(collections_by_type).map(([type, total]) => ({
        type: TYPE_LABELS[type] ?? type,
        total,
    }));

    const loanChartData = (Object.keys(loans_by_status) as (keyof LoansByStatus)[])
        .filter((key) => loans_by_status[key] > 0)
        .map((key) => ({
            status: key,
            label: LOAN_STATUS_LABELS[key],
            total: loans_by_status[key],
            fill: `var(--color-${key})`,
        }));
    return (
        <AdminLayout header={<PageHeader title="Dashboard" description="Ringkasan sistem MALAS." />}>
            <Head title="Dashboard" />
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">Total Series</CardTitle>
                        <BookOpen className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-bold">{stats.series_count}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">Total Volume</CardTitle>
                        <Library className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-bold">{stats.volumes_count}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">Total Koleksi</CardTitle>
                        <Library className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-bold">{stats.collections_count}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">Pengguna</CardTitle>
                        <Users className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-bold">{stats.users_count}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">Pinjaman Aktif</CardTitle>
                        <HandCoins className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-bold">{stats.active_loans_count}</p>
                    </CardContent>
                </Card>

                <Link href={route('admin.tickets.index')} className="block">
                    <Card className="transition-shadow hover:shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">Tiket Terbuka</CardTitle>
                            <Ticket className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className={cn('text-2xl font-bold', stats.open_tickets_count > 0 && 'text-destructive')}>
                                {stats.open_tickets_count}
                            </p>
                            <p className="text-xs text-muted-foreground mt-0.5">
                                {stats.in_progress_tickets_count} sedang diproses
                            </p>
                        </CardContent>
                    </Card>
                </Link>
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-3">
                {seriesChartData.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <TrendingUp className="h-4 w-4" />
                                Series per Status
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ChartContainer config={seriesChartConfig} className="h-56 w-full">
                                <BarChart data={seriesChartData} margin={{ left: -20 }}>
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

                {collectionsChartData.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Library className="h-4 w-4" />
                                Koleksi per Tipe
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ChartContainer config={collectionsChartConfig} className="h-56 w-full">
                                <BarChart data={collectionsChartData} margin={{ left: -20 }}>
                                    <CartesianGrid vertical={false} />
                                    <XAxis
                                        dataKey="type"
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

                {loanChartData.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <HandCoins className="h-4 w-4" />
                                Status Pinjaman
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ChartContainer config={loanChartConfig} className="mx-auto h-56 aspect-square">
                                <PieChart>
                                    <ChartTooltip content={<ChartTooltipContent nameKey="status" hideLabel />} />
                                    <Pie data={loanChartData} dataKey="total" nameKey="label" innerRadius={45}>
                                        {loanChartData.map((entry) => (
                                            <Cell key={entry.status} fill={entry.fill} />
                                        ))}
                                    </Pie>
                                </PieChart>
                            </ChartContainer>
                            <div className="mt-2 flex flex-wrap justify-center gap-3 text-xs text-muted-foreground">
                                {loanChartData.map((entry) => (
                                    <span key={entry.status} className="flex items-center gap-1.5">
                                        <span className="h-2 w-2 rounded-full" style={{ backgroundColor: entry.fill }} />
                                        {entry.label} ({entry.total})
                                    </span>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>

            <p className="mt-6 text-xs text-muted-foreground">
                Selamat datang, {auth.user?.name}.
            </p>
        </AdminLayout>
    );
}
