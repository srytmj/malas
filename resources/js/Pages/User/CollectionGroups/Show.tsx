import { useEffect, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import {
    BookOpen, Loader2, Pencil, Plus, Search, Trash2, X,
} from 'lucide-react';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import { PublicShell } from '@/Components/app/PublicShell';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle, EmptyDescription, EmptyContent } from '@/Components/ui/empty';
import { AdultBlurOverlay } from '@/Components/app/AdultBlurOverlay';
import { SeriesStatusBadge } from '@/Components/app/StatusBadge';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { cn } from '@/lib/utils';
import { useTypeFilterOptions } from '@/lib/typeFilters';
import { PageProps } from '@/types';
import { type PaginatedData, type SeriesStatus, type SeriesType } from '@/lib/types';

interface GroupItem {
    id: string;
    slug: string;
    title_romaji: string;
    title_english: string | null;
    cover_url: string | null;
    status: SeriesStatus;
    type: SeriesType;
    is_adult: boolean;
}

interface AvailableItem {
    id: string;
    title_romaji: string;
    cover_url: string | null;
    type: SeriesType;
}

interface Props extends PageProps {
    group: { id: string; slug: string; name: string; is_public: boolean };
    items: GroupItem[];
    available: PaginatedData<AvailableItem> | null;
    is_owner: boolean;
    is_guest: boolean;
    filters: { q: string; type: string };
}

