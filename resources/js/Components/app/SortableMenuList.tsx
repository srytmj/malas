import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { GripVertical, Pencil } from 'lucide-react';
import {
    DndContext, closestCenter, PointerSensor, useSensor, useSensors, type DragEndEvent,
} from '@dnd-kit/core';
import {
    SortableContext, verticalListSortingStrategy, useSortable, arrayMove,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Badge } from '@/Components/ui/badge';
import { Switch } from '@/Components/ui/switch';
import { buttonVariants } from '@/Components/ui/button';
import { cn } from '@/lib/utils';

export interface SortableMenuRow {
    id: string;
    key: string;
    label: string;
    icon: string | null;
    route_name: string | null;
    sort_order: number;
    is_visible: boolean;
    is_maintenance: boolean;
    role_access: string[];
    is_shared: boolean;
}

interface RowProps {
    item: SortableMenuRow;
    togglingId: string | null;
    onToggle: (item: SortableMenuRow, field: 'is_visible' | 'is_maintenance') => void;
}

function SortableRow({ item, togglingId, onToggle }: RowProps) {
    const { t } = useTranslation();

    const ROLE_LABELS: Record<string, string> = {
        super_admin: t('settings.roles.super_admin'),
        admin:       t('settings.roles.admin'),
        user:        t('settings.roles.user'),
    };

    const {
        attributes, listeners, setNodeRef, transform, transition, isDragging,
    } = useSortable({ id: item.id });

    return (
        <div
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            className={cn(
                'flex items-center gap-3 border-b bg-background px-3 py-2 last:border-0',
                isDragging && 'z-10 opacity-70 shadow-md',
            )}
        >
            <button
                type="button"
                {...attributes}
                {...listeners}
                className="cursor-grab touch-none text-muted-foreground active:cursor-grabbing"
                aria-label={t('components.sortableMenuList.dragToReorder')}
            >
                <GripVertical className="h-4 w-4" />
            </button>

            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium">
                    {item.label}
                    {item.is_shared && (
                        <Badge variant="outline" className="ml-1.5 align-middle text-[10px]">{t('components.sortableMenuList.shared')}</Badge>
                    )}
                </p>
                <p className="truncate font-mono text-xs text-muted-foreground">{item.route_name ?? '—'}</p>
            </div>

            <div className="hidden flex-wrap gap-1 sm:flex">
                {item.role_access.map((r) => (
                    <Badge key={r} variant="outline" className="text-xs">{ROLE_LABELS[r] ?? r}</Badge>
                ))}
            </div>

            <Switch
                checked={item.is_visible}
                disabled={togglingId === `${item.id}-is_visible`}
                onCheckedChange={() => onToggle(item, 'is_visible')}
                aria-label={t('components.sortableMenuList.visible')}
            />
            <Switch
                checked={item.is_maintenance}
                disabled={togglingId === `${item.id}-is_maintenance`}
                onCheckedChange={() => onToggle(item, 'is_maintenance')}
                aria-label={t('components.sortableMenuList.maintenance')}
            />

            <Link
                href={route('admin.menus.edit', item.id)}
                className={cn(buttonVariants({ variant: 'ghost', size: 'icon-sm' }))}
            >
                <Pencil className="h-3.5 w-3.5" />
            </Link>
        </div>
    );
}

interface SortableMenuListProps {
    title: string;
    description?: string;
    items: SortableMenuRow[];
}

export function SortableMenuList({ title, description, items: initialItems }: SortableMenuListProps) {
    const [items, setItems] = useState(initialItems);
    const [togglingId, setTogglingId] = useState<string | null>(null);
    const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 4 } }));

    function handleDragEnd(event: DragEndEvent) {
        const { active, over } = event;
        if (!over || active.id === over.id) return;

        const oldIndex = items.findIndex((i) => i.id === active.id);
        const newIndex = items.findIndex((i) => i.id === over.id);
        const reordered = arrayMove(items, oldIndex, newIndex);
        setItems(reordered);

        router.patch(route('admin.menus.reorder'), {
            items: reordered.map((item, index) => ({ id: item.id, sort_order: index + 1 })),
        }, { preserveScroll: true, preserveState: true });
    }

    function toggle(item: SortableMenuRow, field: 'is_visible' | 'is_maintenance') {
        const key = `${item.id}-${field}`;
        setTogglingId(key);
        setItems((prev) => prev.map((i) => (i.id === item.id ? { ...i, [field]: !i[field] } : i)));
        router.patch(route('admin.menus.update', item.id), {
            [field]: !item[field],
        }, {
            preserveScroll: true,
            onFinish: () => setTogglingId(null),
        });
    }

    if (items.length === 0) return null;

    return (
        <div className="mb-6">
            <div className="mb-2">
                <h2 className="text-sm font-semibold">{title}</h2>
                {description && <p className="text-xs text-muted-foreground">{description}</p>}
            </div>
            <div className="overflow-hidden rounded-lg border">
                <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                    <SortableContext items={items.map((i) => i.id)} strategy={verticalListSortingStrategy}>
                        {items.map((item) => (
                            <SortableRow key={item.id} item={item} togglingId={togglingId} onToggle={toggle} />
                        ))}
                    </SortableContext>
                </DndContext>
            </div>
        </div>
    );
}
