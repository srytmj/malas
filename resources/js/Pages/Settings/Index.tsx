import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { ExternalLink, Globe, Languages, Monitor, Moon, ShieldCheck, Sun, User as UserIcon } from 'lucide-react';
import i18n from '@/lib/i18n';
import AdminLayout from '@/Layouts/AdminLayout';
import UserLayout from '@/Layouts/UserLayout';
import { useTheme, type Theme } from '@/hooks/useTheme';
import PageHeader from '@/Components/app/PageHeader';
import { buttonVariants } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Switch } from '@/Components/ui/switch';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';

interface Props extends PageProps {
    sso_account_url: string;
}

export default function SettingsIndex({ sso_account_url }: Props) {
    const { auth, locale } = usePage().props;
    const { t } = useTranslation();
    const { theme, setTheme } = useTheme();
    const roleLabels: Record<string, string> = {
        super_admin: t('settings.roles.super_admin'),
        admin:       t('settings.roles.admin'),
        user:        t('settings.roles.user'),
    };
    const user    = auth.user!;
    const isAdmin = user.role !== 'user';
    const Layout  = isAdmin ? AdminLayout : UserLayout;

    const [isProfilePublic, setIsProfilePublic] = useState(user.is_profile_public);
    const [savingVisibility, setSavingVisibility] = useState(false);
    const [savingLocale, setSavingLocale] = useState(false);

    function handleToggleVisibility(checked: boolean) {
        setIsProfilePublic(checked);
        setSavingVisibility(true);
        router.patch(route('settings.profile-visibility.update'), { is_profile_public: checked }, {
            preserveScroll: true,
            onError: () => setIsProfilePublic(!checked),
            onFinish: () => setSavingVisibility(false),
        });
    }

    function handleLocaleChange(value: string | null) {
        if (!value) return;
        void i18n.changeLanguage(value);
        setSavingLocale(true);
        router.patch(route('settings.locale.update'), { locale: value }, {
            preserveScroll: true,
            onFinish: () => setSavingLocale(false),
        });
    }

    return (
        <Layout
            header={
                <PageHeader
                    title={t('settings.title')}
                    description={t('settings.description')}
                />
            }
        >
            <Head title={t('settings.title')} />

            <div className="max-w-xl space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <UserIcon className="h-4 w-4" />
                            {t('settings.profile')}
                        </CardTitle>
                        <CardDescription>
                            {t('settings.profileDescription')}
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
                                <p className="text-muted-foreground">{t('settings.email')}</p>
                                <p className="font-medium">{user.email}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground">{t('settings.role')}</p>
                                <Badge variant="outline" className="gap-1">
                                    <ShieldCheck className="h-3 w-3" />
                                    {roleLabels[user.role] ?? user.role}
                                </Badge>
                            </div>
                        </div>

                        <a
                            href={sso_account_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className={cn(buttonVariants({ variant: 'outline' }), 'w-full')}
                        >
                            {t('settings.manageAccount')}
                            <ExternalLink className="ml-1.5 h-3.5 w-3.5" />
                        </a>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Languages className="h-4 w-4" />
                            {t('locale.cardTitle')}
                        </CardTitle>
                        <CardDescription>{t('locale.cardDescription')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Select value={locale} onValueChange={handleLocaleChange} disabled={savingLocale}>
                            <SelectTrigger className="w-full">
                                <SelectValue>
                                    {(value: string) => t(`locale.${value}`)}
                                </SelectValue>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="id">{t('locale.id')}</SelectItem>
                                <SelectItem value="en">{t('locale.en')}</SelectItem>
                                <SelectItem value="ja">{t('locale.ja')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            {theme === 'light' && <Sun className="h-4 w-4" />}
                            {theme === 'dark' && <Moon className="h-4 w-4" />}
                            {theme === 'system' && <Monitor className="h-4 w-4" />}
                            {t('theme.cardTitle')}
                        </CardTitle>
                        <CardDescription>{t('theme.cardDescription')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Select value={theme} onValueChange={(v) => v && setTheme(v as Theme)}>
                            <SelectTrigger className="w-full">
                                <SelectValue>
                                    {(value: string) => t(`theme.${value}`)}
                                </SelectValue>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="light">{t('theme.light')}</SelectItem>
                                <SelectItem value="dark">{t('theme.dark')}</SelectItem>
                                <SelectItem value="system">{t('theme.system')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Globe className="h-4 w-4" />
                            {t('settings.publicProfile')}
                        </CardTitle>
                        <CardDescription>
                            {t('settings.publicProfileDescription')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex items-center justify-between rounded-lg border p-4">
                            <div>
                                <p className="text-sm font-medium">{t('settings.showProfilePublicly')}</p>
                                <p className="text-xs text-muted-foreground">
                                    {t('settings.showProfilePublicHint')}
                                </p>
                            </div>
                            <Switch
                                checked={isProfilePublic}
                                onCheckedChange={handleToggleVisibility}
                                disabled={savingVisibility}
                            />
                        </div>
                        <Link
                            href={route('profile.show', user.username ?? user.id)}
                            className={cn(buttonVariants({ variant: 'outline' }), 'w-full')}
                        >
                            {t('settings.viewMyProfile')}
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </Layout>
    );
}
