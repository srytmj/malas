import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { BookOpen, HandCoins, Library, LogIn, Megaphone } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { LanguageSwitcher } from '@/Components/app/LanguageSwitcher';
import { ThemeSwitcher } from '@/Components/app/ThemeSwitcher';
import { LoginMethodDialog } from '@/Components/app/LoginMethodDialog';

export default function Landing() {
    const { t } = useTranslation();
    const [loginOpen, setLoginOpen] = useState(false);

    const FEATURES = [
        { icon: BookOpen, key: 'catalog' },
        { icon: Library, key: 'collection' },
        { icon: HandCoins, key: 'loans' },
        { icon: Megaphone, key: 'announcements' },
    ] as const;

    return (
        <>
            <Head title={t('landing.pageTitle')} />

            <div className="flex min-h-screen flex-col bg-background">
                <header className="border-b">
                    <div className="mx-auto flex w-full max-w-5xl items-center justify-between px-4 py-3">
                        <span className="text-sm font-bold tracking-tight">Malas</span>
                        <div className="flex items-center gap-1.5">
                            <LanguageSwitcher />
                            <ThemeSwitcher />
                            <Button variant="outline" size="sm" onClick={() => setLoginOpen(true)}>
                                <LogIn className="mr-1.5 h-3.5 w-3.5" />
                                {t('landing.header.login')}
                            </Button>
                        </div>
                    </div>
                </header>

                <main className="flex flex-1 flex-col items-center px-4 py-16">
                    <div className="w-full max-w-3xl text-center">
                        <p className="text-sm font-medium text-muted-foreground">Malas</p>
                        <h1 className="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">
                            {t('landing.heading')}
                        </h1>
                        <p className="mx-auto mt-4 max-w-xl text-muted-foreground">
                            {t('landing.subheading')}
                        </p>

                        <div className="mt-8 flex flex-col items-center gap-3">
                            <Button size="lg" onClick={() => setLoginOpen(true)}>
                                <LogIn className="mr-2 h-4 w-4" />
                                {t('landing.loginButton')}
                            </Button>
                        </div>

                        <LoginMethodDialog open={loginOpen} onOpenChange={setLoginOpen} />
                    </div>

                    <div className="mt-16 grid w-full max-w-3xl gap-4 sm:grid-cols-2">
                        {FEATURES.map((feature) => (
                            <Card key={feature.key}>
                                <CardHeader className="flex flex-row items-center gap-3 space-y-0">
                                    <div className="rounded-md bg-muted p-2">
                                        <feature.icon className="h-5 w-5 text-foreground" />
                                    </div>
                                    <CardTitle className="text-base">{t(`landing.features.${feature.key}.title`)}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm text-muted-foreground">{t(`landing.features.${feature.key}.description`)}</p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </main>

                <footer className="border-t">
                    <div className="mx-auto flex w-full max-w-5xl flex-col items-center gap-2 px-4 py-6 text-center sm:flex-row sm:justify-between sm:text-left">
                        <div>
                            <p className="text-sm font-semibold">Malas</p>
                            <p className="text-xs text-muted-foreground">{t('landing.footer.tagline')}</p>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            {t('landing.footer.copyright', { year: new Date().getFullYear() })}
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
