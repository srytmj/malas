import { Badge } from '@/Components/ui/badge';
import { type SeriesStatus, type SeriesType, type VolumeType } from '@/lib/types';

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
