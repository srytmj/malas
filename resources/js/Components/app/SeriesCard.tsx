import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { BookOpen } from 'lucide-react';
import { SeriesStatusBadge, SeriesTypeBadge } from '@/Components/app/StatusBadge';
import { AdultBlurOverlay } from '@/Components/app/AdultBlurOverlay';
import { Badge } from '@/Components/ui/badge';
import { type SeriesStatus, type SeriesType } from '@/lib/types';

interface SeriesCardProps {
    id: string;
    title_romaji: string;
    title_english: string | null;
    cover_url: string | null;
    status: SeriesStatus;
    type: SeriesType;
    total_volumes: number | null;
    volumes_count: number;
    score: number | null;
    href: string;
    inCollection?: boolean;
    is_adult?: boolean;
}

export function SeriesCard({
    title_romaji,
    title_english,
    cover_url,
    status,
    type,
    score,
    href,
    inCollection,
    is_adult,
}: SeriesCardProps) {
    const { t } = useTranslation();
    return (
        <Link href={href} className="group flex flex-col overflow-hidden rounded-lg border bg-card text-card-foreground transition-shadow hover:shadow-md">
            {/* Cover */}
            <AdultBlurOverlay isAdult={!!is_adult} className="aspect-[2/3] overflow-hidden bg-muted">
                {cover_url ? (
                    <img
                        src={cover_url}
                        alt={title_romaji}
                        className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                ) : (
                    <div className="flex h-full items-center justify-center">
                        <BookOpen className="h-10 w-10 text-muted-foreground/40" />
                    </div>
                )}
                {inCollection && (
                    <div className="absolute top-2 right-2">
                        <Badge variant="secondary" className="text-xs px-1.5 py-0.5">{t('components.seriesCard.inCollection')}</Badge>
                    </div>
                )}
                {score !== null && (
                    <div className="absolute bottom-2 left-2 rounded bg-black/60 px-1.5 py-0.5 text-xs font-medium text-white">
                        ★ {Number(score).toFixed(1)}
                    </div>
                )}
            </AdultBlurOverlay>

            {/* Info */}
            <div className="flex flex-1 flex-col gap-1.5 p-3">
                <p className="line-clamp-2 text-sm font-medium leading-tight">{title_romaji}</p>
                {title_english && (
                    <p className="line-clamp-1 text-xs text-muted-foreground">{title_english}</p>
                )}
                <div className="mt-auto flex flex-wrap items-center gap-1 pt-1.5">
                    <SeriesTypeBadge type={type} />
                    <SeriesStatusBadge status={status} />
                </div>
            </div>
        </Link>
    );
}
