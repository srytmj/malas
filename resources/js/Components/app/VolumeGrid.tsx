import { router } from '@inertiajs/react';
import { useState } from 'react';
import { BookOpen, Check } from 'lucide-react';
import { VolumeTypeBadge } from '@/Components/app/StatusBadge';
import { type VolumeType } from '@/lib/types';

interface VolumeItem {
    id: string;
    volume_number: number;
    type: VolumeType;
    isbn: string | null;
    published_at: string | null;
    cover_url: string | null;
    is_owned?: boolean;
}

interface VolumeGridProps {
    volumes: VolumeItem[];
    collectionId?: string;
    toggleRoute?: string;
}

function VolumeCard({ volume, collectionId, toggleRoute }: {
    volume: VolumeItem;
    collectionId?: string;
    toggleRoute?: string;
}) {
    const [owned, setOwned] = useState(volume.is_owned ?? false);
    const [toggling, setToggling] = useState(false);
    const canToggle = !!collectionId && !!toggleRoute;

    function handleToggle(e: React.MouseEvent) {
        e.preventDefault();
        if (!canToggle || toggling) return;
        setToggling(true);
        // Optimistic update
        setOwned((prev) => !prev);
        router.put(
            route(toggleRoute!, { collection: collectionId, volume: volume.id }),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onError: () => setOwned((prev) => !prev), // revert on error
                onFinish: () => setToggling(false),
            },
        );
    }

    return (
        <div className={`group relative flex flex-col overflow-hidden rounded-lg border transition-all ${owned ? 'border-primary/50 bg-primary/5' : 'bg-card'}`}>
            {/* Cover */}
            <div className="relative aspect-[2/3] overflow-hidden bg-muted">
                {volume.cover_url ? (
                    <img
                        src={volume.cover_url}
                        alt={`Vol ${volume.volume_number}`}
                        className="h-full w-full object-cover"
                    />
                ) : (
                    <div className="flex h-full items-center justify-center">
                        <BookOpen className="h-6 w-6 text-muted-foreground/30" />
                    </div>
                )}

                {/* Owned overlay */}
                {owned && (
                    <div className="absolute inset-0 flex items-center justify-center bg-primary/20">
                        <div className="rounded-full bg-primary p-1.5">
                            <Check className="h-4 w-4 text-primary-foreground" />
                        </div>
                    </div>
                )}

                {/* Toggle button */}
                {canToggle && (
                    <button
                        type="button"
                        onClick={handleToggle}
                        disabled={toggling}
                        className="absolute inset-0 w-full cursor-pointer focus:outline-none"
                        aria-label={owned ? 'Tandai belum dimiliki' : 'Tandai dimiliki'}
                    />
                )}
            </div>

            {/* Info */}
            <div className="p-2">
                <p className="text-xs font-medium">Vol. {volume.volume_number}</p>
                <VolumeTypeBadge type={volume.type} />
            </div>
        </div>
    );
}

export function VolumeGrid({ volumes, collectionId, toggleRoute }: VolumeGridProps) {
    if (volumes.length === 0) {
        return (
            <p className="py-8 text-center text-sm text-muted-foreground">Belum ada volume.</p>
        );
    }

    return (
        <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8">
            {volumes.map((v) => (
                <VolumeCard
                    key={v.id}
                    volume={v}
                    collectionId={collectionId}
                    toggleRoute={toggleRoute}
                />
            ))}
        </div>
    );
}
