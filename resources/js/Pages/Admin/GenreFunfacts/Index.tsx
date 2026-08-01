import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { RotateCcw, Save, Sparkles } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import EmptyState from '@/Components/app/EmptyState';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import { PageProps } from '@/types';

interface FunfactQuotaRow {
    user_id: string;
    name: string;
    avatar: string | null;
    generated_at: string | null;
    used: number;
    quota_max: number;
    quota_override: number | null;
    window_started_at: string | null;
}

interface Props extends PageProps {
    rows: FunfactQuotaRow[];
}

function QuotaRow({ row }: { row: FunfactQuotaRow }) {
    const { t } = useTranslation('admin');
    const [overrideInput, setOverrideInput] = useState(row.quota_override?.toString() ?? '');
    const [processing, setProcessing] = useState<'reset' | 'override' | null>(null);

    function handleReset() {
        setProcessing('reset');
        router.patch(route('admin.funfact-quota.reset', row.user_id), {}, {
            preserveScroll: true,
            onFinish: () => setProcessing(null),
        });
    }

    function handleSaveOverride() {
        setProcessing('override');
        router.patch(route('admin.funfact-quota.override', row.user_id), {
            quota_override: overrideInput === '' ? null : Number(overrideInput),
        }, {
            preserveScroll: true,
            onFinish: () => setProcessing(null),
        });
    }

    return (
        <TableRow>
            <TableCell>
                <div className="flex items-center gap-2.5">
                    <Avatar className="h-7 w-7 shrink-0">
                        <AvatarImage src={row.avatar || undefined} alt={row.name} />
                        <AvatarFallback className="text-xs">{row.name.slice(0, 2).toUpperCase()}</AvatarFallback>
                    </Avatar>
                    <span className="text-sm font-medium">{row.name}</span>
                </div>
            </TableCell>
            <TableCell className="text-center">
                <Badge variant={row.used >= row.quota_max ? 'destructive' : 'outline'} className="text-xs">
                    {row.used} / {row.quota_max}
                </Badge>
            </TableCell>
            <TableCell className="text-xs text-muted-foreground">
                {row.generated_at ? new Date(row.generated_at).toLocaleString('id-ID') : '—'}
            </TableCell>
            <TableCell>
                <div className="flex items-center gap-1.5">
                    <Input
                        type="number"
                        min={0}
                        max={100}
                        placeholder={t('genreFunfacts.defaultPlaceholder')}
                        className="h-7 w-24 text-xs"
                        value={overrideInput}
                        onChange={(e) => setOverrideInput(e.target.value)}
                    />
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        disabled={processing !== null}
                        onClick={handleSaveOverride}
                        aria-label={t('genreFunfacts.saveLimit')}
                    >
                        <Save className="h-3.5 w-3.5" />
                    </Button>
                </div>
            </TableCell>
            <TableCell>
                <Button
                    variant="outline"
                    size="sm"
                    disabled={processing !== null}
                    onClick={handleReset}
                >
                    <RotateCcw className="mr-1.5 h-3.5 w-3.5" />
                    {t('genreFunfacts.reset')}
                </Button>
            </TableCell>
        </TableRow>
    );
}

export default function GenreFunfactsIndex({ rows }: Props) {
    const { t } = useTranslation('admin');
    return (
        <AdminLayout
            header={
                <PageHeader
                    title={t('genreFunfacts.title')}
                    description={t('genreFunfacts.description')}
                />
            }
        >
            <Head title={t('genreFunfacts.title')} />
            {rows.length === 0 ? (
                <EmptyState
                    title={t('genreFunfacts.emptyTitle')}
                    description={t('genreFunfacts.emptyDescription')}
                    icon={Sparkles}
                />
            ) : (
                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('genreFunfacts.table.user')}</TableHead>
                                <TableHead className="text-center">{t('genreFunfacts.table.usedLimit')}</TableHead>
                                <TableHead>{t('genreFunfacts.table.lastGenerated')}</TableHead>
                                <TableHead>{t('genreFunfacts.table.overrideLimit')}</TableHead>
                                <TableHead className="w-28" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.map((row) => (
                                <QuotaRow key={row.user_id} row={row} />
                            ))}
                        </TableBody>
                    </Table>
                </div>
            )}
        </AdminLayout>
    );
}
