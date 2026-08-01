import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { SeriesTypeBadge } from '@/Components/app/StatusBadge';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import { PageProps } from '@/types';
import { type SeriesType, type CollectionCondition } from '@/lib/types';
import { useTypeFilterOptions } from '@/lib/typeFilters';

interface CollectionRow {
    id: string;
    series_title: string;
    series_type: SeriesType;
    owned_volumes_count: number;
    total_volumes: number | null;
    condition: CollectionCondition;
}

interface UserData {
    id: string;
    name: string;
    email: string;
    avatar: string | null;
}

interface Props extends PageProps {
    user: UserData;
    collections: CollectionRow[];
}

export default function AdminCollectionsShow({ user, collections }: Props) {
    const { t } = useTranslation('admin');
    const typeOptions = useTypeFilterOptions();

    const CONDITION_LABELS: Record<CollectionCondition, string> = {
        mint: t('collection:show.condition.mint'),
        good: t('collection:show.condition.good'),
        fair: t('collection:show.condition.fair'),
        poor: t('collection:show.condition.poor'),
    };

    const [typeFilter, setTypeFilter] = useState('all');
    const filteredCollections = typeFilter === 'all' ? collections : collections.filter((c) => c.series_type === typeFilter);

    return (
        <AdminLayout
            header={
                <PageHeader
                    title={user.name}
                    description={t('collections.show.seriesCount', { count: collections.length })}
                    breadcrumbs={[
                        { label: t('collections.title'), href: route('admin.collections.index') },
                        { label: user.name },
                    ]}
                />
            }
        >
            <Head title={user.name} />

            <div className="mb-6 flex items-center gap-3">
                <Avatar className="h-10 w-10">
                    <AvatarImage src={user.avatar || undefined} alt={user.name} />
                    <AvatarFallback>{user.name.slice(0, 2).toUpperCase()}</AvatarFallback>
                </Avatar>
                <div>
                    <p className="font-medium">{user.name}</p>
                    <p className="text-sm text-muted-foreground">{user.email}</p>
                </div>
            </div>

            <div className="mb-4 overflow-x-auto">
                <ToggleGroup
                    value={[typeFilter]}
                    onValueChange={(vals) => setTypeFilter(vals[0] ?? 'all')}
                    variant="outline"
                    size="sm"
                >
                    {typeOptions.map((opt) => (
                        <ToggleGroupItem key={opt.value} value={opt.value}>
                            {opt.label}
                        </ToggleGroupItem>
                    ))}
                </ToggleGroup>
            </div>

            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{t('collections.show.table.series')}</TableHead>
                            <TableHead className="w-28">{t('collections.show.table.type')}</TableHead>
                            <TableHead className="w-32 text-right">{t('collections.show.table.ownedVolumes')}</TableHead>
                            <TableHead className="w-24">{t('collections.show.table.condition')}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {filteredCollections.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={4} className="py-12 text-center text-muted-foreground">
                                    {collections.length === 0 ? t('collections.empty') : t('collections.show.emptyNoType')}
                                </TableCell>
                            </TableRow>
                        ) : filteredCollections.map((c) => (
                            <TableRow key={c.id}>
                                <TableCell className="font-medium">{c.series_title}</TableCell>
                                <TableCell><SeriesTypeBadge type={c.series_type} /></TableCell>
                                <TableCell className="text-right">
                                    {c.owned_volumes_count}
                                    {c.total_volumes ? `/${c.total_volumes}` : ''}
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline">{CONDITION_LABELS[c.condition]}</Badge>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </AdminLayout>
    );
}
