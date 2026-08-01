import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AlertTriangle, Ban, Home, ServerCrash } from 'lucide-react';
import { buttonVariants } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import { type LucideIcon } from 'lucide-react';

interface Props {
    status: 400 | 403 | 404 | 500 | 502 | 503;
}

const ERROR_ICONS: Record<number, LucideIcon> = {
    400: AlertTriangle,
    403: Ban,
    404: AlertTriangle,
    500: ServerCrash,
    502: ServerCrash,
    503: ServerCrash,
};

export default function Error({ status }: Props) {
    const { t } = useTranslation();
    const key = ERROR_ICONS[status] ? status : 500;
    const Icon = ERROR_ICONS[key];
    const title = t(`errorPage.${key}.title`);
    const description = t(`errorPage.${key}.description`);

    return (
        <>
            <Head title={`${status} — ${title}`} />

            <div className="flex min-h-screen flex-col items-center justify-center bg-background px-4 text-center">
                <div className="rounded-full bg-muted p-5">
                    <Icon className="h-10 w-10 text-muted-foreground" />
                </div>

                <p className="mt-6 text-6xl font-bold tracking-tight text-foreground">{status}</p>
                <h1 className="mt-3 text-xl font-semibold">{title}</h1>
                <p className="mt-2 max-w-sm text-sm text-muted-foreground">{description}</p>

                <div className="mt-8 flex gap-3">
                    <button
                        type="button"
                        onClick={() => window.history.back()}
                        className={cn(buttonVariants({ variant: 'outline' }))}
                    >
                        {t('errorPage.back')}
                    </button>
                    <Link href="/" className={cn(buttonVariants())}>
                        <Home className="mr-2 h-4 w-4" />
                        {t('errorPage.home')}
                    </Link>
                </div>
            </div>
        </>
    );
}
