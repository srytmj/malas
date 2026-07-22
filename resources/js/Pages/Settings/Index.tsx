import { Head, usePage } from '@inertiajs/react';
import { ExternalLink, ShieldCheck, User as UserIcon } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import { buttonVariants } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';

interface Props extends PageProps {
    sso_account_url: string;
}

const ROLE_LABELS: Record<string, string> = {
    super_admin: 'Super Admin',
    admin:       'Admin',
    user:        'User',
};

export default function SettingsIndex({ sso_account_url }: Props) {
    const { auth } = usePage().props;
    const user    = auth.user!;
    const isAdmin = user.role !== 'user';
    const Layout  = isAdmin ? AdminLayout : UserLayout;

    return (
        <Layout
            header={
                <PageHeader
                    title="Pengaturan"
                    description="Informasi akunmu, dikelola lewat whitearchive.id."
                />
            }
        >
            <Head title="Pengaturan" />

            <div className="max-w-xl space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <UserIcon className="h-4 w-4" />
                            Profil
                        </CardTitle>
                        <CardDescription>
                            Nama, username, dan email disinkron dari akun whitearchive.id-mu setiap login.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex items-center gap-4">
                            <Avatar className="h-14 w-14">
                                <AvatarImage src={user.avatar ?? undefined} alt={user.name} />
                                <AvatarFallback>{user.name.slice(0, 2).toUpperCase()}</AvatarFallback>
                            </Avatar>
                            <div>
                                <p className="font-medium">{user.name}</p>
                                {user.username && (
                                    <p className="text-sm text-muted-foreground">@{user.username}</p>
                                )}
                            </div>
                        </div>

                        <div className="grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <p className="text-muted-foreground">Email</p>
                                <p className="font-medium">{user.email}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground">Role</p>
                                <Badge variant="outline" className="gap-1">
                                    <ShieldCheck className="h-3 w-3" />
                                    {ROLE_LABELS[user.role] ?? user.role}
                                </Badge>
                            </div>
                        </div>

                        <a
                            href={sso_account_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className={cn(buttonVariants({ variant: 'outline' }), 'w-full')}
                        >
                            Kelola Akun di whitearchive.id
                            <ExternalLink className="ml-1.5 h-3.5 w-3.5" />
                        </a>
                    </CardContent>
                </Card>
            </div>
        </Layout>
    );
}
