import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { BookOpen, HandCoins, Library, LogIn, Megaphone } from 'lucide-react';
import { buttonVariants } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { cn } from '@/lib/utils';

export default function Landing() {
    const { t } = useTranslation();

    const FEATURES = [
        { icon: BookOpen, key: 'catalog' },
        { icon: Library, key: 'collection' },
        { icon: HandCoins, key: 'loans' },
        { icon: Megaphone, key: 'announcements' },
    ] as const;

    return (
        <>
            <Head title={t('landing.pageTitle')} />

            <div className="flex min-h-screen flex-col items-center bg-background px-4 py-16">
                <div className="w-full max-w-3xl text-center">
                    <p className="text-sm font-medium text-muted-foreground">MALAS</p>
                    <h1 className="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">
                        {t('landing.heading')}
                    </h1>
                    <p className="mx-auto mt-4 max-w-xl text-muted-foreground">
                        {t('landing.subheading')}
                    </p>

                    <div className="mt-8">
                        <a
                            href={route('sso.redirect')}
                            className={cn(buttonVariants({ size: 'lg' }))}
                        >
                            <LogIn className="mr-2 h-4 w-4" />
                            {t('landing.loginButton')}
                        </a>
                    </div>
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
            </div>
        </>
    );
}
