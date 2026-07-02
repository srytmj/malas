import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { type SharedAnnouncement } from '@/types';

const TYPE_STYLES: Record<string, string> = {
    info:    'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-950 dark:text-blue-200 dark:border-blue-800',
    warning: 'bg-yellow-50 text-yellow-800 border-yellow-200 dark:bg-yellow-950 dark:text-yellow-200 dark:border-yellow-800',
    danger:  'bg-red-50 text-red-800 border-red-200 dark:bg-red-950 dark:text-red-200 dark:border-red-800',
    success: 'bg-green-50 text-green-800 border-green-200 dark:bg-green-950 dark:text-green-200 dark:border-green-800',
};

function SingleBanner({ announcement, onDismiss }: {
    announcement: SharedAnnouncement;
    onDismiss: (id: string) => void;
}) {
    return (
        <div
            className={cn(
                'flex items-start gap-3 border-b px-4 py-3 text-sm',
                TYPE_STYLES[announcement.type] ?? TYPE_STYLES.info,
            )}
        >
            <div className="flex-1 min-w-0">
                <span className="font-medium">{announcement.title}</span>
                {announcement.body && (
                    <span className="ml-2 opacity-80">{announcement.body}</span>
                )}
            </div>
            <button
                type="button"
                className="shrink-0 rounded-sm opacity-70 hover:opacity-100 transition-opacity"
                onClick={() => onDismiss(announcement.id)}
                aria-label="Tutup pengumuman"
            >
                <X className="h-4 w-4" />
            </button>
        </div>
    );
}

export default function AnnouncementBanner() {
    const { announcements } = usePage().props;
    const [localDismissed, setLocalDismissed] = useState<Set<string>>(new Set());

    const visible = announcements.filter((a) => !localDismissed.has(a.id));

    if (visible.length === 0) return null;

    function handleDismiss(id: string) {
        setLocalDismissed((prev) => new Set([...prev, id]));
        router.post(route('announcements.dismiss', id), {}, {
            preserveScroll: true,
            preserveState: true,
        });
    }

    return (
        <div>
            {visible.map((a) => (
                <SingleBanner key={a.id} announcement={a} onDismiss={handleDismiss} />
            ))}
        </div>
    );
}
