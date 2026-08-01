import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';
import { ArrowLeft, Ban, ShieldCheck, UserCog } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Button, buttonVariants } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import {
    Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';

interface UserDetail {
    id: string;
    name: string;
    email: string;
    role: 'super_admin' | 'admin' | 'user';
    is_banned: boolean;
    ban_reason: string | null;
    banned_at: string | null;
    created_at: string;
}

interface Props extends PageProps {
    user: UserDetail;
    collections_count: number;
    can: { ban: boolean; changeRole: boolean };
}

const banSchema = z.object({
    ban_reason: z.string().min(1).max(500),
});
type BanFormValues = z.infer<typeof banSchema>;

function FieldError({ message }: { message?: string }) {
    if (!message) return null;
    return <p className="text-sm text-destructive">{message}</p>;
}

export default function UserShow({ user, collections_count, can }: Props) {
    const { t } = useTranslation('admin');

    const ROLE_LABELS: Record<string, string> = {
        super_admin: t('common:settings.roles.super_admin'),
        admin:       t('common:settings.roles.admin'),
        user:        t('common:settings.roles.user'),
    };

    const [banOpen, setBanOpen]           = useState(false);
    const [unbanOpen, setUnbanOpen]       = useState(false);
    const [roleOpen, setRoleOpen]         = useState(false);
    const [banning, setBanning]           = useState(false);
    const [unbanning, setUnbanning]       = useState(false);
    const [changingRole, setChangingRole] = useState(false);
    const [selectedRole, setSelectedRole] = useState<string>(user.role);

    const {
        register,
        handleSubmit,
        reset,
        formState: { errors },
    } = useForm<BanFormValues>({ resolver: zodResolver(banSchema) });

    function handleBan(values: BanFormValues) {
        setBanning(true);
        router.patch(route('admin.users.ban', user.id), values, {
            onSuccess: () => { setBanOpen(false); reset(); },
            onFinish:  () => setBanning(false),
        });
    }

    function handleUnban() {
        setUnbanning(true);
        router.patch(route('admin.users.unban', user.id), {}, {
            onSuccess: () => setUnbanOpen(false),
            onFinish:  () => setUnbanning(false),
        });
    }

    function handleChangeRole() {
        setChangingRole(true);
        router.patch(route('admin.users.role', user.id), { role: selectedRole }, {
            onSuccess: () => setRoleOpen(false),
            onFinish:  () => setChangingRole(false),
        });
    }

    return (
        <AdminLayout
            header={
                <PageHeader
                    title={user.name}
                    breadcrumbs={[
                        { label: t('users.show.breadcrumb'), href: route('admin.users.index') },
                        { label: user.name },
                    ]}
                    actions={
                        <Link
                            href={route('admin.users.index')}
                            className={cn(buttonVariants({ variant: 'outline', size: 'sm' }))}
                        >
                            <ArrowLeft className="mr-1.5 h-4 w-4" />
                            {t('users.show.back')}
                        </Link>
                    }
                />
            }
        >
            <Head title={user.name} />
            <div className="grid gap-6 lg:grid-cols-3">
                {/* Info */}
                <div className="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">{t('users.show.accountInfoTitle')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-xs text-muted-foreground">{t('users.show.name')}</p>
                                    <p className="font-medium">{user.name}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">{t('users.show.email')}</p>
                                    <p className="font-medium">{user.email}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">{t('users.show.role')}</p>
                                    <p className="font-medium">{ROLE_LABELS[user.role] ?? user.role}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">{t('users.show.joined')}</p>
                                    <p className="font-medium">{user.created_at}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">{t('users.show.status')}</p>
                                    {user.is_banned
                                        ? <Badge variant="destructive">{t('users.statusBanned')}</Badge>
                                        : <Badge variant="outline">{t('users.statusActive')}</Badge>}
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">{t('users.show.collections')}</p>
                                    <p className="font-medium">{t('users.show.collectionsCount', { count: collections_count })}</p>
                                </div>
                            </div>

                            {user.is_banned && user.ban_reason && (
                                <div className="rounded-md bg-destructive/10 px-3 py-2">
                                    <p className="text-xs font-medium text-destructive">{t('users.show.banReasonTitle')}</p>
                                    <p className="mt-0.5 text-sm">{user.ban_reason}</p>
                                    {user.banned_at && (
                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                            {t('users.show.bannedAt', { date: user.banned_at })}
                                        </p>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Actions */}
                <div className="space-y-3">
                    {can.ban && !user.is_banned && (
                        <Button
                            variant="destructive"
                            className="w-full"
                            onClick={() => setBanOpen(true)}
                        >
                            <Ban className="mr-2 h-4 w-4" />
                            {t('users.show.banUser')}
                        </Button>
                    )}

                    {can.ban && user.is_banned && (
                        <Button
                            variant="outline"
                            className="w-full"
                            onClick={() => setUnbanOpen(true)}
                        >
                            <ShieldCheck className="mr-2 h-4 w-4" />
                            {t('users.show.unbanUser')}
                        </Button>
                    )}

                    {can.changeRole && (
                        <Button
                            variant="outline"
                            className="w-full"
                            onClick={() => setRoleOpen(true)}
                        >
                            <UserCog className="mr-2 h-4 w-4" />
                            {t('users.show.changeRole')}
                        </Button>
                    )}
                </div>
            </div>

            {/* Ban Dialog */}
            <Dialog open={banOpen} onOpenChange={(open) => { setBanOpen(open); if (!open) reset(); }}>
                <DialogContent>
                    <DialogHeader><DialogTitle>{t('users.show.banDialogTitle', { name: user.name })}</DialogTitle></DialogHeader>
                    <form onSubmit={handleSubmit(handleBan)} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="ban_reason">{t('users.show.banReasonLabel')} <span className="text-destructive">*</span></Label>
                            <Textarea
                                id="ban_reason"
                                rows={3}
                                placeholder={t('users.show.banReasonPlaceholder')}
                                {...register('ban_reason')}
                            />
                            <FieldError message={errors.ban_reason ? t('common:common.required') : undefined} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setBanOpen(false)}>{t('common:common.cancel')}</Button>
                            <Button type="submit" variant="destructive" disabled={banning}>
                                {banning ? t('users.show.processing') : t('users.show.banAction')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Unban Dialog */}
            <Dialog open={unbanOpen} onOpenChange={setUnbanOpen}>
                <DialogContent>
                    <DialogHeader><DialogTitle>{t('users.show.unbanDialogTitle', { name: user.name })}</DialogTitle></DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        {t('users.show.unbanDialogDescription')}
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setUnbanOpen(false)}>{t('common:common.cancel')}</Button>
                        <Button disabled={unbanning} onClick={handleUnban}>
                            {unbanning ? t('users.show.processing') : t('users.show.unbanUser')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Change Role Dialog */}
            <Dialog open={roleOpen} onOpenChange={setRoleOpen}>
                <DialogContent>
                    <DialogHeader><DialogTitle>{t('users.show.changeRoleDialogTitle', { name: user.name })}</DialogTitle></DialogHeader>
                    <div className="space-y-3">
                        <p className="text-sm text-muted-foreground">
                            {t('users.show.currentRole')} <strong>{ROLE_LABELS[user.role]}</strong>
                        </p>
                        <div className="space-y-1.5">
                            <Label>{t('users.show.newRole')}</Label>
                            <Select value={selectedRole} onValueChange={(v) => v !== null && setSelectedRole(v)}>
                                <SelectTrigger>
                                    <SelectValue>
                                        {(value: string) => ROLE_LABELS[value] ?? value}
                                    </SelectValue>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="user">{ROLE_LABELS.user}</SelectItem>
                                    <SelectItem value="admin">{ROLE_LABELS.admin}</SelectItem>
                                    <SelectItem value="super_admin">{ROLE_LABELS.super_admin}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setRoleOpen(false)}>{t('common:common.cancel')}</Button>
                        <Button
                            disabled={changingRole || selectedRole === user.role}
                            onClick={handleChangeRole}
                        >
                            {changingRole ? t('common:common.saving') : t('common:common.save')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
