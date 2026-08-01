import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';
import { ArrowLeft } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import { Switch } from '@/Components/ui/switch';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/Components/ui/table';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';

interface MenuDetail {
    id: string;
    key: string;
    label: string;
    icon: string | null;
    route_name: string | null;
    sort_order: number;
    is_visible: boolean;
    is_maintenance: boolean;
    maintenance_message: string | null;
    role_access: string[];
}

interface Props extends PageProps {
    menu: MenuDetail;
}

const schema = z.object({
    label:               z.string().min(1).max(100),
    icon:                z.string().max(50).optional(),
    is_visible:          z.boolean(),
    is_maintenance:      z.boolean(),
    maintenance_message: z.string().max(500).optional(),
});

type FormValues = z.infer<typeof schema>;

const ALL_ROLES = ['super_admin', 'admin', 'user'] as const;

export default function MenuEdit({ menu }: Props) {
    const { t } = useTranslation('admin');

    const ROLE_LABELS: Record<string, string> = {
        super_admin: t('common:settings.roles.super_admin'),
        admin:       t('common:settings.roles.admin'),
        user:        t('common:settings.roles.user'),
    };

    const [submitting, setSubmitting] = useState(false);
    const [roleAccess, setRoleAccess] = useState<string[]>(menu.role_access);

    const {
        register,
        handleSubmit,
        watch,
        setValue,
        setError,
        formState: { errors },
    } = useForm<FormValues>({
        resolver: zodResolver(schema),
        defaultValues: {
            label:               menu.label,
            icon:                menu.icon ?? '',
            is_visible:          menu.is_visible,
            is_maintenance:      menu.is_maintenance,
            maintenance_message: menu.maintenance_message ?? '',
        },
    });

    const isVisible     = watch('is_visible');
    const isMaintenance = watch('is_maintenance');

    function toggleRole(role: string) {
        setRoleAccess((prev) =>
            prev.includes(role) ? prev.filter((r) => r !== role) : [...prev, role],
        );
    }

    function onSubmit(values: FormValues) {
        setSubmitting(true);
        router.put(route('admin.menus.update', menu.id), {
            ...values,
            role_access: roleAccess,
        }, {
            onError: (errs) => {
                Object.entries(errs).forEach(([k, msg]) => {
                    setError(k as keyof FormValues, { message: msg });
                });
            },
            onFinish: () => setSubmitting(false),
        });
    }

    return (
        <AdminLayout
            header={
                <PageHeader
                    title={t('menus.title')}
                    description={t('menus.description')}
                    actions={
                        <Link
                            href={route('admin.menus.index')}
                            className={cn(buttonVariants({ variant: 'outline', size: 'sm' }))}
                        >
                            <ArrowLeft className="mr-1.5 h-4 w-4" />
                            {t('menus.back')}
                        </Link>
                    }
                />
            }
        >
            <Head title={menu.label} />
            <form onSubmit={handleSubmit(onSubmit)}>
                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('menus.table.label')}</TableHead>
                                <TableHead>{t('menus.table.route')}</TableHead>
                                <TableHead>{t('menus.table.roleAccess')}</TableHead>
                                <TableHead className="w-28 text-center">{t('menus.table.visible')}</TableHead>
                                <TableHead className="w-32 text-center">{t('menus.table.maintenance')}</TableHead>
                                <TableHead className="w-16" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {/* Edit row — same layout as Index but cells are inputs */}
                            <TableRow className="align-top">
                                <TableCell className="pt-3 space-y-1.5">
                                    <Input
                                        className="h-7 text-sm font-medium"
                                        placeholder={t('menus.labelPlaceholder')}
                                        {...register('label')}
                                    />
                                    {errors.label && (
                                        <p className="text-xs text-destructive">{t('common:common.required')}</p>
                                    )}
                                    <Input
                                        className="h-6 text-xs text-muted-foreground"
                                        placeholder={t('menus.iconPlaceholder')}
                                        {...register('icon')}
                                    />
                                </TableCell>

                                <TableCell className="pt-4 text-xs text-muted-foreground font-mono">
                                    {menu.route_name ?? '—'}
                                </TableCell>

                                <TableCell className="pt-3">
                                    <div className="flex flex-wrap gap-1">
                                        {ALL_ROLES.map((role) => {
                                            const active = roleAccess.includes(role);
                                            return (
                                                <button
                                                    key={role}
                                                    type="button"
                                                    onClick={() => toggleRole(role)}
                                                >
                                                    <Badge
                                                        variant={active ? 'secondary' : 'outline'}
                                                        className={cn(
                                                            'cursor-pointer text-xs transition-opacity',
                                                            !active && 'opacity-40',
                                                        )}
                                                    >
                                                        {ROLE_LABELS[role]}
                                                    </Badge>
                                                </button>
                                            );
                                        })}
                                    </div>
                                    {roleAccess.length === 0 && (
                                        <p className="mt-1 text-xs text-destructive">{t('menus.selectAtLeastOneRole')}</p>
                                    )}
                                </TableCell>

                                <TableCell className="pt-3 text-center">
                                    <Switch
                                        checked={isVisible}
                                        onCheckedChange={(v) => setValue('is_visible', v)}
                                    />
                                </TableCell>

                                <TableCell className="pt-3 text-center">
                                    <Switch
                                        checked={isMaintenance}
                                        onCheckedChange={(v) => setValue('is_maintenance', v)}
                                    />
                                </TableCell>

                                <TableCell />
                            </TableRow>

                            {/* Expanded row for maintenance message */}
                            {isMaintenance && (
                                <TableRow>
                                    <TableCell colSpan={5} className="pb-3 pt-0">
                                        <p className="mb-1.5 text-xs font-medium text-muted-foreground">
                                            {t('menus.maintenanceMessageLabel')}
                                        </p>
                                        <Textarea
                                            rows={2}
                                            placeholder={t('menus.maintenanceMessagePlaceholder')}
                                            className="text-sm"
                                            {...register('maintenance_message')}
                                        />
                                        {errors.maintenance_message && (
                                            <p className="mt-1 text-xs text-destructive">
                                                {errors.maintenance_message.message}
                                            </p>
                                        )}
                                    </TableCell>
                                    <TableCell />
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                <div className="mt-4 flex gap-3">
                    <Button type="submit" disabled={submitting || roleAccess.length === 0}>
                        {submitting ? t('common:common.saving') : t('menus.saveChanges')}
                    </Button>
                    <Link
                        href={route('admin.menus.index')}
                        className={cn(buttonVariants({ variant: 'outline' }))}
                    >
                        {t('common:common.cancel')}
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}
