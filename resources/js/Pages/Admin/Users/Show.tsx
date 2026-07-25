import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
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

const ROLE_LABELS: Record<string, string> = {
    super_admin: 'Super Admin',
    admin:       'Admin',
    user:        'User',
};

const banSchema = z.object({
    ban_reason: z.string().min(1, 'Wajib diisi').max(500),
});
type BanFormValues = z.infer<typeof banSchema>;

function FieldError({ message }: { message?: string }) {
    if (!message) return null;
    return <p className="text-sm text-destructive">{message}</p>;
}

export default function UserShow({ user, collections_count, can }: Props) {
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
                        { label: 'Pengguna', href: route('admin.users.index') },
                        { label: user.name },
                    ]}
                    actions={
                        <Link
                            href={route('admin.users.index')}
                            className={cn(buttonVariants({ variant: 'outline', size: 'sm' }))}
                        >
                            <ArrowLeft className="mr-1.5 h-4 w-4" />
                            Kembali
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
                            <CardTitle className="text-base">Informasi Akun</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-xs text-muted-foreground">Nama</p>
                                    <p className="font-medium">{user.name}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">Email</p>
                                    <p className="font-medium">{user.email}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">Role</p>
                                    <p className="font-medium">{ROLE_LABELS[user.role] ?? user.role}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">Bergabung</p>
                                    <p className="font-medium">{user.created_at}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">Status</p>
                                    {user.is_banned
                                        ? <Badge variant="destructive">Di-ban</Badge>
                                        : <Badge variant="outline">Aktif</Badge>}
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">Koleksi</p>
                                    <p className="font-medium">{collections_count} series</p>
                                </div>
                            </div>

                            {user.is_banned && user.ban_reason && (
                                <div className="rounded-md bg-destructive/10 px-3 py-2">
                                    <p className="text-xs font-medium text-destructive">Alasan ban</p>
                                    <p className="mt-0.5 text-sm">{user.ban_reason}</p>
                                    {user.banned_at && (
                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                            Di-ban pada {user.banned_at}
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
                            Ban Pengguna
                        </Button>
                    )}

                    {can.ban && user.is_banned && (
                        <Button
                            variant="outline"
                            className="w-full"
                            onClick={() => setUnbanOpen(true)}
                        >
                            <ShieldCheck className="mr-2 h-4 w-4" />
                            Cabut Ban
                        </Button>
                    )}

                    {can.changeRole && (
                        <Button
                            variant="outline"
                            className="w-full"
                            onClick={() => setRoleOpen(true)}
                        >
                            <UserCog className="mr-2 h-4 w-4" />
                            Ganti Role
                        </Button>
                    )}
                </div>
            </div>

            {/* Ban Dialog */}
            <Dialog open={banOpen} onOpenChange={(open) => { setBanOpen(open); if (!open) reset(); }}>
                <DialogContent>
                    <DialogHeader><DialogTitle>Ban {user.name}</DialogTitle></DialogHeader>
                    <form onSubmit={handleSubmit(handleBan)} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="ban_reason">Alasan ban <span className="text-destructive">*</span></Label>
                            <Textarea
                                id="ban_reason"
                                rows={3}
                                placeholder="Jelaskan alasan pemblokiran akun ini..."
                                {...register('ban_reason')}
                            />
                            <FieldError message={errors.ban_reason?.message} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setBanOpen(false)}>Batal</Button>
                            <Button type="submit" variant="destructive" disabled={banning}>
                                {banning ? 'Memproses...' : 'Ban'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Unban Dialog */}
            <Dialog open={unbanOpen} onOpenChange={setUnbanOpen}>
                <DialogContent>
                    <DialogHeader><DialogTitle>Cabut ban {user.name}?</DialogTitle></DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Pengguna ini akan bisa login kembali setelah ban dicabut.
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setUnbanOpen(false)}>Batal</Button>
                        <Button disabled={unbanning} onClick={handleUnban}>
                            {unbanning ? 'Memproses...' : 'Cabut Ban'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Change Role Dialog */}
            <Dialog open={roleOpen} onOpenChange={setRoleOpen}>
                <DialogContent>
                    <DialogHeader><DialogTitle>Ganti role {user.name}</DialogTitle></DialogHeader>
                    <div className="space-y-3">
                        <p className="text-sm text-muted-foreground">
                            Role saat ini: <strong>{ROLE_LABELS[user.role]}</strong>
                        </p>
                        <div className="space-y-1.5">
                            <Label>Role baru</Label>
                            <Select value={selectedRole} onValueChange={(v) => v !== null && setSelectedRole(v)}>
                                <SelectTrigger>
                                    <SelectValue>
                                        {(value: string) => ROLE_LABELS[value] ?? value}
                                    </SelectValue>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="user">User</SelectItem>
                                    <SelectItem value="admin">Admin</SelectItem>
                                    <SelectItem value="super_admin">Super Admin</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setRoleOpen(false)}>Batal</Button>
                        <Button
                            disabled={changingRole || selectedRole === user.role}
                            onClick={handleChangeRole}
                        >
                            {changingRole ? 'Menyimpan...' : 'Simpan'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
