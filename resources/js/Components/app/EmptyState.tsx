import { ReactNode } from 'react';
import { Inbox, type LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

interface EmptyStateProps {
    title: string;
    description?: string;
    icon?: LucideIcon;
    action?: ReactNode;
    className?: string;
}

export default function EmptyState({
    title,
    description,
    icon: Icon = Inbox,
    action,
    className,
}: EmptyStateProps) {
    return (
        <div className={cn('flex flex-col items-center justify-center py-16 text-center', className)}>
            <div className="rounded-full bg-muted p-4">
                <Icon className="h-8 w-8 text-muted-foreground" />
            </div>
            <h3 className="mt-4 text-sm font-semibold">{title}</h3>
            {description && (
                <p className="mt-1 text-sm text-muted-foreground max-w-xs">{description}</p>
            )}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
