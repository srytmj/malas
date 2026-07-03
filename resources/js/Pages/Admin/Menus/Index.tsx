import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Menu as MenuIcon, Pencil } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import EmptyState from '@/Components/app/EmptyState';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Switch } from '@/Components/ui/switch';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';

interface MenuRow {
    id: string;
    key: string;
    label: string;
    icon: string | null;
    route_name: string | null;
    sort_order: number;
    is_visible: boolean;
    is_maintenance: boolean;
    role_access: string[];
}

interface Props extends PageProps {
    rows: MenuRow[];
}

const ROLE_LABELS: Record<string, string> = {
    super_admin: 'Super Admin',
    admin:       'Admin',
    user:        'User',
};

export default function MenusIndex({ rows }: Props) {
    const [togglingId, setTogglingId] = useState<string | null>(null);

    function toggle(menu: MenuRow, field: 'is_visible' | 'is_maintenance') {
        const key = `${menu.id}-${field}`;
        setTogglingId(key);
        router.patch(route('admin.menus.update', menu.id), {
            [field]: !menu[field],
        }, {
            preserveScroll: true,
            onFinish: () => setTogglingId(null),
        });
    }

    return (
        <AdminLayout
            header={
                <PageHeader
                    title="Manajemen Menu"
                    description="Kelola visibilitas, maintenance mode, dan akses menu."
                />
            }
        >
            <Head title="Menu" />
            {rows.length === 0 ? (
                <EmptyState
                    title="Tidak ada menu"
                    description="Belum ada menu yang terdaftar di sistem."
                    icon={MenuIcon}
                />
            ) : (
                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-8">#</TableHead>
                                <TableHead>Label</TableHead>
                                <TableHead>Route</TableHead>
                                <TableHead>Akses Role</TableHead>
                                <TableHead className="w-28 text-center">Tampil</TableHead>
                                <TableHead className="w-32 text-center">Maintenance</TableHead>
                                <TableHead className="w-16" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.map((m) => (
                                <TableRow key={m.id}>
                                    <TableCell className="text-xs text-muted-foreground">{m.sort_order}</TableCell>
                                    <TableCell className="font-medium">{m.label}</TableCell>
                                    <TableCell className="text-xs text-muted-foreground font-mono">
                                        {m.route_name ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex flex-wrap gap-1">
                                            {m.role_access.map((r) => (
                                                <Badge key={r} variant="outline" className="text-xs">
                                                    {ROLE_LABELS[r] ?? r}
                                                </Badge>
                                            ))}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <Switch
                                            checked={m.is_visible}
                                            disabled={togglingId === `${m.id}-is_visible`}
                                            onCheckedChange={() => toggle(m, 'is_visible')}
                                        />
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <Switch
                                            checked={m.is_maintenance}
                                            disabled={togglingId === `${m.id}-is_maintenance`}
                                            onCheckedChange={() => toggle(m, 'is_maintenance')}
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <Link
                                            href={route('admin.menus.edit', m.id)}
                                            className={cn(buttonVariants({ variant: 'ghost', size: 'icon' }), 'h-8 w-8')}
                                        >
                                            <Pencil className="h-3.5 w-3.5" />
                                        </Link>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            )}
        </AdminLayout>
    );
}
