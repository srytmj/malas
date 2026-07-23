import { ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

interface BreadcrumbItem {
    label: string;
    href?: string;
}

interface PageHeaderProps {
    title: string;
    description?: string;
    breadcrumbs?: BreadcrumbItem[];
    actions?: ReactNode;
    className?: string;
}

export default function PageHeader({
    title,
    description,
    breadcrumbs,
    actions,
    className,
}: PageHeaderProps) {
    return (
        <div className={cn('flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between', className)}>
            <div className="min-w-0">
                {breadcrumbs && breadcrumbs.length > 0 && (
                    <nav className="mb-1 flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
                        {breadcrumbs.map((crumb, i) => (
                            <span key={i} className="flex items-center gap-1.5">
                                {i > 0 && <span>/</span>}
                                {crumb.href ? (
                                    <Link href={crumb.href} className="hover:text-foreground transition-colors">
                                        {crumb.label}
                                    </Link>
                                ) : (
                                    <span>{crumb.label}</span>
                                )}
                            </span>
                        ))}
                    </nav>
                )}
                <h1 className="truncate text-xl font-semibold">{title}</h1>
                {description && (
                    <p className="mt-0.5 text-sm text-muted-foreground">{description}</p>
                )}
            </div>
            {actions && <div className="sm:shrink-0">{actions}</div>}
        </div>
    );
}