export default function CollectionGroupShow({ group, items, available, is_owner, is_guest, filters }: Props) {
    const { t } = useTranslation('collection');
    const typeFilterOptions = useTypeFilterOptions();
    const Layout = is_guest ? PublicShell : UserLayout;
    const [renameOpen, setRenameOpen] = useState(false);
    const [name, setName] = useState(group.name);
    const [renaming, setRenaming] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const [addOpen, setAddOpen] = useState(false);
    const [search, setSearch] = useState(filters.q);
    const [typeFilter, setTypeFilter] = useState(filters.type);
    const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());
    const [adding, setAdding] = useState(false);
    const [removingId, setRemovingId] = useState<string | null>(null);
    const [removeConfirmItem, setRemoveConfirmItem] = useState<GroupItem | null>(null);
    const [savingVisibility, setSavingVisibility] = useState(false);
    const [loadingAvailable, setLoadingAvailable] = useState(false);
    const [loadingMore, setLoadingMore] = useState(false);
    const [accumulated, setAccumulated] = useState<AvailableItem[]>(available?.data ?? []);
    const skipNextFetch = useRef(true);
    const appendModeRef = useRef(false);

    useEffect(() => {
        if (skipNextFetch.current) {
            skipNextFetch.current = false;
            return;
        }

        setLoadingAvailable(true);
        const handle = setTimeout(() => {
            router.get(route('collection.groups.show', group.slug), { q: search, type: typeFilter }, {
                preserveState: true,
                preserveScroll: true,
                only: ['available'],
                onFinish: () => setLoadingAvailable(false),
            });
        }, 300);

        return () => clearTimeout(handle);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search, typeFilter]);

    useEffect(() => {
        if (!available) {
            setAccumulated([]);
            return;
        }

        if (appendModeRef.current) {
            setAccumulated((prev) => [...prev, ...available.data]);
            appendModeRef.current = false;
        } else {
            setAccumulated(available.data);
        }
        setLoadingMore(false);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [available]);

    function loadMore() {
        if (!available || loadingAvailable || loadingMore || available.current_page >= available.last_page) return;

        appendModeRef.current = true;
        setLoadingMore(true);
        router.get(route('collection.groups.show', group.slug), { q: search, type: typeFilter, page: available.current_page + 1 }, {
            preserveState: true,
            preserveScroll: true,
            only: ['available'],
        });
    }

    // Plain scroll-position check instead of IntersectionObserver — simpler, and doesn't depend
    // on the tab actively compositing frames (IntersectionObserver callbacks can be delayed or
    // skipped entirely for backgrounded/non-visible tabs).
    function handleAvailableScroll(e: React.UIEvent<HTMLDivElement>) {
        const el = e.currentTarget;
        if (el.scrollHeight - el.scrollTop - el.clientHeight < 300) {
            loadMore();
        }
    }

    function handleRename(e: React.FormEvent) {
        e.preventDefault();
        setRenaming(true);
        router.patch(route('collection.groups.update', group.slug), { name }, {
            onSuccess: () => setRenameOpen(false),
            onFinish: () => setRenaming(false),
        });
    }

    function handleDelete() {
        setDeleting(true);
        router.delete(route('collection.groups.destroy', group.slug), {
            onFinish: () => setDeleting(false),
        });
    }

    function handleToggleVisibility(checked: boolean) {
        setSavingVisibility(true);
        router.patch(route('collection.groups.visibility.update', group.slug), { is_public: checked }, {
            preserveScroll: true,
            onFinish: () => setSavingVisibility(false),
        });
    }

    function toggleSelect(id: string) {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            next.has(id) ? next.delete(id) : next.add(id);
            return next;
        });
    }

    function handleAdd() {
        if (selectedIds.size === 0) return;
        setAdding(true);
        router.post(route('collection.groups.items.add', group.slug), { collection_ids: Array.from(selectedIds) }, {
            onSuccess: () => { setAddOpen(false); setSelectedIds(new Set()); setSearch(''); setTypeFilter('all'); },
            onFinish: () => setAdding(false),
        });
    }

    function handleRemove(collectionId: string) {
        setRemovingId(collectionId);
        router.delete(route('collection.groups.items.remove', [group.slug, collectionId]), {
            preserveScroll: true,
            onFinish: () => setRemovingId(null),
        });
    }

    function handleConfirmRemove() {
        if (!removeConfirmItem) return;
        handleRemove(removeConfirmItem.id);
        setRemoveConfirmItem(null);
    }

    return (
        <Layout
            header={
                <PageHeader
                    title={group.name}
                    breadcrumbs={is_guest ? [{ label: group.name }] : [
                        { label: t('groups.index.title'), href: route('collection.groups.index') },
                        { label: group.name },
                    ]}
                    actions={is_owner ? (
                        <div className="flex flex-wrap items-center gap-2">
                            <Button variant="outline" size="sm" onClick={() => { setName(group.name); setRenameOpen(true); }}>
                                <Pencil className="mr-1.5 h-3.5 w-3.5" />
                                {t('groups.rename')}
                            </Button>
                            <Button variant="destructive" size="sm" onClick={() => setDeleteOpen(true)}>
                                <Trash2 className="mr-1.5 h-3.5 w-3.5" />
                                {t('groups.delete')}
                            </Button>
                            <Button size="sm" onClick={() => setAddOpen(true)}>
                                <Plus className="mr-1.5 h-3.5 w-3.5" />
                                {t('groups.addManga')}
                            </Button>
                        </div>
                    ) : (
                        <Badge variant="outline">
                            {group.is_public ? t('groups.visibility.public') : t('groups.visibility.private')}
                        </Badge>
                    )}
                />
            }
        >
            <Head title={group.name} />

            {is_owner && (
                <div className="mb-6 flex items-center justify-between rounded-lg border p-4">
                    <div>
                        <p className="text-sm font-medium">
                            {group.is_public ? t('groups.visibility.public') : t('groups.visibility.private')}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            {group.is_public ? t('groups.visibility.publicHint') : t('groups.visibility.privateHint')}
                        </p>
                    </div>
                    <Switch
                        checked={group.is_public}
                        onCheckedChange={handleToggleVisibility}
                        disabled={savingVisibility}
                    />
                </div>
            )}

            {items.length === 0 ? (
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <BookOpen />
                        </EmptyMedia>
                        <EmptyTitle>{t('groups.show.empty.title')}</EmptyTitle>
                        <EmptyDescription>{t('groups.show.empty.description')}</EmptyDescription>
                    </EmptyHeader>
                    {is_owner && (
                        <EmptyContent>
                            <Button onClick={() => setAddOpen(true)}>
                                <Plus className="mr-1.5 h-4 w-4" />
                                {t('groups.addManga')}
                            </Button>
                        </EmptyContent>
                    )}
                </Empty>
            ) : (
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                    {items.map((item) => (
                        <div key={item.id} className="group flex flex-col overflow-hidden rounded-lg border bg-card">
                            <div className="relative">
                                <Link href={route('collection.show', item.slug)}>
                                    <AdultBlurOverlay isAdult={item.is_adult} className="aspect-[2/3] w-full overflow-hidden bg-muted">
                                        {item.cover_url ? (
                                            <img
                                                src={item.cover_url}
                                                alt={item.title_romaji}
                                                className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                            />
                                        ) : (
                                            <div className="flex h-full items-center justify-center">
                                                <BookOpen className="h-10 w-10 text-muted-foreground/40" />
                                            </div>
                                        )}
                                    </AdultBlurOverlay>
                                </Link>
                                {is_owner && (
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        size="icon"
                                        className="absolute right-1.5 top-1.5 h-7 w-7"
                                        disabled={removingId === item.id}
                                        onClick={() => setRemoveConfirmItem(item)}
                                        aria-label={t('groups.removeManga')}
                                    >
                                        {removingId === item.id ? <Loader2 className="h-3.5 w-3.5 animate-spin" /> : <X className="h-3.5 w-3.5" />}
                                    </Button>
                                )}
                            </div>
                            <div className="flex flex-col gap-1 p-2">
                                <p className="line-clamp-2 text-sm font-medium leading-tight">{item.title_romaji}</p>
                                <SeriesStatusBadge status={item.status} />
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {is_owner && (
                <>
                    {/* Rename Dialog */}
                    <Dialog open={renameOpen} onOpenChange={setRenameOpen}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{t('groups.renameDialog.title')}</DialogTitle>
                            </DialogHeader>
                            <form onSubmit={handleRename} className="space-y-4">
                                <div className="space-y-1.5">
                                    <Label htmlFor="rename-group">{t('groups.nameLabel')}</Label>
                                    <Input
                                        id="rename-group"
                                        value={name}
                                        onChange={(e) => setName(e.target.value)}
                                        autoFocus
                                        required
                                        maxLength={100}
                                    />
                                </div>
                                <DialogFooter>
                                    <Button type="button" variant="outline" onClick={() => setRenameOpen(false)}>
                                        {t('groups.createDialog.cancel')}
                                    </Button>
                                    <Button type="submit" disabled={renaming || !name.trim()}>
                                        {renaming ? t('groups.renameDialog.saving') : t('groups.renameDialog.save')}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>

                    {/* Delete Dialog */}
                    <Dialog open={deleteOpen} onOpenChange={setDeleteOpen}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{t('groups.deleteDialog.title')}</DialogTitle>
                                <DialogDescription>
                                    {t('groups.deleteDialog.confirmPrefix')} <strong>{group.name}</strong> {t('groups.deleteDialog.confirmSuffix')}
                                </DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <Button variant="outline" onClick={() => setDeleteOpen(false)}>{t('groups.createDialog.cancel')}</Button>
                                <Button variant="destructive" disabled={deleting} onClick={handleDelete}>
                                    {deleting ? t('groups.deleteDialog.deleting') : t('groups.deleteDialog.delete')}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    {/* Remove Item Dialog */}
                    <Dialog open={!!removeConfirmItem} onOpenChange={(open) => { if (!open) setRemoveConfirmItem(null); }}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{t('groups.removeDialog.title')}</DialogTitle>
                                <DialogDescription>
                                    {t('groups.removeDialog.confirmPrefix')} <strong>{removeConfirmItem?.title_romaji}</strong> {t('groups.removeDialog.confirmSuffix')}
                                </DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <Button variant="outline" onClick={() => setRemoveConfirmItem(null)}>{t('groups.createDialog.cancel')}</Button>
                                <Button variant="destructive" disabled={removingId === removeConfirmItem?.id} onClick={handleConfirmRemove}>
                                    {removingId === removeConfirmItem?.id ? t('groups.removeDialog.removing') : t('groups.removeDialog.remove')}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    {/* Add Manga Dialog */}
                    <Dialog
                        open={addOpen}
                        onOpenChange={(open) => {
                            setAddOpen(open);
                            if (!open) { setSelectedIds(new Set()); setSearch(''); setTypeFilter('all'); }
                        }}
                    >
                        <DialogContent className="flex max-h-[85vh] w-full max-w-7xl flex-col sm:max-w-7xl">
                            <DialogHeader className="shrink-0">
                                <DialogTitle>{t('groups.addDialog.title')}</DialogTitle>
                                <DialogDescription>{t('groups.addDialog.description')}</DialogDescription>
                            </DialogHeader>

                            <div className="flex shrink-0 flex-col gap-3 sm:flex-row sm:items-center">
                                <div className="relative flex-1">
                                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        className="pl-9"
                                        placeholder={t('groups.addDialog.searchPlaceholder')}
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        autoFocus
                                    />
                                </div>
                            </div>

                            <div className="shrink-0 overflow-x-auto">
                                <ToggleGroup
                                    value={[typeFilter]}
                                    onValueChange={(vals) => setTypeFilter(vals[0] ?? 'all')}
                                    variant="outline"
                                    size="sm"
                                >
                                    {typeFilterOptions.map((opt) => (
                                        <ToggleGroupItem key={opt.value} value={opt.value}>
                                            {opt.label}
                                        </ToggleGroupItem>
                                    ))}
                                </ToggleGroup>
                            </div>

                            <div className="min-h-0 flex-1 overflow-y-auto" onScroll={handleAvailableScroll}>
                                {loadingAvailable ? (
                                    <div className="flex h-full items-center justify-center py-16">
                                        <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                                    </div>
                                ) : accumulated.length === 0 ? (
                                    <p className="py-8 text-center text-sm text-muted-foreground">
                                        {available && available.total === 0 && !search && typeFilter === 'all'
                                            ? t('groups.addDialog.noneAvailable')
                                            : t('groups.addDialog.noResults')}
                                    </p>
                                ) : (
                                    <div className="grid grid-cols-3 gap-2 pb-1 sm:grid-cols-5 lg:grid-cols-8">
                                        {accumulated.map((s) => {
                                            const isSelected = selectedIds.has(s.id);
                                            return (
                                                <button
                                                    key={s.id}
                                                    type="button"
                                                    onClick={() => toggleSelect(s.id)}
                                                    className={cn(
                                                        'group relative flex flex-col overflow-hidden rounded-lg border text-left transition-all focus:outline-none focus:ring-2 focus:ring-ring',
                                                        isSelected && 'ring-2 ring-primary border-primary',
                                                        !isSelected && 'hover:ring-2 hover:ring-primary/50',
                                                    )}
                                                >
                                                    <div className="relative aspect-[2/3] overflow-hidden bg-muted">
                                                        {s.cover_url ? (
                                                            <img src={s.cover_url} alt={s.title_romaji} className="h-full w-full object-cover" />
                                                        ) : (
                                                            <div className="flex h-full items-center justify-center">
                                                                <BookOpen className="h-6 w-6 text-muted-foreground/30" />
                                                            </div>
                                                        )}
                                                        {isSelected && (
                                                            <div className="absolute inset-0 flex items-center justify-center bg-primary/30">
                                                                <div className="rounded-full bg-primary p-1.5">
                                                                    <svg className="h-4 w-4 text-primary-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
                                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                        )}
                                                    </div>
                                                    <div className="p-1.5">
                                                        <p className="line-clamp-2 text-xs font-medium leading-tight">{s.title_romaji}</p>
                                                    </div>
                                                </button>
                                            );
                                        })}
                                    </div>
                                )}

                                {loadingMore && (
                                    <div className="flex items-center justify-center py-4">
                                        <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
                                    </div>
                                )}
                            </div>

                            <DialogFooter className="shrink-0 items-center gap-2">
                                {selectedIds.size > 0 && (
                                    <p className="mr-auto text-sm text-muted-foreground">{t('groups.addDialog.selectedCount', { count: selectedIds.size })}</p>
                                )}
                                <Button variant="outline" onClick={() => setAddOpen(false)}>{t('groups.createDialog.cancel')}</Button>
                                <Button disabled={selectedIds.size === 0 || adding} onClick={handleAdd}>
                                    {adding ? t('groups.addDialog.adding') : t('groups.addDialog.add')}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </>
            )}
        </Layout>
    );
}
