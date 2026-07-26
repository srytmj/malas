import { Link, router } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { type PaginatedData } from '@/lib/types';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';

const PER_PAGE_OPTIONS = [5, 10, 25, 50, 100];

interface PaginationProps<T> {
    data: PaginatedData<T>;
    /** Inertia route name (e.g. "admin.series.index") — enables the per-page selector when provided. */
    routeName?: string;
    /** Other active query params (search/status/filters) to preserve when changing per-page. */
    filters?: Record<string, string | number | undefined | null>;
}

export function Pagination<T>({ data, routeName, filters = {} }: PaginationProps<T>) {
    if (data.total === 0) return null;

    function handlePerPageChange(value: string | null) {
        if (!value || !routeName) return;
        router.get(route(routeName), { ...filters, per_page: value }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex flex-wrap items-center gap-3">
                <p className="text-sm text-muted-foreground">
                    {data.from !== null && data.to !== null
                        ? `Menampilkan ${data.from}–${data.to} dari ${data.total}`
                        : `Total ${data.total}`}
                </p>
                {routeName && (
                    <div className="flex items-center gap-1.5">
                        <span className="text-sm text-muted-foreground">per halaman</span>
                        <Select value={String(data.per_page)} onValueChange={handlePerPageChange}>
                            <SelectTrigger className="h-8 w-[68px]">
                                <SelectValue>{(v: string) => v}</SelectValue>
                            </SelectTrigger>
                            <SelectContent>
                                {PER_PAGE_OPTIONS.map((n) => (
                                    <SelectItem key={n} value={String(n)}>{n}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                )}
            </div>
            {data.last_page > 1 && (
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
            )}
        </div>
    );
}
