import { useTranslation } from 'react-i18next';
import { BookOpen } from 'lucide-react';
import { VolumeTypeBadge } from '@/Components/app/StatusBadge';
import { type VolumeType } from '@/lib/types';

interface VolumeItem {
    id: string;
    volume_number: number;
    type: VolumeType;
    isbn: string | null;
    published_at: string | null;
    cover_url: string | null;
}

interface VolumeGridProps {
    volumes: VolumeItem[];
}

function VolumeCard({ volume }: { volume: VolumeItem }) {
    const { t } = useTranslation();
    return (
        <div className="flex flex-col overflow-hidden rounded-lg border bg-card">
            <div className="aspect-[2/3] overflow-hidden bg-muted">
                {volume.cover_url ? (
                    <img
                        src={volume.cover_url}
                        alt={t('components.volumeGrid.volumePrefix', { number: volume.volume_number })}
                        className="h-full w-full object-cover"
                    />
                ) : (
                    <div className="flex h-full items-center justify-center">
                        <BookOpen className="h-6 w-6 text-muted-foreground/30" />
                    </div>
                )}
            </div>

            <div className="p-2">
                <p className="text-xs font-medium">{t('components.volumeGrid.volumePrefix', { number: volume.volume_number })}</p>
                <VolumeTypeBadge type={volume.type} />
            </div>
        </div>
    );
}

export function VolumeGrid({ volumes }: VolumeGridProps) {
    const { t } = useTranslation();
    if (volumes.length === 0) {
        return (
            <p className="py-8 text-center text-sm text-muted-foreground">{t('components.volumeGrid.empty')}</p>
        );
    }

    return (
        <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8">
            {volumes.map((v) => (
                <VolumeCard key={v.id} volume={v} />
            ))}
        </div>
    );
}
