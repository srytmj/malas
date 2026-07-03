import { FormEvent, useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { KeyRound, User } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { PageProps } from '@/types';

interface Props extends PageProps {
    name_can_change_at: string | null;
}

export default function SettingsIndex({ name_can_change_at }: Props) {
    const { auth } = usePage().props;
    const isAdmin = auth.user!.role !== 'user';
    const Layout  = isAdmin ? AdminLayout : UserLayout;

    const now            = new Date();
    const cooldownUntil  = name_can_change_at ? new Date(name_can_change_at) : null;
    const canChangeName  = !cooldownUntil || cooldownUntil <= now;

    const cooldownLabel = cooldownUntil && !canChangeName
        ? `Bisa ganti lagi pada ${cooldownUntil.toLocaleString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
          })}`
        : null;

    const nameForm = useForm({ name: auth.user!.name });
    const [nameSaving, setNameSaving] = useState(false);

    const pwForm = useForm({
        current_password:      '',
        password:              '',
        password_confirmation: '',
    });
    const [pwSaving, setPwSaving] = useState(false);

    function submitName(e: FormEvent) {
        e.preventDefault();
        setNameSaving(true);
        nameForm.patch(route('settings.name'), {
            onFinish: () => setNameSaving(false),
        });
    }

    function submitPassword(e: FormEvent) {
        e.preventDefault();
        setPwSaving(true);
        pwForm.put(route('settings.password'), {
            onSuccess: () => pwForm.reset(),
            onFinish:  () => setPwSaving(false),
        });
    }

    return (
        <Layout
            header={
                <PageHeader
                    title="Pengaturan"
                    description="Kelola nama dan password akunmu."
                />
            }
        >
            <Head title="Pengaturan" />

            <div className="max-w-xl space-y-6">
                {/* Name */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <User className="h-4 w-4" />
                            Ganti Nama
                        </CardTitle>
                        <CardDescription>
                            Nama bisa diganti maksimal 1 kali setiap 2 jam.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitName} className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="name">Nama</Label>
                                <Input
                                    id="name"
                                    value={nameForm.data.name}
                                    onChange={(e) => nameForm.setData('name', e.target.value)}
                                    maxLength={100}
                                    disabled={!canChangeName}
                                />
                                {nameForm.errors.name && (
                                    <p className="text-sm text-destructive">{nameForm.errors.name}</p>
                                )}
                                {cooldownLabel && (
                                    <p className="text-sm text-muted-foreground">{cooldownLabel}</p>
                                )}
                            </div>
                            <Button
                                type="submit"
                                disabled={nameSaving || !canChangeName || nameForm.data.name === auth.user!.name}
                            >
                                {nameSaving ? 'Menyimpan...' : 'Simpan Nama'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Password */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <KeyRound className="h-4 w-4" />
                            Ganti Password
                        </CardTitle>
                        <CardDescription>
                            Masukkan password lama dulu, lalu isi password baru.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitPassword} className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="current_password">Password Saat Ini</Label>
                                <Input
                                    id="current_password"
                                    type="password"
                                    value={pwForm.data.current_password}
                                    onChange={(e) => pwForm.setData('current_password', e.target.value)}
                                    autoComplete="current-password"
                                />
                                {pwForm.errors.current_password && (
                                    <p className="text-sm text-destructive">{pwForm.errors.current_password}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="password">Password Baru</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={pwForm.data.password}
                                    onChange={(e) => pwForm.setData('password', e.target.value)}
                                    autoComplete="new-password"
                                />
                                {pwForm.errors.password && (
                                    <p className="text-sm text-destructive">{pwForm.errors.password}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="password_confirmation">Konfirmasi Password Baru</Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    value={pwForm.data.password_confirmation}
                                    onChange={(e) => pwForm.setData('password_confirmation', e.target.value)}
                                    autoComplete="new-password"
                                />
                                {pwForm.errors.password_confirmation && (
                                    <p className="text-sm text-destructive">{pwForm.errors.password_confirmation}</p>
                                )}
                            </div>

                            <Button
                                type="submit"
                                disabled={pwSaving || !pwForm.data.current_password || !pwForm.data.password}
                            >
                                {pwSaving ? 'Menyimpan...' : 'Ganti Password'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </Layout>
    );
}
