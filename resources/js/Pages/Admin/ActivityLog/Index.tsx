import { Head } from '@inertiajs/react';
import { Activity } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import EmptyState from '@/Components/app/EmptyState';
import { Pagination } from '@/Components/app/Pagination';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import { PageProps } from '@/types';
import { type PaginatedData } from '@/lib/types';

interface ActivityLogRow {
    id: string;
    action: string;
    description: string;
    user_name: string;
    user_avatar: string | null;
    created_at: string;
}

interface Props extends PageProps {
    logs: PaginatedData<ActivityLogRow>;
}

function actionBadgeVariant(action: string): 'default' | 'destructive' | 'secondary' | 'outline' {
    if (action.includes('delete') || action.includes('ban')) return 'destructive';
    if (action.includes('update') || action.includes('role_change')) return 'secondary';
    return 'outline';
}

export default function ActivityLogIndex({ logs }: Props) {
    return (
        <AdminLayout
            header={
                <PageHeader
                    title="Log Aktivitas"
                    description="Riwayat aksi sensitif yang dilakukan admin — hapus, ban, ganti role, import database, ubah pengaturan."
                />
            }
        >
            <Head title="Log Aktivitas" />

            {logs.data.length === 0 ? (
                <EmptyState
                    title="Belum ada aktivitas tercatat"
                    description="Aksi sensitif admin (hapus series, ban user, dll) akan muncul di sini."
                    icon={Activity}
                />
            ) : (
                <>
                    <div className="space-y-2">
                        {logs.data.map((log) => (
                            <div key={log.id} className="flex items-start gap-3 rounded-lg border bg-card p-3">
                                <Avatar className="h-8 w-8 shrink-0">
                                    <AvatarImage src={log.user_avatar || undefined} alt={log.user_name} />
                                    <AvatarFallback className="text-xs">{log.user_name.slice(0, 2).toUpperCase()}</AvatarFallback>
                                </Avatar>
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="text-sm font-medium">{log.user_name}</span>
                                        <Badge variant={actionBadgeVariant(log.action)} className="text-[10px] px-1.5 py-0">
                                            {log.action}
                                        </Badge>
                                    </div>
                                    <p className="mt-0.5 text-sm text-muted-foreground">{log.description}</p>
                                </div>
                                <span className="shrink-0 text-xs text-muted-foreground">
                                    {new Date(log.created_at).toLocaleString('id-ID')}
                                </span>
                            </div>
                        ))}
                    </div>

                    <div className="mt-4">
                        <Pagination data={logs} />
                    </div>
                </>
            )}
        </AdminLayout>
    );
}
