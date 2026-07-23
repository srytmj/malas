import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { type PaginatedData } from '@/lib/types';

interface PaginationProps<T> {
    data: PaginatedData<T>;
}

export function Pagination<T>({ data }: PaginationProps<T>) {
    if (data.last_page <= 1) return null;

    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-sm text-muted-foreground">
                {data.from !== null && data.to !== null
                    ? `Menampilkan ${data.from}–${data.to} dari ${data.total}`
                    : `Total ${data.total}`}
            </p>
            <div className="flex flex-wrap items-center gap-1">
                {data.links.map((link, i) => (
                    link.url ? (
                        <Link
                            key={i}
                            href={link.url}
                            className={cn(
                                'inline-flex h-8 min-w-8 items-center justify-center rounded border px-2 text-sm transition-colors',
                                link.active
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-input bg-background hover:bg-accent',
                            )}
                            preserveScroll
                            // eslint-disable-next-line react/no-danger
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <span
                            key={i}
                            className="inline-flex h-8 min-w-8 items-center justify-center rounded border border-input px-2 text-sm opacity-40"
                            // eslint-disable-next-line react/no-danger
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    )
                ))}
            </div>
        </div>
    );
}
