import { Badge } from '@/Components/ui/badge';
import { type SeriesStatus, type SeriesType, type VolumeType, type CollectionVolumeFormat } from '@/lib/types';

const STATUS_MAP: Record<SeriesStatus, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
    publishing:        { label: 'Publishing',    variant: 'default' },
    finished:          { label: 'Selesai',       variant: 'secondary' },
    on_hiatus:         { label: 'Hiatus',        variant: 'outline' },
    discontinued:      { label: 'Discontinued',  variant: 'destructive' },
    not_yet_published: { label: 'Belum Terbit',  variant: 'outline' },
};

const TYPE_MAP: Record<SeriesType, string> = {
    manga:    'Manga',
    manhwa:   'Manhwa',
    manhua:   'Manhua',
    novel:    'Novel',
    one_shot: 'One Shot',
    doujinshi:'Doujinshi',
};

const VOLUME_TYPE_MAP: Record<VolumeType, string> = {
    regular:  'Regular',
    digital:  'Digital',
    bind_up:  'Bind-up',
};

export function SeriesStatusBadge({ status }: { status: SeriesStatus }) {
    const config = STATUS_MAP[status] ?? { label: status, variant: 'outline' as const };
    return <Badge variant={config.variant}>{config.label}</Badge>;
}

export function SeriesTypeBadge({ type }: { type: SeriesType }) {
    return <Badge variant="outline">{TYPE_MAP[type] ?? type}</Badge>;
}

export function VolumeTypeBadge({ type }: { type: VolumeType }) {
    return <Badge variant="secondary">{VOLUME_TYPE_MAP[type] ?? type}</Badge>;
}

const FORMAT_MAP: Record<CollectionVolumeFormat, { label: string; className: string }> = {
    physical: { label: 'Fisik',    className: 'border-blue-500 text-blue-600 dark:text-blue-400' },
    ebook:    { label: 'Ebook',    className: 'border-purple-500 text-purple-600 dark:text-purple-400' },
    online:   { label: 'Online',   className: 'border-green-500 text-green-600 dark:text-green-400' },
    webtoon:  { label: 'Webtoon',  className: 'border-orange-500 text-orange-600 dark:text-orange-400' },
};

export function VolumeFormatBadge({ format }: { format: CollectionVolumeFormat }) {
    const config = FORMAT_MAP[format] ?? { label: format, className: '' };
    return <Badge variant="outline" className={`text-xs ${config.className}`}>{config.label}</Badge>;
}
