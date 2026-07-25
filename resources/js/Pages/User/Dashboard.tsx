import { Head, Link } from '@inertiajs/react';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { TicketStatusBadge } from '@/Components/app/StatusBadge';
import { BookOpen, Library, HandCoins, AlertTriangle, Ticket as TicketIcon } from 'lucide-react';
import { PageProps } from '@/types';
import { type TicketStatus } from '@/lib/types';

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
}

export default function UserDashboard({ auth, stats, latest_ticket }: Props) {
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
        </UserLayout>
    );
}
