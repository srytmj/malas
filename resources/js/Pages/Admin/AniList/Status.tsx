import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { Activity, CheckCircle2, Clock, ExternalLink, XCircle } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import EmptyState from '@/Components/app/EmptyState';
import { PageProps } from '@/types';

interface RecentImport {
    id: string;
    title_romaji: string;
    anilist_id: number;
    updated_at: string;
}

interface Props extends PageProps {
    recentImports: RecentImport[];
}

interface PingResult {
    online: boolean;
    duration_ms: number;
    status: number | null;
}

export default function AniListStatus({ recentImports }: Props) {
    const [checking, setChecking] = useState(false);
    const [result, setResult]     = useState<PingResult | null>(null);
    const [checkedAt, setCheckedAt] = useState<string | null>(null);

    async function checkStatus() {
        setChecking(true);
        try {
            const res = await fetch(route('admin.anilist.status'), { credentials: 'same-origin' });
            const data: PingResult = await res.json();
            setResult(data);
            setCheckedAt(new Date().toLocaleString('id-ID', {
                day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
            }));
        } catch {
            setResult({ online: false, duration_ms: 0, status: null });
        } finally {
            setChecking(false);
        }
    }

    return (
        <AdminLayout
            header={
                <PageHeader
                    title="Status AniList"
                    description="Cek konektivitas AniList API dan riwayat import terbaru."
                />
            }
        >
            <Head title="Status AniList" />

            <div className="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Activity className="h-4 w-4" />
                            Live Check
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {result && (
                            <div className="flex items-center gap-3 rounded-lg border p-4">
                                {result.online ? (
                                    <CheckCircle2 className="h-8 w-8 text-green-500" />
                                ) : (
                                    <XCircle className="h-8 w-8 text-destructive" />
                                )}
                                <div>
                                    <p className="font-semibold">
                                        {result.online ? 'Online' : 'Offline'}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {result.online
                                            ? `Response time: ${result.duration_ms}ms`
                                            : 'Tidak dapat menghubungi graphql.anilist.co'}
                                    </p>
                                    {checkedAt && (
                                        <p className="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground">
                                            <Clock className="h-3 w-3" />
                                            Dicek {checkedAt}
                                        </p>
                                    )}
                                </div>
                            </div>
                        )}

                        <Button onClick={checkStatus} disabled={checking} className="w-full">
                            {checking ? 'Mengecek...' : 'Cek Status'}
                        </Button>

                        <a
                            href="https://status.anilist.co"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground hover:underline"
                        >
                            Lihat status page resmi AniList
                            <ExternalLink className="h-3 w-3" />
                        </a>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Riwayat Import Terbaru</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {recentImports.length === 0 ? (
                            <EmptyState
                                title="Belum ada import"
                                description="Series yang di-import/update via AniList akan muncul di sini."
                                icon={Activity}
                            />
                        ) : (
                            <div className="space-y-2">
                                {recentImports.map((item) => (
                                    <Link
                                        key={item.id}
                                        href={route('admin.series.show', item.id)}
                                        className="flex items-center justify-between rounded-md px-2 py-1.5 text-sm hover:bg-muted"
                                    >
                                        <span className="truncate font-medium">{item.title_romaji}</span>
                                        <span className="shrink-0 text-xs text-muted-foreground">
                                            {new Date(item.updated_at).toLocaleDateString('id-ID', {
                                                day: '2-digit', month: 'short', year: 'numeric',
                                            })}
                                        </span>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
