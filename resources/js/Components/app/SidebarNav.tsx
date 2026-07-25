import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronDown, type LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { type MenuItem } from '@/types';

interface NavLinkProps {
    item: MenuItem;
    iconMap: Record<string, LucideIcon>;
    onClick?: () => void;
    indent?: boolean;
}

function NavLink({ item, iconMap, onClick, indent }: NavLinkProps) {
    const Icon = item.icon ? (iconMap[item.icon] ?? null) : null;
    const isActive = !!item.route_name && route().current(item.route_name);
    const href = item.route_name && route().has(item.route_name) ? route(item.route_name) : '#';

    return (
        <Link
            href={href}
            onClick={onClick}
            className={cn(
                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                indent && 'pl-9',
                isActive
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
            )}
        >
            {Icon && <Icon className="h-4 w-4 shrink-0" />}
            <span>{item.label}</span>
            {item.is_maintenance && (
                <span className="ml-auto text-[10px] text-yellow-500">●</span>
            )}
        </Link>
    );
}

interface CategoryGroupProps {
    item: MenuItem;
    items: MenuItem[];
    iconMap: Record<string, LucideIcon>;
    onNavClick?: () => void;
}

function CategoryGroup({ item, items, iconMap, onNavClick }: CategoryGroupProps) {
    const hasActiveChild = items.some((c) => c.route_name && route().current(c.route_name));
    const [open, setOpen] = useState(hasActiveChild);
    const Icon = item.icon ? (iconMap[item.icon] ?? null) : null;

    return (
        <div>
            <button
                type="button"
                onClick={() => setOpen((o) => !o)}
                className="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
            >
                {Icon && <Icon className="h-4 w-4 shrink-0" />}
                <span className="flex-1 text-left">{item.label}</span>
                <ChevronDown className={cn('h-3.5 w-3.5 shrink-0 transition-transform', open && 'rotate-180')} />
            </button>
            {open && (
                <div className="mt-0.5 space-y-0.5">
                    {items.map((c) => (
                        <NavLink key={c.key} item={c} iconMap={iconMap} onClick={onNavClick} indent />
                    ))}
                </div>
            )}
        </div>
    );
}

interface SidebarNavProps {
    menus: MenuItem[];
    iconMap: Record<string, LucideIcon>;
    onNavClick?: () => void;
}

export function SidebarNav({ menus, iconMap, onNavClick }: SidebarNavProps) {
    const topLevel = menus.filter((m) => !m.parent_key);
    const childrenByParent = new Map<string, MenuItem[]>();
    menus.filter((m) => m.parent_key).forEach((m) => {
        const arr = childrenByParent.get(m.parent_key!) ?? [];
        arr.push(m);
        childrenByParent.set(m.parent_key!, arr);
    });

    return (
        <div className="space-y-0.5">
            {topLevel.map((item) => {
                const children = childrenByParent.get(item.key);
                if (children && children.length > 0) {
                    return (
                        <CategoryGroup
                            key={item.key}
                            item={item}
                            items={children}
                            iconMap={iconMap}
                            onNavClick={onNavClick}
                        />
                    );
                }
                return <NavLink key={item.key} item={item} iconMap={iconMap} onClick={onNavClick} />;
            })}
        </div>
    );
}
