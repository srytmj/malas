import { useTranslation } from 'react-i18next';
import { Badge } from '@/Components/ui/badge';
import {
    type SeriesStatus, type SeriesType, type VolumeType, type CollectionVolumeFormat,
    type TicketStatus, type TicketType,
} from '@/lib/types';

const STATUS_VARIANT: Record<SeriesStatus, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    publishing:        'default',
    finished:          'secondary',
    on_hiatus:         'outline',
    discontinued:      'destructive',
    not_yet_published: 'outline',
};

export function SeriesStatusBadge({ status }: { status: SeriesStatus }) {
    const { t } = useTranslation();
    return <Badge variant={STATUS_VARIANT[status] ?? 'outline'}>{t(`badge.status.${status}`, { defaultValue: status })}</Badge>;
}

export function SeriesTypeBadge({ type }: { type: SeriesType }) {
    const { t } = useTranslation();
    return <Badge variant="outline">{t(`badge.type.${type}`, { defaultValue: type })}</Badge>;
}

export function VolumeTypeBadge({ type }: { type: VolumeType }) {
    const { t } = useTranslation();
    return <Badge variant="secondary">{t(`badge.volumeType.${type}`, { defaultValue: type })}</Badge>;
}

const FORMAT_CLASSNAME: Record<CollectionVolumeFormat, string> = {
    physical: 'border-blue-500 text-blue-600 dark:text-blue-400',
    ebook:    'border-purple-500 text-purple-600 dark:text-purple-400',
    online:   'border-green-500 text-green-600 dark:text-green-400',
    webtoon:  'border-orange-500 text-orange-600 dark:text-orange-400',
};

export function VolumeFormatBadge({ format }: { format: CollectionVolumeFormat }) {
    const { t } = useTranslation();
    return (
        <Badge variant="outline" className={`text-xs ${FORMAT_CLASSNAME[format] ?? ''}`}>
            {t(`badge.format.${format}`, { defaultValue: format })}
        </Badge>
    );
}

const TICKET_STATUS_VARIANT: Record<TicketStatus, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    open:        'default',
    in_progress: 'outline',
    resolved:    'secondary',
    closed:      'destructive',
};

export function TicketStatusBadge({ status }: { status: TicketStatus }) {
    const { t } = useTranslation();
    return <Badge variant={TICKET_STATUS_VARIANT[status] ?? 'outline'}>{t(`badge.ticketStatus.${status}`, { defaultValue: status })}</Badge>;
}

export function TicketTypeBadge({ type }: { type: TicketType }) {
    const { t } = useTranslation();
    return <Badge variant="outline">{t(`badge.ticketType.${type}`, { defaultValue: type })}</Badge>;
}
