import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { buttonVariants } from '@/Components/ui/button';
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

interface Props extends PageProps {
    stats: Stats;
    series_by_status: Record<string, number>;
}

const STATUS_LABELS: Record<string, string> = {
    publishing:        'Publishing',
    finished:          'Selesai',
    on_hiatus:         'Hiatus',
    discontinued:      'Discontinued',
    not_yet_published: 'Belum Terbit',
};

export default function AdminDashboard({ auth, stats, series_by_status }: Props) {
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

            {Object.keys(series_by_status).length > 0 && (
                <div className="mt-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <TrendingUp className="h-4 w-4" />
                                Series per Status
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {Object.entries(series_by_status).map(([status, count]) => (
                                    <div key={status} className="flex items-center justify-between text-sm">
                                        <span className="text-muted-foreground">
                                            {STATUS_LABELS[status] ?? status}
                                        </span>
                                        <span className="font-medium">{count}</span>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            )}

            <p className="mt-6 text-xs text-muted-foreground">
                Selamat datang, {auth.user?.name}.
            </p>
        </AdminLayout>
    );
}
